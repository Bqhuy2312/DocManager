<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'description'
    ];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}