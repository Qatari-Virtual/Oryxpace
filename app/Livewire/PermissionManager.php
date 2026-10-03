<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Permission;
use App\Models\PermissionAccess;
use App\Models\Pilot;
use App\Models\Project;
use Livewire\Component;

class PermissionManager extends Component
{
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showAccessModal = false;
    public $showPilotsModal = false;

    public $editingPermissionId = null;
    public $permissionName = '';
    public $permissionSlug = '';
    public $permissionDescription = '';

    public $newAccessDepartmentId = null;
    public $newAccessProjectId = null;
    public $deptSearch = '';
    public $projectSearch = '';

    public $newPilotId = null;
    public $pilotSearch = '';
    public $searchedPilots = null;

    public $showStaffRolesModal = false;
    public $selectedRoleTypes = [];

    public function mount()
    {
        if (!auth()->user()?->isAdmin()) {
            abort(403);
        }
    }

    public function openCreateModal()
    {
        $this->reset(['permissionName', 'permissionSlug', 'permissionDescription']);
        $this->resetErrorBag();
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
    }

    public function createPermission()
    {
        $this->validate([
            'permissionName' => 'required|string|max:255',
            'permissionSlug' => 'required|string|max:255|unique:oryxpace_custom_permissions,slug',
            'permissionDescription' => 'nullable|string|max:255',
        ]);

        Permission::create([
            'name' => $this->permissionName,
            'slug' => $this->permissionSlug,
            'description' => $this->permissionDescription,
        ]);

        $this->reset(['permissionName', 'permissionSlug', 'permissionDescription']);
        $this->showCreateModal = false;
        session()->flash('message', 'Permission created successfully.');
    }

    public function openEditModal(int $permissionId)
    {
        $permission = Permission::findOrFail($permissionId);
        $this->editingPermissionId = $permission->id;
        $this->permissionName = $permission->name;
        $this->permissionSlug = $permission->slug;
        $this->permissionDescription = $permission->description ?? '';
        $this->resetErrorBag();
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->reset(['editingPermissionId', 'permissionName', 'permissionSlug', 'permissionDescription']);
    }

    public function updatePermission()
    {
        $this->validate([
            'permissionName' => 'required|string|max:255',
            'permissionSlug' => 'required|string|max:255|unique:oryxpace_custom_permissions,slug,' . $this->editingPermissionId,
            'permissionDescription' => 'nullable|string|max:255',
        ]);

        $permission = Permission::findOrFail($this->editingPermissionId);
        $permission->update([
            'name' => $this->permissionName,
            'slug' => $this->permissionSlug,
            'description' => $this->permissionDescription,
        ]);

        $this->closeEditModal();
        session()->flash('message', 'Permission updated successfully.');
    }

    public function deletePermission(int $permissionId)
    {
        Permission::findOrFail($permissionId)->delete();
        session()->flash('message', 'Permission deleted successfully.');
    }

    public function openAccessModal(int $permissionId)
    {
        $this->editingPermissionId = $permissionId;
        $this->reset(['newAccessDepartmentId', 'newAccessProjectId', 'deptSearch', 'projectSearch']);
        $this->showAccessModal = true;
    }

    public function closeAccessModal()
    {
        $this->showAccessModal = false;
        $this->reset(['editingPermissionId', 'newAccessDepartmentId', 'newAccessProjectId', 'deptSearch', 'projectSearch']);
    }

    public function addDepartmentAccessById(int $departmentId)
    {
        $existing = PermissionAccess::where('permission_id', $this->editingPermissionId)
            ->where('department_id', $departmentId)
            ->whereNull('project_id')
            ->exists();

        if ($existing) {
            session()->flash('message', 'Department access already exists.');
            return;
        }

        PermissionAccess::create([
            'permission_id' => $this->editingPermissionId,
            'department_id' => $departmentId,
            'project_id' => null,
        ]);

        session()->flash('message', 'Department access added.');
    }

    public function addProjectAccessById(int $projectId)
    {
        $existing = PermissionAccess::where('permission_id', $this->editingPermissionId)
            ->where('project_id', $projectId)
            ->exists();

        if ($existing) {
            session()->flash('message', 'Project access already exists.');
            return;
        }

        PermissionAccess::create([
            'permission_id' => $this->editingPermissionId,
            'department_id' => null,
            'project_id' => $projectId,
        ]);

        session()->flash('message', 'Project access added.');
    }

    public function addDepartmentAccess()
    {
        $this->validate([
            'newAccessDepartmentId' => 'required|exists:oryxpace_departments,id',
        ]);

        $this->addDepartmentAccessById($this->newAccessDepartmentId);
        $this->newAccessDepartmentId = null;
    }

    public function addProjectAccess()
    {
        $this->validate([
            'newAccessProjectId' => 'required|exists:oryxpace_projects,id',
        ]);

        $this->addProjectAccessById($this->newAccessProjectId);
        $this->newAccessProjectId = null;
    }

    public function removeAccess(int $accessId)
    {
        PermissionAccess::findOrFail($accessId)->delete();
        session()->flash('message', 'Access removed.');
    }

    public function openPilotsModal(int $permissionId)
    {
        $this->editingPermissionId = $permissionId;
        $this->reset(['newPilotId', 'pilotSearch', 'searchedPilots']);
        $this->showPilotsModal = true;
    }

    public function closePilotsModal()
    {
        $this->showPilotsModal = false;
        $this->reset(['editingPermissionId', 'newPilotId', 'pilotSearch', 'searchedPilots']);
    }

    public function searchPilots()
    {
        $permission = Permission::with('roleTypes')->findOrFail($this->editingPermissionId);

        if ($permission->isTeamPermission()) {
            $roleNameLevelPairs = $permission->roleTypes->map(fn ($rt) => [trim($rt->role_name), trim($rt->level)])->all();

            $matchingStaffRoles = \App\Models\StaffRole::where(function ($query) use ($roleNameLevelPairs) {
                foreach ($roleNameLevelPairs as $pair) {
                    $query->orWhere(function ($q) use ($pair) {
                        $q->whereRaw('TRIM(role_name) = ?', [$pair[0]])
                          ->whereRaw('TRIM(level) = ?', [$pair[1]]);
                    });
                }
            })->get();

            $pilotIds = $matchingStaffRoles->pluck('user_id')->unique();

            $pilots = Pilot::whereIn('id', $pilotIds)
                ->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->pilotSearch . '%')
                          ->orWhere('callsign', 'like', '%' . $this->pilotSearch . '%');
                })
                ->orderBy('name')
                ->limit(50)
                ->get();

            $this->searchedPilots = $pilots->map(function ($pilot) use ($matchingStaffRoles) {
                $pilot->matchingRoles = $matchingStaffRoles->filter(fn ($sr) => $sr->user_id === $pilot->id);
                return $pilot;
            });
        } else {
            $this->searchedPilots = Pilot::where('name', 'like', '%' . $this->pilotSearch . '%')
                ->orWhere('callsign', 'like', '%' . $this->pilotSearch . '%')
                ->orderBy('name')
                ->limit(50)
                ->get();
        }
    }

    public function addPilot()
    {
        $this->validate([
            'newPilotId' => 'required|exists:pilots,id',
        ]);

        $permission = Permission::findOrFail($this->editingPermissionId);
        $permission->pilots()->attach($this->newPilotId);

        $this->newPilotId = null;
        session()->flash('message', 'Pilot assigned to permission.');
    }

    public function removePilot(int $pilotId)
    {
        $permission = Permission::findOrFail($this->editingPermissionId);
        $permission->pilots()->detach($pilotId);
        session()->flash('message', 'Pilot removed from permission.');
    }

    public function openStaffRolesModal(int $permissionId)
    {
        $this->editingPermissionId = $permissionId;
        $permission = Permission::with('roleTypes')->findOrFail($permissionId);
        $this->selectedRoleTypes = $permission->roleTypes->map(fn ($rt) => $rt->role_name . '|' . $rt->level)->toArray();
        $this->showStaffRolesModal = true;
    }

    public function closeStaffRolesModal()
    {
        $this->showStaffRolesModal = false;
        $this->reset(['editingPermissionId', 'selectedRoleTypes']);
    }

    public function saveStaffRoles()
    {
        $permission = Permission::findOrFail($this->editingPermissionId);
        $permission->roleTypes()->delete();

        foreach ($this->selectedRoleTypes as $roleTypeKey) {
            [$roleName, $level] = explode('|', $roleTypeKey);
            \App\Models\PermissionRoleType::create([
                'permission_id' => $permission->id,
                'role_name' => trim($roleName),
                'level' => trim($level),
            ]);
        }

        session()->flash('message', 'Staff roles updated.');
        $this->closeStaffRolesModal();
    }

    public function render()
    {
        $permissions = Permission::with(['access.department', 'access.project', 'pilots', 'roleTypes'])->get();
        $departments = Department::all();
        $projects = Project::with('department')->get();

        $filteredDepartments = $this->deptSearch !== ''
            ? $departments->filter(fn ($d) => str_contains(strtolower($d->name), strtolower($this->deptSearch)))
            : $departments;

        $filteredProjects = $this->projectSearch !== ''
            ? $projects->filter(fn ($p) => str_contains(strtolower($p->name), strtolower($this->projectSearch)))
            : $projects;

        $pilots = $this->searchedPilots ?? \App\Models\Pilot::orderBy('name')->limit(50)->get();

        $uniqueRoleTypes = \App\Models\StaffRole::selectRaw("TRIM(role_name) as role_name, TRIM(level) as level")
            ->distinct()
            ->orderBy('level')
            ->orderBy('role_name')
            ->get();

        return view('livewire.permission-manager', [
            'permissions' => $permissions,
            'departments' => $filteredDepartments,
            'projects' => $filteredProjects,
            'pilots' => $pilots,
            'uniqueRoleTypes' => $uniqueRoleTypes,
        ]);
    }
}
