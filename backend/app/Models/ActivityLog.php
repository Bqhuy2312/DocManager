<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasUuid;

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
