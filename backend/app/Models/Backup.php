<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'created_by',
        'type',
        'status',
        'file_name',
        'file_path',
        'file_size',
        'documents_count',
        'versions_count',
        'avatars_count',
        'message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'documents_count' => 'integer',
        'versions_count' => 'integer',
        'avatars_count' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
