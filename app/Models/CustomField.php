<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'field_type',
        'options',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function values()
    {
        return $this->hasMany(TaskCustomFieldValue::class);
    }
}
