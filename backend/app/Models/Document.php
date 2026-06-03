<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'documents';

    protected $fillable = [
        'folder_id',
        'created_by',
        'approved_by',
        'title',
        'description',
        'file_name',
        'file_path',
        'cloudinary_public_id',
        'file_size',
        'mime_type',
        'version',
        'status'
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function tags()
    {
        return $this->hasMany(DocumentTag::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(
            User::class,
            'favorites',
            'document_id',
            'user_id'
        );
    }
}
