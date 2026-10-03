<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

    public function getAuthPassword(): string
    {
        return (string) $this->password;
    }

    public function isActive(): bool
    {
        return (int) $this->status === 1;
    }

    public function isExecutive(): bool
    {
        return in_array(Str::upper((string) $this->callsign), config('oryxpace.executive_callsigns'), true);
    }

    public function isAdmin(): bool
    {
        return $this->isExecutive() || $this->hasPermission('admin') || $this->hasLegacyPermission('admin');
    }

    public function hasPilotAccess(): bool
    {
        if ($this->isExecutive() || $this->isAdmin()) {
            return true;
        }

        return Schema::hasTable('awards_granted')
            && DB::table('awards_granted')
                ->where('awardid', 9)
                ->where('pilotid', $this->id)
                ->exists();
    }

    private function hasLegacyPermission(string $name): bool
    {
        return Schema::hasTable('permissions')
            && DB::table('permissions')
                ->where('name', $name)
                ->where('userid', $this->id)
                ->exists();
    }

    public function getAvatarUrl(?int $size = 128): ?string
    {
        if (empty($this->ifc)) {
            return null;
        }

        $ifc = trim((string) $this->ifc);

        if (preg_match('~/u/([^/?#]+)~i', $ifc, $matches)) {
            $username = strtolower($matches[1]);
        } else {
            $username = strtolower(trim(basename(rtrim($ifc, '/'))));
        }

        if (empty($username)) {
            return null;
        }

        return route('avatar.proxy', ['username' => $username, 'size' => $size]);
    }

    public function toAuthProfile(): array
    {
        return [
            'id' => $this->id,
            'callsign' => $this->callsign,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'ifc' => $this->ifc,
            'avatar_url' => $this->getAvatarUrl(),
            'is_executive' => $this->isExecutive(),
            'is_admin' => $this->isAdmin(),
            'has_pilot_access' => $this->hasPilotAccess(),
        ];
    }

    public function assignedTasks()
    {
        return $this->belongsToMany(Task::class, 'oryxpace_task_assignees', 'pilot_id', 'task_id');
    }

    public function resolutionTasks()
    {
        return $this->hasMany(Task::class, 'resolution_user_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'oryxpace_pilot_permissions', 'pilot_id', 'permission_id');
    }

    public function staffRoles()
    {
        return $this->hasMany(StaffRole::class, 'user_id');
    }

    public function departmentAssignments()
    {
        return $this->hasMany(DepartmentStaff::class, 'pilot_id');
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_staff', 'pilot_id', 'department_id')
            ->withPivot('staff_role_id')
            ->withTimestamps();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return Schema::hasTable('oryxpace_custom_permissions')
            && Schema::hasTable('oryxpace_pilot_permissions')
            && $this->permissions()->where('slug', $permissionSlug)->exists();
    }
}
