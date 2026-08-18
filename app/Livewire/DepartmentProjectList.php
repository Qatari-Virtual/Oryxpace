<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Project;
use Livewire\Component;

class DepartmentProjectList extends Component
{
    public $showArchived = false;
    public $newDepartmentName = '';
    public $newProjectName = '';
    public $newProjectDepartmentId = null;
    public $newProjectStartDate = null;
    public $newProjectCompletionDate = null;
    public $newProjectDescription = '';

    public function createDepartment()
    {
        $this->validate([
            'newDepartmentName' => 'required|string|max:255',
        ]);

        Department::create([
            'name' => $this->newDepartmentName,
            'slug' => \Illuminate\Support\Str::slug($this->newDepartmentName),
        ]);

        $this->newDepartmentName = '';
        session()->flash('message', 'Department created successfully.');
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
