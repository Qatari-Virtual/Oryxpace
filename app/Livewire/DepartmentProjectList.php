<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Project;
use Livewire\Component;

use Livewire\WithFileUploads;

class DepartmentProjectList extends Component
{
    use WithFileUploads;

    public $showArchived = false;
    public $newDepartmentName = '';
    public $newDepartmentIcon = null;

    public $editingDepartmentId = null;
    public $editingDepartmentName = '';
    public $editingDepartmentIcon = null;

    public $newProjectName = '';
    public $newProjectDepartmentId = null;
    public $newProjectStartDate = null;
    public $newProjectCompletionDate = null;
    public $newProjectDescription = '';

    public function createDepartment()
    {
        $this->validate([
            'newDepartmentName' => 'required|string|max:255',
            'newDepartmentIcon' => 'nullable|file|mimes:png,jpg,jpeg,webp,svg,gif,ico|max:5120',
        ]);

        $dept = Department::create([
            'name' => $this->newDepartmentName,
            'slug' => \Illuminate\Support\Str::slug($this->newDepartmentName),
        ]);

        if ($this->newDepartmentIcon) {
            $path = $this->newDepartmentIcon->store('departments', 'public');
            $publicFile = public_path('storage/' . $path);
            @mkdir(dirname($publicFile), 0755, true);
            @copy(storage_path('app/public/' . $path), $publicFile);
            Department::setDepartmentIcon($dept->id, $path);
        }

        $this->reset(['newDepartmentName', 'newDepartmentIcon']);
        session()->flash('message', 'Department created successfully.');
    }

    public function openEditDepartment(int $departmentId)
    {
        $dept = Department::findOrFail($departmentId);
        $this->editingDepartmentId = $dept->id;
        $this->editingDepartmentName = $dept->name;
        $this->editingDepartmentIcon = null;
    }

    public function updateDepartment()
    {
        $this->validate([
            'editingDepartmentName' => 'required|string|max:255',
            'editingDepartmentIcon' => 'nullable|file|mimes:png,jpg,jpeg,webp,svg,gif,ico|max:5120',
        ]);

        $dept = Department::findOrFail($this->editingDepartmentId);
        $dept->update([
            'name' => $this->editingDepartmentName,
            'slug' => \Illuminate\Support\Str::slug($this->editingDepartmentName),
        ]);

        if ($this->editingDepartmentIcon) {
            $path = $this->editingDepartmentIcon->store('departments', 'public');
            $publicFile = public_path('storage/' . $path);
            @mkdir(dirname($publicFile), 0755, true);
            @copy(storage_path('app/public/' . $path), $publicFile);
            Department::setDepartmentIcon($dept->id, $path);
        }

        $this->reset(['editingDepartmentId', 'editingDepartmentName', 'editingDepartmentIcon']);
        session()->flash('message', 'Department updated successfully.');
    }

    public function removeIcon(int $departmentId)
    {
        Department::removeDepartmentIcon($departmentId);
        session()->flash('message', 'Department icon removed.');
    }

    public function createProject()
    {
        $this->validate([
            'newProjectName' => 'required|string|max:255',
            'newProjectDepartmentId' => 'required|exists:departments,id',
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
        $departments = Department::with(['projects' => function ($query) {
            if (!$this->showArchived) {
                $query->where('is_archived', false);
            }
        }])->get();

        return view('livewire.department-project-list', [
            'departments' => $departments,
        ]);
    }
}
