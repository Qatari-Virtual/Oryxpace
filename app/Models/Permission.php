<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'oryxpace_custom_permissions';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function pilots()
    {
        return $this->belongsToMany(Pilot::class, 'oryxpace_pilot_permissions', 'permission_id', 'pilot_id');
    }

    public function access()
    {
        return $this->hasMany(PermissionAccess::class, 'permission_id');
    }

    public function roleTypes()
    {
        return $this->hasMany(PermissionRoleType::class, 'permission_id');
    }

    public function isTeamPermission(): bool
    {
        return $this->roleTypes()->exists();
    }
}
