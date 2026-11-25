<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The single row holding the outgoing mail server and the daily summary set
 * up in the UI. Read it through App\Services\Mail\MailSettings.
 */
class MailSetting extends Model
{
    protected $table = 'mail_settings';

    protected $fillable = ['settings', 'digest', 'status', 'updated_by'];

    /** The SMTP password never leaves the model in arrays or JSON. */
    protected $hidden = ['settings'];

    protected $casts = [
        'settings' => 'encrypted:array',
        'digest' => 'array',
        'status' => 'array',
    ];

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
