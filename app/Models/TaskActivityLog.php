<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'pilot_id',
        'activity_type',
        'description',
        'old_value',
        'new_value',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function pilot()
    {
        return $this->belongsTo(Pilot::class, 'pilot_id');
    }
}
