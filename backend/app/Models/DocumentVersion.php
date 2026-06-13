<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'document_versions';

    protected $fillable = [
        'document_id',
        'updated_by',
        'version',
        'title',
        'description',
        'file_name',
        'file_path',
        'cloudinary_public_id',
        'file_size',
        'mime_type',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
