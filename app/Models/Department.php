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
        'icon',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function getIconUrlAttribute(): ?string
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'icon')) {
            $icon = $this->getAttribute('icon');
            if (!empty($icon)) {
                if (str_starts_with($icon, 'http://') || str_starts_with($icon, 'https://') || str_starts_with($icon, '/')) {
                    return $icon;
                }
                return asset('storage/' . $icon);
            }
            return null;
        }

        $map = self::getIconStorageMap();
        $icon = $map[$this->id] ?? null;

        if (empty($icon)) {
            return null;
        }

        if (str_starts_with($icon, 'http://') || str_starts_with($icon, 'https://') || str_starts_with($icon, '/')) {
            return $icon;
        }

        return asset('storage/' . $icon);
    }

    public static function setDepartmentIcon(int $departmentId, string $iconPath): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('oryxpace_departments', 'icon')) {
            try {
                $dept = self::find($departmentId);
                if ($dept) {
                    $dept->icon = $iconPath;
                    $dept->save();
                }
            } catch (\Throwable $e) {
                // Ignore DB error if schema alter is restricted
            }
        }

        $map = self::getIconStorageMap();
        $map[$departmentId] = $iconPath;
        self::saveIconStorageMap($map);
    }

    public static function removeDepartmentIcon(int $departmentId): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('oryxpace_departments', 'icon')) {
            try {
                $dept = self::find($departmentId);
                if ($dept) {
                    $dept->icon = null;
                    $dept->save();
                }
            } catch (\Throwable $e) {
                // Ignore DB error if schema alter is restricted
            }
        }

        $map = self::getIconStorageMap();
        unset($map[$departmentId]);
        self::saveIconStorageMap($map);
    }

    public static function getIconStorageMap(): array
    {
        if (\Illuminate\Support\Facades\Storage::disk('local')->exists('department_icons.json')) {
            return json_decode(\Illuminate\Support\Facades\Storage::disk('local')->get('department_icons.json'), true) ?? [];
        }
        return [];
    }

    public static function saveIconStorageMap(array $map): void
    {
        \Illuminate\Support\Facades\Storage::disk('local')->put('department_icons.json', json_encode($map, JSON_PRETTY_PRINT));
    }
}
