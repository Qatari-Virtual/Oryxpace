<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'color',
        'permission',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_tags', 'project_tag_id', 'task_id');
    }
}
