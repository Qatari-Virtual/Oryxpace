<?php

namespace App\Livewire;

use App\Models\CustomField;
use App\Models\Pilot;
use App\Models\Task;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskDetailModal extends Component
{
    public ?Task $task = null;
    public $isOpen = false;
    public $newSubtaskTitle = '';
    public $selectedAssignees = [];
    public $selectedResolutionUserId = null;
    public $customFieldValues = [];

    #[On('open-task-modal')]
    public function loadTask(int $taskId)
    {
        $this->task = Task::withStandardRelations()->findOrFail($taskId);
        $this->isOpen = true;
        $this->selectedAssignees = $this->task->assignees->pluck('id')->toArray();
        $this->selectedResolutionUserId = $this->task->resolution_user_id;

        $this->customFieldValues = [];
        foreach ($this->task->customFieldValues as $cfv) {
            $this->customFieldValues[$cfv->custom_field_id] = $cfv->value;
        }
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->task = null;
    }

    public function addSubtask()
    {
        if (!$this->task) return;

        $this->validate([
            'newSubtaskTitle' => 'required|string|max:255',
        ]);

        $subtask = Task::create([
            'project_id' => $this->task->project_id,
            'parent_id' => $this->task->id,
            'kanban_column_id' => $this->task->kanban_column_id,
            'title' => $this->newSubtaskTitle,
        ]);

        $this->task->logActivity(auth()->id(), 'subtask_created', "Added sub-task: '{$subtask->title}'");
        $this->newSubtaskTitle = '';
        $this->task = $this->task->fresh()->loadStandardRelations();
    }

    public function updateAssignees()
    {
        if (!$this->task) return;

        $this->task->assignees()->sync($this->selectedAssignees);
        $this->task->logActivity(auth()->id(), 'assignees_updated', "Updated task assignees.");
        $this->task = $this->task->fresh()->loadStandardRelations();
    }

    public function updateResolutionUser()
    {
        if (!$this->task) return;

        $oldUser = $this->task->resolutionUser ? $this->task->resolutionUser->name : 'None';
        $this->task->update(['resolution_user_id' => $this->selectedResolutionUserId ?: null]);

        $newUser = $this->task->fresh()->resolutionUser ? $this->task->fresh()->resolutionUser->name : 'None';
        $this->task->logActivity(auth()->id(), 'resolution_user_updated', "Resolution user changed from {$oldUser} to {$newUser}.");
        $this->task = $this->task->fresh()->loadStandardRelations();
    }

    public function saveCustomFields()
    {
        if (!$this->task) return;

        foreach ($this->customFieldValues as $fieldId => $val) {
            $this->task->customFieldValues()->updateOrCreate(
                ['custom_field_id' => $fieldId],
                ['value' => $val]
            );
        }

        $this->task->logActivity(auth()->id(), 'custom_fields_updated', "Updated custom fields.");
        session()->flash('modal_message', 'Custom fields saved successfully.');
    }

    public function render()
    {
        $pilots = Pilot::all();
        $customFields = $this->task ? CustomField::where('project_id', $this->task->project_id)->get() : collect();

        return view('livewire.task-detail-modal', [
            'pilots' => $pilots,
            'customFields' => $customFields,
        ]);
    }
}
