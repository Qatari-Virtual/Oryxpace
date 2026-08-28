<?php

namespace App\Livewire;

use App\Models\Pilot;
use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\Task;
use Livewire\Component;

class ProjectListView extends Component
{
    public Project $project;
    public $search = '';
    public $filterTagId = null;
    public $filterAssigneeId = null;
    public $filterResolutionUserId = null;
    public $sortBy = 'created_at';
    public $sortDir = 'desc';

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function openTaskDetail(int $taskId)
    {
        $this->dispatch('open-task-modal', taskId: $taskId);
    }

    public function render()
    {
        $query = Task::where('project_id', $this->project->id)
            ->whereNull('parent_id')
            ->withStandardRelations();

        if ($this->search) {
            $query->where('title', 'like', "%{$this->search}%");
        }

        if ($this->filterTagId) {
            $query->whereHas('tags', function ($q) {
                $q->where('project_tags.id', $this->filterTagId);
            });
        }

        if ($this->filterAssigneeId) {
            $query->whereHas('assignees', function ($q) {
                $q->where('pilots.id', $this->filterAssigneeId);
            });
        }

        if ($this->filterResolutionUserId) {
            $query->where('resolution_user_id', $this->filterResolutionUserId);
        }

        $tasks = $query->orderBy($this->sortBy, $this->sortDir)->get();
        $tags = ProjectTag::where('project_id', $this->project->id)->get();
        $pilots = Pilot::all();

        return view('livewire.project-list-view', [
            'tasks' => $tasks,
            'tags' => $tags,
            'pilots' => $pilots,
        ]);
    }
}
