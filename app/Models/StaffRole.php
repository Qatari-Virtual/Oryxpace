<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffRole extends Model
{
    protected $table = 'staff_roles';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'level',
        'role_name',
    ];

    protected $casts = [
        'role_name' => 'string',
        'level' => 'string',
    ];

    public function getRoleNameAttribute($value)
    {
        return trim($value);
    }

    public function getLevelAttribute($value)
    {
        return trim($value);
    }

    public function pilot()
    {
        return $this->belongsTo(Pilot::class, 'user_id');
    }

    public function departmentAssignments()
    {
        return $this->hasMany(DepartmentStaff::class, 'staff_role_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'oryxpace_permission_staff_roles', 'staff_role_id', 'permission_id');
    }
}
