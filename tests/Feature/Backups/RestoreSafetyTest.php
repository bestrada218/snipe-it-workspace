<?php

namespace Tests\Feature\Backups;

use App\Console\Commands\RestoreFromBackup;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * A backup that cannot be read in full is refused before the database is
 * wiped, whatever the reason: no database dump, damaged dump, or a password
 * that does not open it.
 */
class RestoreSafetyTest extends TestCase
{
    private const SQL = "CREATE TABLE `settings` (\n  `id` int NOT NULL\n) ENGINE=InnoDB;\n";

    /** @var list<string> */
    private array $planted = [];

    protected function tearDown(): void
    {
        foreach ($this->planted as $filename) {
            Storage::delete('app/backups/'.$filename);
        }
        parent::tearDown();
    }

    public function test_backup_without_database_dump_is_refused_before_wiping(): void
    {
        $this->assertRefusedBeforeWipe($this->plant(fn (ZipArchive $zip) => $zip->addFromString('storage/private_uploads/x.pdf', 'pdf')));
    }

    public function test_backup_with_wrong_password_is_refused_before_wiping(): void
    {
        config(['backup.backup.password' => 'current-password']);

        $this->assertRefusedBeforeWipe($this->plant(function (ZipArchive $zip) {
            $zip->addFromString('db-dumps/mysql-snipeit.sql', self::SQL);
            $zip->setPassword('older-password');
            $zip->setEncryptionName('db-dumps/mysql-snipeit.sql', ZipArchive::EM_AES_256);
        }));
    }

    public function test_encrypted_backup_without_password_is_refused_before_wiping(): void
    {
        $this->assertRefusedBeforeWipe($this->plant(function (ZipArchive $zip) {
            $zip->addFromString('db-dumps/mysql-snipeit.sql', self::SQL);
            $zip->setPassword('some-password');
            $zip->setEncryptionName('db-dumps/mysql-snipeit.sql', ZipArchive::EM_AES_256);
        }));
    }

    public function test_damaged_database_dump_is_detected(): void
    {
        $path = Storage::path('app/backups/'.$this->plant(function (ZipArchive $zip) {
            $zip->addFromString('db-dumps/mysql-snipeit.sql', self::SQL);
            $zip->setCompressionName('db-dumps/mysql-snipeit.sql', ZipArchive::CM_STORE);
        }));

        $this->assertNull(RestoreFromBackup::archiveProblem($path, null));

        // Flip one byte of the stored dump: the CRC no longer matches.
        file_put_contents($path, str_replace('ENGINE=InnoDB', 'ENGINE=Innodb', file_get_contents($path)));

        $this->assertNotNull(RestoreFromBackup::archiveProblem($path, null));
        $this->assertRefusedBeforeWipe(basename($path));
    }

    public function test_a_damaged_attachment_stops_the_restore_before_wiping(): void
    {
        $filename = $this->plant(static function (ZipArchive $zip) {
            $zip->addFromString('db-dumps/mysql-snipeit.sql', self::SQL);
            $zip->addFromString('storage/private_uploads/evidence.pdf', 'original attachment');
            $zip->setCompressionName('storage/private_uploads/evidence.pdf', ZipArchive::CM_STORE);
        });
        $path = Storage::path('app/backups/'.$filename);
        $this->assertNull(RestoreFromBackup::archiveProblem($path, null));
        file_put_contents($path, str_replace('original attachment', 'modified attachment', file_get_contents($path)));

        $this->assertStringContainsString('attachment', RestoreFromBackup::archiveProblem($path, null));
        $this->assertRefusedBeforeWipe($filename);
    }

    public function test_restore_command_fails_on_an_unreadable_backup(): void
    {
        $path = Storage::path('app/backups/'.$this->plant(fn (ZipArchive $zip) => $zip->addFromString('readme.txt', 'no dump')));

        $this->artisan('snipeit:restore', ['filename' => $path, '--force' => true])
            ->expectsOutputToContain('no database dump')
            ->assertFailed();
    }

    public function test_a_second_web_restore_cannot_start_while_one_is_running(): void
    {
        $filename = $this->plant(fn (ZipArchive $zip) => $zip->addFromString('db-dumps/mysql-snipeit.sql', self::SQL));
        $lock = Cache::lock('snipeit-backup-restore', 60);
        $this->assertTrue($lock->get());

        try {
            $this->actingAs(User::factory()->superuser()->create())
                ->post(route('settings.backups.restore', $filename))
                ->assertSessionHas('error', trans('admin/settings/message.restore.in_progress'));
        } finally {
            $lock->release();
        }
    }

    private function plant(callable $fill): string
    {
        $filename = 'restore-safety-'.uniqid().'.zip';
        Storage::makeDirectory('app/backups');
        $zip = new ZipArchive;
        $zip->open(Storage::path('app/backups/'.$filename), ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $fill($zip);
        $zip->close();
        $this->planted[] = $filename;

        return $filename;
    }

    private function assertRefusedBeforeWipe(string $filename): void
    {
        Artisan::spy();
        $keeper = User::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('settings.backups.restore', $filename))
            ->assertRedirect(route('settings.backups.index'))
            ->assertSessionHas('error');

        Artisan::shouldNotHaveReceived('call', fn ($command) => in_array($command, ['snipeit:backup', 'db:wipe', 'snipeit:restore', 'migrate'], true));
        $this->assertModelExists($keeper);
    }
}
