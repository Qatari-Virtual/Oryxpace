<?php

namespace App\Livewire;

use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Task;
use Livewire\Component;

class ProjectKanban extends Component
{
    public Project $project;
    public $selectedTaskId = null;
    public $newColumnName = '';
    public $newColumnPermission = '';
    public $editingColumnId = null;
    public $editColumnName = '';
    public $editColumnPermission = '';

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function moveTask(int $taskId, int $targetColumnId, int $newOrder = 0)
    {
        $task = Task::findOrFail($taskId);
        $targetColumn = KanbanColumn::findOrFail($targetColumnId);

        if ($targetColumn->required_permission) {
            $user = auth()->user();
            if (!$user || !$user->hasPermission($targetColumn->required_permission)) {
                session()->flash('error', "Permission denied: Requires '{$targetColumn->required_permission}' permission to move task into {$targetColumn->name}.");
                return;
            }
        }

        $oldColumn = $task->kanbanColumn ? $task->kanbanColumn->name : 'None';
        $task->update([
            'kanban_column_id' => $targetColumn->id,
            'order_in_column' => $newOrder,
        ]);

        $task->logActivity(
            auth()->id(),
            'status_changed',
            "Task moved from '{$oldColumn}' to '{$targetColumn->name}'.",
            $oldColumn,
            $targetColumn->name
        );

        session()->flash('message', "Task moved to {$targetColumn->name}.");
    }

    public function addColumn()
    {
        $this->validate([
            'newColumnName' => 'required|string|max:255',
            'newColumnPermission' => 'nullable|string|max:255',
        ]);

        $maxOrder = $this->project->kanbanColumns()->max('order') ?? 0;

        $this->project->kanbanColumns()->create([
            'name' => $this->newColumnName,
            'slug' => \Illuminate\Support\Str::slug($this->newColumnName),
            'order' => $maxOrder + 1,
            'required_permission' => $this->newColumnPermission ?: null,
        ]);

        $this->reset(['newColumnName', 'newColumnPermission']);
        session()->flash('message', 'Kanban column added.');
    }

    public function updateColumn(int $columnId)
    {
        $col = KanbanColumn::findOrFail($columnId);
        $this->validate([
            'editColumnName' => 'required|string|max:255',
            'editColumnPermission' => 'nullable|string|max:255',
        ]);

        $col->update([
            'name' => $this->editColumnName,
            'slug' => \Illuminate\Support\Str::slug($this->editColumnName),
            'required_permission' => $this->editColumnPermission ?: null,
        ]);

        $this->editingColumnId = null;
        session()->flash('message', 'Column updated successfully.');
    }

    public function openTaskDetail(int $taskId)
    {
        $this->selectedTaskId = $taskId;
        $this->dispatch('open-task-modal', taskId: $taskId);
    }

    public function render()
    {
        $columns = $this->project->kanbanColumns()
            ->with(['tasks' => function ($q) {
                $q->whereNull('parent_id')
                    ->withStandardRelations()
                    ->orderBy('order_in_column');
            }])
            ->get();

        return view('livewire.project-kanban', [
            'columns' => $columns,
        ]);
    }
}
