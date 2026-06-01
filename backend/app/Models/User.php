<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'users';

    protected $fillable = [
        'department_id',
        'full_name',
        'email',
        'password',
        'role',
        'avatar'
    ];

    protected $hidden = [
        'password'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'created_by');
    }

    public function approvedDocuments()
    {
        return $this->hasMany(Document::class, 'approved_by');
    }

    public function favorites()
    {
        return $this->belongsToMany(
            Document::class,
            'favorites',
            'user_id',
            'document_id'
        );
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}