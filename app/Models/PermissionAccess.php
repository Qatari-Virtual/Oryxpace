<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionAccess extends Model
{
    protected $table = 'oryxpace_permission_access';

    protected $fillable = [
        'permission_id',
        'department_id',
        'project_id',
    ];

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function isDepartmentLevel(): bool
    {
        return $this->department_id !== null && $this->project_id === null;
    }

    public function isProjectLevel(): bool
    {
        return $this->project_id !== null;
    }

    public function getScopeLabel(): string
    {
        if ($this->isProjectLevel()) {
            return 'Project: ' . ($this->project?->name ?? 'Unknown');
        }
        if ($this->isDepartmentLevel()) {
            return 'Department: ' . ($this->department?->name ?? 'Unknown') . ' (all projects)';
        }
        return 'Unknown scope';
    }
}
