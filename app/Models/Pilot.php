<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pilot extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'pilots';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'callsign',
        'email',
        'password',
        'ifc',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    public function assignedTasks()
    {
        return $this->belongsToMany(Task::class, 'task_assignees', 'pilot_id', 'task_id');
    }

    public function resolutionTasks()
    {
        return $this->hasMany(Task::class, 'resolution_user_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'pilot_permissions', 'pilot_id', 'permission_id');
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }
}
