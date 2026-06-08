<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'departments';

    protected $fillable = [
        'name',
        'description'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
