<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'documents';

    protected $fillable = [
        'category_id',
        'folder_id',
        'created_by',
        'approved_by',
        'title',
        'description',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'version',
        'status'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

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