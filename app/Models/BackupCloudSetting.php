<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The single row holding the cloud backup destinations set up in the UI.
 * Read it through App\Services\Backups\CloudBackupSettings, which handles a
 * row that cannot be decrypted (APP_KEY changed).
 */
class BackupCloudSetting extends Model
{
    protected $table = 'backup_cloud_settings';

    protected $fillable = ['settings', 'status', 'updated_by'];

    /** Credentials never leave the model in arrays or JSON. */
    protected $hidden = ['settings'];

    protected $casts = [
        'settings' => 'encrypted:array',
        'status' => 'array',
    ];

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
