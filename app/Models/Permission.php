<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'custom_permissions';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function pilots()
    {
        return $this->belongsToMany(Pilot::class, 'pilot_permissions', 'permission_id', 'pilot_id');
    }
}
