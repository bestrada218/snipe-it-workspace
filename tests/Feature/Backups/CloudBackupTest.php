<?php

namespace Tests\Feature\Backups;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Masbug\Flysystem\GoogleDriveAdapter;
use Tests\TestCase;
use ZipArchive;

/**
 * Off-site backup copies (backup_s3 / backup_google disks), archive
 * encryption and bringing a backup back from the cloud.
 */
class CloudBackupTest extends TestCase
{
    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workDir = storage_path('framework/testing/cloud-backup-'.uniqid());
        File::ensureDirectoryExists($this->workDir.'/source');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workDir);

        parent::tearDown();
    }

    public function test_backup_is_written_to_local_and_cloud_disks_encrypted(): void
    {
        Storage::fake('backup');
        Storage::fake('backup_s3');
        File::put($this->workDir.'/source/evidence.pdf', 'approval evidence');

        config([
            'backup.backup.source.files.include' => [$this->workDir.'/source'],
            'backup.backup.source.files.relative_path' => $this->workDir,
            'backup.backup.source.databases' => [],
            'backup.backup.destination.disks' => ['backup', 'backup_s3'],
            'backup.backup.password' => 'correct horse battery',
        ]);

        $this->artisan('backup:run', ['--only-files' => true, '--disable-notifications' => true])->assertSuccessful();

        $localFiles = Storage::disk('backup')->files('backups');
        $this->assertCount(1, $localFiles);
        $this->assertSame($localFiles, Storage::disk('backup_s3')->files('backups'));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('backup')->path($localFiles[0])));
        $entry = collect(range(0, $zip->numFiles - 1))
            ->map(fn (int $i) => $zip->statIndex($i))
            ->first(fn (array $stat) => str_ends_with($stat['name'], 'evidence.pdf'));

        $this->assertNotNull($entry);
        $this->assertSame(ZipArchive::EM_AES_256, $entry['encryption_method']);
        $this->assertFalse(@$zip->getFromName($entry['name']));

        $zip->setPassword('correct horse battery');
        $this->assertSame('approval evidence', $zip->getFromName($entry['name']));
        $zip->close();
    }

    public function test_cloud_copy_is_refused_without_a_password_but_the_local_copy_is_kept(): void
    {
        Storage::fake('backup');
        Storage::fake('backup_s3');
        File::put($this->workDir.'/source/evidence.pdf', 'approval evidence');

        config([
            'backup.backup.source.files.include' => [$this->workDir.'/source'],
            'backup.backup.source.files.relative_path' => $this->workDir,
            'backup.backup.source.databases' => [],
            'backup.backup.destination.disks' => ['backup', 'backup_s3'],
            'backup.backup.password' => null,
        ]);

        $this->artisan('snipeit:backup', ['--filename' => 'unencrypted-check'])
            ->expectsOutputToContain('Backup saved on: backup.')
            ->expectsOutputToContain('set BACKUP_ARCHIVE_PASSWORD')
            ->assertFailed();

        $this->assertCount(1, Storage::disk('backup')->files('backups'));
        $this->assertSame([], Storage::disk('backup_s3')->files('backups'));
    }

    public function test_backup_reports_a_destination_that_failed(): void
    {
        Storage::fake('backup');
        File::put($this->workDir.'/source/evidence.pdf', 'approval evidence');

        config([
            'backup.backup.source.files.include' => [$this->workDir.'/source'],
            'backup.backup.source.files.relative_path' => $this->workDir,
            'backup.backup.source.databases' => [],
            // An S3 disk with no bucket cannot be written to.
            'filesystems.disks.backup_s3.bucket' => '',
            'filesystems.disks.backup_s3.endpoint' => 'http://127.0.0.1:9',
            'backup.backup.destination.disks' => ['backup', 'backup_s3'],
            'backup.backup.password' => 'correct horse battery',
        ]);

        $this->artisan('snipeit:backup', ['--filename' => 'failing-destination'])
            ->expectsOutputToContain('Backup saved on: backup.')
            ->expectsOutputToContain('Backup failed on: backup_s3.')
            ->assertFailed();
    }

    public function test_google_drive_without_credentials_does_not_cost_the_local_copy(): void
    {
        Storage::fake('backup');
        File::put($this->workDir.'/source/evidence.pdf', 'approval evidence');

        config([
            'backup.backup.source.files.include' => [$this->workDir.'/source'],
            'backup.backup.source.files.relative_path' => $this->workDir,
            'backup.backup.source.databases' => [],
            'backup.backup.destination.disks' => ['backup', 'backup_google'],
            'backup.backup.password' => 'correct horse battery',
        ]);

        $this->artisan('snipeit:backup', ['--filename' => 'drive-not-configured'])
            ->expectsOutputToContain('Backup saved on: backup.')
            ->expectsOutputToContain('Backup failed on: backup_google.')
            ->assertFailed();

        $this->assertCount(1, Storage::disk('backup')->files('backups'));
    }

    public function test_restore_refuses_an_encrypted_backup_without_its_password(): void
    {
        $archive = $this->encryptedBackup('right-password');

        $this->artisan('snipeit:restore', ['filename' => $archive, '--sanitize-guess-prefix' => true])
            ->expectsOutputToContain('This backup is encrypted')
            ->assertFailed();

        $this->artisan('snipeit:restore', ['filename' => $archive, '--sanitize-guess-prefix' => true, '--password' => 'wrong-password'])
            ->expectsOutputToContain('the backup password is wrong')
            ->assertFailed();
    }

    public function test_restore_reads_an_encrypted_backup_with_its_password(): void
    {
        $archive = $this->encryptedBackup('right-password');

        $this->artisan('snipeit:restore', ['filename' => $archive, '--sanitize-guess-prefix' => true, '--password' => 'right-password'])
            ->expectsOutputToContain('--sanitize-with-prefix=')
            ->doesntExpectOutputToContain('encrypted');

        config(['backup.backup.password' => 'right-password']);

        $this->artisan('snipeit:restore', ['filename' => $archive, '--sanitize-guess-prefix' => true])
            ->expectsOutputToContain('--sanitize-with-prefix=');
    }

    public function test_latest_cloud_backup_can_be_brought_back_to_the_server(): void
    {
        Storage::fake('backup');
        Storage::fake('backup_s3');
        config(['backup.backup.destination.disks' => ['backup', 'backup_s3']]);

        $cloud = Storage::disk('backup_s3');
        $cloud->put('backups/snipe-it-older.zip', 'older');
        $cloud->put('backups/snipe-it-newest.zip', 'newest');
        touch($cloud->path('backups/snipe-it-older.zip'), now()->subDay()->getTimestamp());

        $this->artisan('snipeit:backup-pull', ['disk' => 'backup_s3'])
            ->expectsOutputToContain('snipe-it-newest.zip')
            ->assertSuccessful();
        Storage::disk('backup')->assertMissing('backups/snipe-it-newest.zip');

        $this->artisan('snipeit:backup-pull', ['disk' => 'backup_s3', '--latest' => true])->assertSuccessful();
        $this->assertSame('newest', Storage::disk('backup')->get('backups/snipe-it-newest.zip'));

        $this->artisan('snipeit:backup-pull', ['disk' => 'backup_s3', 'file' => 'snipe-it-older.zip'])->assertSuccessful();
        $this->assertSame('older', Storage::disk('backup')->get('backups/snipe-it-older.zip'));
    }

    public function test_pull_rejects_a_disk_that_is_not_a_cloud_backup_disk(): void
    {
        $this->artisan('snipeit:backup-pull', ['disk' => 'backup'])->assertFailed();
        $this->artisan('snipeit:backup-pull', ['disk' => 'nowhere'])->assertFailed();
    }

    public function test_google_drive_disk_builds_without_contacting_google(): void
    {
        config(['filesystems.disks.backup_google' => array_merge(config('filesystems.disks.backup_google'), [
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'refresh_token' => 'refresh-token',
            // A folder id needs no lookup; a folder name is resolved on Google
            // as soon as the disk is built.
            'folder_id' => 'drive-folder-id',
        ])]);

        $this->assertInstanceOf(GoogleDriveAdapter::class, Storage::disk('backup_google')->getAdapter());
    }

    public function test_google_drive_disk_explains_missing_credentials(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('BACKUP_GOOGLE_SERVICE_ACCOUNT_JSON');

        Storage::build(['driver' => 'google']);
    }

    private function encryptedBackup(string $password): string
    {
        $path = $this->workDir.'/encrypted-backup.zip';
        $sql = "CREATE TABLE `settings` (\n  `id` int NOT NULL\n) ENGINE=InnoDB;\nCREATE TABLE `migrations` (\n  `id` int NOT NULL\n) ENGINE=InnoDB;\n";

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('db-dumps/mysql-snipeit.sql', $sql);
        $zip->setPassword($password);
        $zip->setEncryptionName('db-dumps/mysql-snipeit.sql', ZipArchive::EM_AES_256);
        $zip->close();

        return $path;
    }
}
