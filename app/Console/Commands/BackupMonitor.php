<?php

namespace App\Console\Commands;

use App\Services\Backups\CloudBackupSettings;
use Illuminate\Console\Command;
use Spatie\Backup\Config\MonitoredBackupsConfig;
use Spatie\Backup\Events\HealthyBackupWasFound;
use Spatie\Backup\Events\UnhealthyBackupWasFound;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatusFactory;

/**
 * backup:monitor, but also watching the cloud destinations set up in
 * Settings → Backups → Cloud copies. spatie's own command reads its config
 * when the console boots, before those destinations are known.
 *
 * An unhealthy destination (no backup, or the newest older than a day)
 * fires the same event as backup:monitor, so the same mail is sent.
 */
class BackupMonitor extends Command
{
    protected $signature = 'snipeit:backup-monitor';

    protected $description = 'Check that every backup destination, cloud copies included, has a recent backup.';

    public function handle(): int
    {
        CloudBackupSettings::apply();

        $disks = config('backup.backup.destination.disks', ['backup']);
        $monitors = array_map(
            fn (array $monitor) => ['disks' => array_values(array_unique(array_merge($monitor['disks'] ?? [], $disks)))] + $monitor,
            config('backup.monitor_backups', [])
        );

        $healthy = true;
        $copyResults = CloudBackupSettings::results();
        foreach (BackupDestinationStatusFactory::createForMonitorConfig(MonitoredBackupsConfig::fromArray($monitors)) as $status) {
            $name = $status->backupDestination()->backupName();
            $disk = $status->backupDestination()->diskName();

            if ($status->isHealthy() && ($copyResults[$disk]['ok'] ?? true)) {
                $this->info("The {$name} backups on the {$disk} disk are considered healthy.");
                event(new HealthyBackupWasFound($status));
            } else {
                $healthy = false;
                $this->error("The {$name} backups on the {$disk} disk are considered unhealthy!");
                event(new UnhealthyBackupWasFound($status));
            }
        }

        return $healthy ? self::SUCCESS : self::FAILURE;
    }
}
