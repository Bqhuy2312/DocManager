<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}