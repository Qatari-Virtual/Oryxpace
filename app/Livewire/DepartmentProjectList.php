<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Project;
use Livewire\Component;

class DepartmentProjectList extends Component
{
    public $showArchived = false;
    public $showNewDepartmentModal = false;
    public $newDepartmentName = '';
    public $newDepartmentIcon = '';
    public $newIconSearch = '';

    public $showEditModal = false;
    public $editingDepartmentId = null;
    public $editingDepartmentName = '';
    public $editingDepartmentIcon = '';
    public $editIconSearch = '';

    public $showNewProjectModal = false;
    public $newProjectName = '';
    public $newProjectDepartmentId = null;
    public $newProjectStartDate = null;
    public $newProjectCompletionDate = null;
    public $newProjectDescription = '';

    public function openNewDepartmentModal()
    {
        $this->reset(['newDepartmentName', 'newDepartmentIcon', 'newIconSearch']);
        $this->resetErrorBag(['newDepartmentName', 'newDepartmentIcon']);
        $this->showNewDepartmentModal = true;
    }

    public function closeNewDepartmentModal()
    {
        $this->showNewDepartmentModal = false;
    }

    public function createDepartment()
    {
        $this->validate([
            'newDepartmentName' => 'required|string|max:255',
            'newDepartmentIcon' => 'nullable|string|max:255',
        ]);

        Department::create([
            'name' => $this->newDepartmentName,
            'slug' => \Illuminate\Support\Str::slug($this->newDepartmentName),
            'icon' => $this->newDepartmentIcon ?: null,
        ]);

        $this->reset(['newDepartmentName', 'newDepartmentIcon', 'newIconSearch']);
        $this->showNewDepartmentModal = false;
        session()->flash('message', 'Department created successfully.');
    }

    public function openEditDepartment(int $departmentId)
    {
        $dept = Department::findOrFail($departmentId);
        $this->editingDepartmentId = $dept->id;
        $this->editingDepartmentName = $dept->name;
        $this->editingDepartmentIcon = $dept->icon ?? '';
        $this->editIconSearch = '';
        $this->resetErrorBag(['editingDepartmentName', 'editingDepartmentIcon']);
        $this->showEditModal = true;
    }

    public function closeEditDepartment()
    {
        $this->showEditModal = false;
        $this->reset(['editingDepartmentId', 'editingDepartmentName', 'editingDepartmentIcon', 'editIconSearch']);
        $this->resetErrorBag(['editingDepartmentName', 'editingDepartmentIcon']);
    }

    public function updateDepartment()
    {
        $this->validate([
            'editingDepartmentName' => 'required|string|max:255',
            'editingDepartmentIcon' => 'nullable|string|max:255',
        ]);

        $dept = Department::findOrFail($this->editingDepartmentId);
        $dept->update([
            'name' => $this->editingDepartmentName,
            'slug' => \Illuminate\Support\Str::slug($this->editingDepartmentName),
            'icon' => $this->editingDepartmentIcon ?: null,
        ]);

        $this->reset(['editingDepartmentId', 'editingDepartmentName', 'editingDepartmentIcon', 'editIconSearch']);
        $this->showEditModal = false;
        session()->flash('message', 'Department updated successfully.');
    }

    public function removeIcon(int $departmentId)
    {
        Department::where('id', $departmentId)->update(['icon' => null]);
        if ($this->editingDepartmentId === $departmentId) {
            $this->editingDepartmentIcon = '';
        }
        session()->flash('message', 'Department icon removed.');
    }

    public function openNewProjectModal(?int $departmentId = null)
    {
        $this->reset(['newProjectName', 'newProjectDepartmentId', 'newProjectStartDate', 'newProjectCompletionDate', 'newProjectDescription']);
        $this->resetErrorBag(['newProjectName', 'newProjectDepartmentId', 'newProjectCompletionDate']);
        $this->newProjectDepartmentId = $departmentId;
        $this->showNewProjectModal = true;
    }

    public function closeNewProjectModal()
    {
        $this->showNewProjectModal = false;
    }

    public function createProject()
    {
        $this->validate([
            'newProjectName' => 'required|string|max:255',
            'newProjectDepartmentId' => 'required|exists:oryxpace_departments,id',
            'newProjectStartDate' => 'nullable|date',
            'newProjectCompletionDate' => 'nullable|date|after_or_equal:newProjectStartDate',
        ]);

        $project = Project::create([
            'department_id' => $this->newProjectDepartmentId,
            'name' => $this->newProjectName,
            'description' => $this->newProjectDescription,
            'start_date' => $this->newProjectStartDate,
            'completion_date' => $this->newProjectCompletionDate,
        ]);

        $defaultColumns = ['To Do', 'In Progress', 'Done'];
        foreach ($defaultColumns as $index => $colName) {
            $project->kanbanColumns()->create([
                'name' => $colName,
                'slug' => \Illuminate\Support\Str::slug($colName),
                'order' => $index,
                'is_default' => ($index === 0),
            ]);
        }

        $this->reset(['newProjectName', 'newProjectDepartmentId', 'newProjectStartDate', 'newProjectCompletionDate', 'newProjectDescription']);
        $this->showNewProjectModal = false;
        session()->flash('message', 'Project created successfully.');
    }

    public function toggleArchive(int $projectId)
    {
        $project = Project::findOrFail($projectId);
        $project->update(['is_archived' => !$project->is_archived]);
        session()->flash('message', 'Project archive status updated.');
    }

    public function render()
    {
        $user = auth()->user();
        $isStaff = $user?->isStaff() ?? false;

        $query = Department::with(['projects' => function ($query) {
            if (!$this->showArchived) {
                $query->where('is_archived', false);
            }
        }]);

        if (!$isStaff) {
            $accessibleDeptIds = \App\Models\PermissionAccess::whereIn('permission_id', function ($sub) use ($user) {
                $sub->select('permission_id')
                    ->from('oryxpace_pilot_permissions')
                    ->where('pilot_id', $user?->id);
            })->pluck('department_id')->filter()->unique();

            $query->whereIn('id', $accessibleDeptIds);
        }

        $departments = $query->get();

        $allIcons = Department::getAvailableIcons();

        $newIcons = $this->newIconSearch !== ''
            ? array_filter($allIcons, fn ($label, $class) => str_contains(strtolower($label), strtolower($this->newIconSearch)) || str_contains(strtolower($class), strtolower($this->newIconSearch)), ARRAY_FILTER_USE_BOTH)
            : $allIcons;

        $editIcons = $this->editIconSearch !== ''
            ? array_filter($allIcons, fn ($label, $class) => str_contains(strtolower($label), strtolower($this->editIconSearch)) || str_contains(strtolower($class), strtolower($this->editIconSearch)), ARRAY_FILTER_USE_BOTH)
            : $allIcons;

        return view('livewire.department-project-list', [
            'departments' => $departments,
            'newIcons' => $newIcons,
            'editIcons' => $editIcons,
        ]);
    }
}
