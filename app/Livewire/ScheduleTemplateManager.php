<?php

namespace App\Livewire;

use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskSchedule;
use Livewire\Component;

class ScheduleTemplateManager extends Component
{
    public $title = '';
    public $projectId = null;
    public $cronExpression = '0 0 * * *';
    public $subtaskTemplatesText = '';
    public $isActive = true;

    public function createSchedule()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'projectId' => 'required|exists:oryxpace_projects,id',
            'cronExpression' => 'required|string',
        ]);

        $subtasks = array_filter(array_map('trim', explode("\n", $this->subtaskTemplatesText)));

        TaskSchedule::create([
            'title' => $this->title,
            'project_id' => $this->projectId,
            'cron_expression' => $this->cronExpression,
            'subtask_templates' => $subtasks,
            'is_active' => $this->isActive,
        ]);

        $this->reset(['title', 'projectId', 'cronExpression', 'subtaskTemplatesText']);
        session()->flash('message', 'Task schedule template created.');
    }

    public function runScheduleNow(int $scheduleId)
    {
        $schedule = TaskSchedule::findOrFail($scheduleId);
        $project = $schedule->project;

        $defaultCol = KanbanColumn::where('project_id', $project->id)->where('is_default', true)->first()
            ?? $project->kanbanColumns()->first();

        $mainTask = Task::create([
            'project_id' => $project->id,
            'kanban_column_id' => $defaultCol ? $defaultCol->id : null,
            'title' => "[Scheduled] " . $schedule->title . " (" . now()->format('Y-m-d') . ")",
            'description' => "Automated scheduled task generated from template: {$schedule->title}",
        ]);

        if (!empty($schedule->subtask_templates)) {
            foreach ($schedule->subtask_templates as $subTitle) {
                Task::create([
                    'project_id' => $project->id,
                    'parent_id' => $mainTask->id,
                    'kanban_column_id' => $defaultCol ? $defaultCol->id : null,
                    'title' => $subTitle,
                ]);
            }
        }

        $schedule->update(['last_run_at' => now()]);
        $mainTask->logActivity(null, 'schedule_run', "Task generated automatically from schedule template.");

        session()->flash('message', "Task '{$mainTask->title}' created with sub-tasks!");
    }

    public function toggleSchedule(int $scheduleId)
    {
        $schedule = TaskSchedule::findOrFail($scheduleId);
        $schedule->update(['is_active' => !$schedule->is_active]);
        session()->flash('message', 'Schedule active status updated.');
    }

    public function render()
    {
        $schedules = TaskSchedule::with('project')->get();
        $projects = Project::all();

        return view('livewire.schedule-template-manager', [
            'schedules' => $schedules,
            'projects' => $projects,
        ]);
    }
}
