<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $table = 'oryxpace_departments';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
