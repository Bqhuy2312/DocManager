<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'favorites';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'document_id'
    ];
}