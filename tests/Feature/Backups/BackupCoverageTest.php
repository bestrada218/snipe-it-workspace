<?php

namespace Tests\Feature\Backups;

use App\Enums\FileStorage;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The customised flows store evidence files and data that a restore
 * must bring back. These checks fail if a change leaves any of it out
 * of the backup or the restore.
 */
class BackupCoverageTest extends TestCase
{
    /**
     * @return array<string, array{FileStorage}>
     */
    public static function evidenceFolders(): array
    {
        return [
            'duplicate assignment approvals' => [FileStorage::DuplicateApprovals],
            'shipment purchase approvals' => [FileStorage::AssetShipmentApprovals],
        ];
    }

    #[DataProvider('evidenceFolders')]
    public function test_evidence_folder_is_backed_up_and_restored(FileStorage $folder): void
    {
        $this->assertStringStartsWith('storage/private_uploads/', $folder->privateDir());
        $this->assertContains(base_path('storage/private_uploads'), config('backup.backup.source.files.include'));
        $this->assertContains($folder->privateDir(), FileStorage::privateDirsForRestore());
    }

    public function test_restore_accepts_every_evidence_file_type(): void
    {
        $allowed = config('filesystems.allowed_upload_extensions_array');

        foreach (['pdf', 'jpg', 'jpeg', 'png', 'webp'] as $extension) {
            $this->assertContains($extension, $allowed, "A restore would skip .{$extension} evidence files.");
        }
    }

    public function test_backup_runs_every_six_hours(): void
    {
        $this->app->make(Kernel::class)->bootstrap();

        $backup = collect($this->app->make(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'snipeit:backup'));

        $this->assertNotNull($backup, 'snipeit:backup is not scheduled.');
        $this->assertSame('30 1,7,13,19 * * *', $backup->expression);
    }
}
