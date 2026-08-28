<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $table = 'oryxpace_tasks';

    protected $fillable = [
        'project_id',
        'parent_id',
        'kanban_column_id',
        'title',
        'description',
        'resolution_user_id',
        'created_by_pilot_id',
        'due_date',
        'order_in_column',
        'is_template',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_template' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    /**
     * Immediate subtasks
     */
    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('order_in_column');
    }

    /**
     * Recursive subtasks with pre-configured eager loading to avoid N+1 queries.
     */
    public function subtasksRecursive()
    {
        return $this->subtasks()->with([
            'assignees',
            'tags',
            'kanbanColumn',
            'resolutionUser',
            'subtasksRecursive'
        ]);
    }

    public function kanbanColumn()
    {
        return $this->belongsTo(KanbanColumn::class, 'kanban_column_id');
    }

    public function assignees()
    {
        return $this->belongsToMany(Pilot::class, 'oryxpace_task_assignees', 'task_id', 'pilot_id');
    }

    public function resolutionUser()
    {
        return $this->belongsTo(Pilot::class, 'resolution_user_id');
    }

    public function createdByPilot()
    {
        return $this->belongsTo(Pilot::class, 'created_by_pilot_id');
    }

    public function tags()
    {
        return $this->belongsToMany(ProjectTag::class, 'oryxpace_task_tags', 'task_id', 'project_tag_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(TaskActivityLog::class)->latest();
    }

    public function customFieldValues()
    {
        return $this->hasMany(TaskCustomFieldValue::class);
    }

    /**
     * Scope to load standard relations and prevent N+1 issues when displaying tasks.
     */
    public function scopeWithStandardRelations(Builder $query): Builder
    {
        return $query->with([
            'assignees',
            'tags',
            'kanbanColumn',
            'resolutionUser',
            'createdByPilot',
            'subtasksRecursive',
        ]);
    }

    public function loadStandardRelations(): self
    {
        return $this->load([
            'assignees',
            'tags',
            'kanbanColumn',
            'resolutionUser',
            'createdByPilot',
            'subtasksRecursive',
        ]);
    }

    public function logActivity(?int $pilotId, string $type, string $description, ?string $oldVal = null, ?string $newVal = null): TaskActivityLog
    {
        return $this->activityLogs()->create([
            'pilot_id' => $pilotId,
            'activity_type' => $type,
            'description' => $description,
            'old_value' => $oldVal,
            'new_value' => $newVal,
        ]);
    }
}
