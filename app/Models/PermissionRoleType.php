<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionRoleType extends Model
{
    protected $table = 'oryxpace_permission_role_types';

    protected $fillable = [
        'permission_id',
        'role_name',
        'level',
    ];

    public function getRoleNameAttribute($value)
    {
        return trim($value);
    }

    public function getLevelAttribute($value)
    {
        return trim($value);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

    public function getLevelBadgeClass(): string
    {
        return match ($this->level) {
            'Executive' => 'badge-primary',
            'Manager' => 'badge-secondary',
            'Staff' => 'badge-outline',
            default => 'badge-ghost',
        };
    }
}
