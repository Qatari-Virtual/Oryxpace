<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'project_id',
        'cron_expression',
        'subtask_templates',
        'custom_fields_template',
        'last_run_at',
        'is_active',
    ];

    protected $casts = [
        'subtask_templates' => 'array',
        'custom_fields_template' => 'array',
        'last_run_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
