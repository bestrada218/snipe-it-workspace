<?php

namespace App\Services\Backups;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Filesystem\FilesystemAdapter;
use InvalidArgumentException;
use League\Flysystem\Filesystem;
use League\Flysystem\Visibility;
use Masbug\Flysystem\GoogleDriveAdapter;

/**
 * Builds the "google" filesystem driver used by the backup_google disk, so
 * backups can be copied to Google Drive. Nothing talks to Google until a
 * file is actually written or listed.
 */
class GoogleDriveDisk
{
    /**
     * @param  array{service_account_json?: ?string, client_id?: ?string, client_secret?: ?string, refresh_token?: ?string, folder?: ?string, folder_id?: ?string, shared_drive?: bool}  $config
     */
    public static function make(array $config): FilesystemAdapter
    {
        $client = new Client;
        $client->setApplicationName(config('app.name').' backups');
        $client->setScopes([Drive::DRIVE]);

        $serviceAccount = $config['service_account_json'] ?? null;
        if (filled($serviceAccount)) {
            $client->setAuthConfig(is_file($serviceAccount)
                ? $serviceAccount
                : json_decode($serviceAccount, true, flags: JSON_THROW_ON_ERROR));
        } elseif (filled($config['client_id'] ?? null) && filled($config['client_secret'] ?? null) && filled($config['refresh_token'] ?? null)) {
            $client->setClientId($config['client_id']);
            $client->setClientSecret($config['client_secret']);
            // An expired token carrying a refresh token makes the client
            // fetch a fresh access token on the first request.
            $client->setAccessToken([
                'access_token' => '',
                'refresh_token' => $config['refresh_token'],
                'expires_in' => 0,
                'created' => 0,
            ]);
        } else {
            throw new InvalidArgumentException('The backup_google disk needs BACKUP_GOOGLE_SERVICE_ACCOUNT_JSON, or BACKUP_GOOGLE_CLIENT_ID, BACKUP_GOOGLE_CLIENT_SECRET and BACKUP_GOOGLE_REFRESH_TOKEN.');
        }

        $folderId = $config['folder_id'] ?? null;
        $adapter = new GoogleDriveAdapter(
            new Drive($client),
            filled($folderId) ? null : ($config['folder'] ?? 'Snipe-IT Backups'),
            filled($folderId) ? ['sharedFolderId' => $folderId] : [],
        );

        if ($config['shared_drive'] ?? false) {
            $adapter->enableTeamDriveSupport();
        }

        $config['visibility'] = Visibility::PRIVATE;

        return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
    }
}
