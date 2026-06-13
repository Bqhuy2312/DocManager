<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'user_settings';

    protected $fillable = [
        'user_id',
        'language',
        'auto_save',
        'dark_mode',
        'timezone',
        'email_enabled',
        'in_app_enabled',
        'notify_upload',
        'notify_edit',
        'notify_approve',
        'notify_system',
        'two_factor_enabled',
        'two_factor_pin_hash'
    ];

    protected $hidden = [
        'two_factor_pin_hash',
    ];

    protected $casts = [
        'auto_save' => 'boolean',
        'dark_mode' => 'boolean',
        'email_enabled' => 'boolean',
        'in_app_enabled' => 'boolean',
        'notify_upload' => 'boolean',
        'notify_edit' => 'boolean',
        'notify_approve' => 'boolean',
        'notify_system' => 'boolean',
        'two_factor_enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
