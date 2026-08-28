<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $table = 'oryxpace_projects';

    protected $fillable = [
        'department_id',
        'name',
        'description',
        'start_date',
        'completion_date',
        'is_archived',
    ];

    protected $casts = [
        'start_date' => 'date',
        'completion_date' => 'date',
        'is_archived' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function kanbanColumns()
    {
        return $this->hasMany(KanbanColumn::class)->orderBy('order');
    }

    public function tags()
    {
        return $this->hasMany(ProjectTag::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function customFields()
    {
        return $this->hasMany(CustomField::class);
    }

    public function subscribers()
    {
        return $this->belongsToMany(Pilot::class, 'oryxpace_project_subscriptions', 'project_id', 'pilot_id');
    }

    public function getProgressPercentageAttribute(): int
    {
        $total = $this->tasks()->whereNull('parent_id')->count();
        if ($total === 0) {
            return 0;
        }

        $completedColumnIds = $this->kanbanColumns()->where('slug', 'completed')->orWhere('name', 'like', '%done%')->pluck('id');
        $completed = $this->tasks()->whereNull('parent_id')->whereIn('kanban_column_id', $completedColumnIds)->count();

        return (int) round(($completed / $total) * 100);
    }
}
