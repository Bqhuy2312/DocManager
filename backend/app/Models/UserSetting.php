<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'user_settings';

    protected $fillable = [
        'user_id',
        'language',
        'dark_mode',
        'notify_upload',
        'notify_edit',
        'notify_approve',
        'notify_system'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}