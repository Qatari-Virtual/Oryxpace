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

    public function accessEntries()
    {
        return $this->hasMany(\App\Models\PermissionAccess::class, 'department_id');
    }

    public function teamMembers()
    {
        $permissionIds = $this->accessEntries()
            ->whereNull('project_id')
            ->pluck('permission_id');

        $teamPermissionIds = \App\Models\Permission::whereIn('id', $permissionIds)
            ->whereHas('staffRoles')
            ->pluck('id');

        $pilotIds = \Illuminate\Support\Facades\DB::table('oryxpace_pilot_permissions')
            ->whereIn('permission_id', $teamPermissionIds)
            ->pluck('pilot_id');

        return Pilot::whereIn('id', $pilotIds)->with('staffRoles');
    }

    public function getTeamMembers()
    {
        $permissionIds = $this->accessEntries()
            ->whereNull('project_id')
            ->pluck('permission_id');

        $teamPermissions = \App\Models\Permission::whereIn('id', $permissionIds)
            ->whereHas('roleTypes')
            ->with('roleTypes')
            ->get();

        if ($teamPermissions->isEmpty()) {
            return collect();
        }

        $roleNameLevelPairs = $teamPermissions->flatMap(fn ($p) => $p->roleTypes)
            ->unique(fn ($rt) => trim($rt->role_name) . '|' . trim($rt->level))
            ->map(fn ($rt) => [trim($rt->role_name), trim($rt->level)])
            ->all();

        $matchingStaffRoles = \App\Models\StaffRole::where(function ($query) use ($roleNameLevelPairs) {
            foreach ($roleNameLevelPairs as $pair) {
                $query->orWhere(function ($q) use ($pair) {
                    $q->whereRaw('TRIM(role_name) = ?', [$pair[0]])
                      ->whereRaw('TRIM(level) = ?', [$pair[1]]);
                });
            }
        })->get();

        $pilotIds = $matchingStaffRoles->pluck('user_id')->unique();
        $pilots = Pilot::whereIn('id', $pilotIds)->get()->keyBy('id');

        $result = [];
        foreach ($matchingStaffRoles as $sr) {
            $pilot = $pilots->get($sr->user_id);
            if (!$pilot) continue;

            $roleName = trim($sr->role_name);
            $level = trim($sr->level);

            if (!isset($result[$pilot->id])) {
                $result[$pilot->id] = [
                    'pilot' => $pilot,
                    'roles' => collect(),
                    'highest_level' => $level,
                ];
            }

            if (!$result[$pilot->id]['roles']->contains('role_name', $roleName)) {
                $result[$pilot->id]['roles']->push((object)[
                    'role_name' => $roleName,
                    'level' => $level,
                ]);
            }

            $levelPriority = ['Executive' => 1, 'Manager' => 2, 'Staff' => 3];
            $currentPriority = $levelPriority[$result[$pilot->id]['highest_level']] ?? 99;
            $newPriority = $levelPriority[$level] ?? 99;
            if ($newPriority < $currentPriority) {
                $result[$pilot->id]['highest_level'] = $level;
            }
        }

        $levelPriority = ['Executive' => 1, 'Manager' => 2, 'Staff' => 3];

        return collect($result)
            ->sortBy(fn ($m) => [
                $levelPriority[$m['highest_level']] ?? 99,
                $m['highest_level'] === 'Executive' && str_contains(strtolower($m['roles']->first()?->role_name ?? ''), 'chief executive') ? 0 : 1,
                strtolower($m['pilot']->name ?? ''),
            ])
            ->values();
    }

    public static function getAvailableIcons(): array
    {
        return [
            'ri-dashboard-fill' => 'Dashboard',
            'ri-settings-3-fill' => 'Settings',
            'ri-settings-4-fill' => 'Config',
            'ri-tools-fill' => 'Tools',
            'ri-code-s-slash-fill' => 'Code',
            'ri-bug-fill' => 'Bug',
            'ri-lightbulb-fill' => 'Idea',
            'ri-lightbulb-flash-fill' => 'Innovation',
            'ri-box-3-fill' => 'Box',
            'ri-palette-fill' => 'Palette',
            'ri-paint-brush-fill' => 'Brush',
            'ri-megaphone-fill' => 'Megaphone',
            'ri-share-fill' => 'Share',
            'ri-hand-coin-fill' => 'Sales',
            'ri-money-dollar-circle-fill' => 'Dollar',
            'ri-money-euro-circle-fill' => 'Euro',
            'ri-user-heart-fill' => 'Heart',
            'ri-team-fill' => 'Team',
            'ri-user-star-fill' => 'Star User',
            'ri-user-voice-fill' => 'Voice',
            'ri-funds-fill' => 'Funds',
            'ri-bank-card-fill' => 'Bank Card',
            'ri-customer-service-2-fill' => 'Support',
            'ri-headphone-fill' => 'Headphone',
            'ri-computer-fill' => 'Computer',
            'ri-server-fill' => 'Server',
            'ri-database-fill' => 'Database',
            'ri-cloud-fill' => 'Cloud',
            'ri-scales-3-fill' => 'Legal',
            'ri-file-shield-2-fill' => 'Shield File',
            'ri-shield-fill' => 'Shield',
            'ri-admin-fill' => 'Admin',
            'ri-flight-land-fill' => 'Flight Land',
            'ri-flight-takeoff-fill' => 'Flight Takeoff',
            'ri-rocket-fill' => 'Rocket',
            'ri-rocket-2-fill' => 'Rocket 2',
            'ri-star-fill' => 'Star',
            'ri-star-s-fill' => 'Star S',
            'ri-trophy-fill' => 'Trophy',
            'ri-flag-fill' => 'Flag',
            'ri-flag-2-fill' => 'Flag 2',
            'ri-bookmark-fill' => 'Bookmark',
            'ri-bookmark-2-fill' => 'Bookmark 2',
            'ri-calendar-fill' => 'Calendar',
            'ri-calendar-event-fill' => 'Event',
            'ri-mail-fill' => 'Mail',
            'ri-mail-send-fill' => 'Mail Send',
            'ri-phone-fill' => 'Phone',
            'ri-map-pin-fill' => 'Location',
            'ri-map-pin-2-fill' => 'Location 2',
            'ri-building-fill' => 'Building',
            'ri-building-2-fill' => 'Building 2',
            'ri-building-4-fill' => 'Office',
            'ri-store-2-fill' => 'Store',
            'ri-store-3-fill' => 'Shop',
            'ri-shopping-bag-fill' => 'Shopping Bag',
            'ri-shopping-cart-fill' => 'Cart',
            'ri-truck-fill' => 'Truck',
            'ri-leaf-fill' => 'Leaf',
            'ri-tree-fill' => 'Tree',
            'ri-fire-fill' => 'Fire',
            'ri-flashlight-fill' => 'Flashlight',
            'ri-sun-fill' => 'Sun',
            'ri-moon-fill' => 'Moon',
            'ri-cloudy-fill' => 'Cloudy',
            'ri-umbrella-fill' => 'Umbrella',
            'ri-cup-fill' => 'Cup',
            'ri-gift-fill' => 'Gift',
            'ri-gift-2-fill' => 'Gift 2',
            'ri-heart-fill' => 'Heart',
            'ri-heart-2-fill' => 'Heart 2',
            'ri-heart-3-fill' => 'Heart 3',
            'ri-thumb-up-fill' => 'Thumb Up',
            'ri-medal-fill' => 'Medal',
            'ri-medal-2-fill' => 'Medal 2',
            'ri-vip-crown-fill' => 'Crown',
            'ri-vip-diamond-fill' => 'Diamond',
            'ri-alarm-fill' => 'Alarm',
            'ri-bell-fill' => 'Bell',
            'ri-notification-fill' => 'Notification',
            'ri-notification-2-fill' => 'Notification 2',
            'ri-chat-1-fill' => 'Chat',
            'ri-chat-3-fill' => 'Chat 3',
            'ri-message-2-fill' => 'Message',
            'ri-message-3-fill' => 'Message 3',
            'ri-file-text-fill' => 'File Text',
            'ri-file-list-fill' => 'File List',
            'ri-file-chart-fill' => 'File Chart',
            'ri-file-copy-fill' => 'File Copy',
            'ri-folder-fill' => 'Folder',
            'ri-folder-2-fill' => 'Folder 2',
            'ri-folder-user-fill' => 'Folder User',
            'ri-key-fill' => 'Key',
            'ri-key-2-fill' => 'Key 2',
            'ri-lock-fill' => 'Lock',
            'ri-lock-2-fill' => 'Lock 2',
            'ri-eye-fill' => 'Eye',
            'ri-eye-off-fill' => 'Eye Off',
            'ri-search-fill' => 'Search',
            'ri-filter-fill' => 'Filter',
            'ri-sort-asc' => 'Sort',
            'ri-bar-chart-fill' => 'Bar Chart',
            'ri-bar-chart-2-fill' => 'Bar Chart 2',
            'ri-pie-chart-fill' => 'Pie Chart',
            'ri-line-chart-fill' => 'Line Chart',
            'ri-donut-chart-fill' => 'Donut Chart',
            'ri-global-fill' => 'Global',
            'ri-earth-fill' => 'Earth',
            'ri-compass-fill' => 'Compass',
            'ri-compass-2-fill' => 'Compass 2',
            'ri-navigation-fill' => 'Navigation',
            'ri-anchor-fill' => 'Anchor',
            'ri-ship-fill' => 'Ship',
            'ri-car-fill' => 'Car',
            'ri-bus-fill' => 'Bus',
            'ri-bike-fill' => 'Bike',
            'ri-subway-fill' => 'Subway',
            'ri-train-fill' => 'Train',
            'ri-home-fill' => 'Home',
            'ri-home-2-fill' => 'Home 2',
            'ri-home-3-fill' => 'Home 3',
            'ri-home-4-fill' => 'Home 4',
            'ri-hotel-fill' => 'Hotel',
            'ri-hospital-fill' => 'Hospital',
            'ri-first-aid-kit-fill' => 'First Aid',
            'ri-capsule-fill' => 'Capsule',
            'ri-stethoscope-fill' => 'Stethoscope',
            'ri-microscope-fill' => 'Microscope',
            'ri-test-tube-fill' => 'Test Tube',
            'ri-flask-fill' => 'Flask',
            'ri-beaker-fill' => 'Beaker',
            'ri-graduation-cap-fill' => 'Graduation',
            'ri-book-fill' => 'Book',
            'ri-book-2-fill' => 'Book 2',
            'ri-book-open-fill' => 'Book Open',
            'ri-newspaper-fill' => 'Newspaper',
            'ri-article-fill' => 'Article',
            'ri-edit-fill' => 'Edit',
            'ri-edit-2-fill' => 'Edit 2',
            'ri-quill-pen-fill' => 'Quill Pen',
            'ri-pencil-fill' => 'Pencil',
            'ri-pencil-ruler-fill' => 'Pencil Ruler',
            'ri-ruler-fill' => 'Ruler',
            'ri-scissors-fill' => 'Scissors',
            'ri-printer-fill' => 'Printer',
            'ri-camera-fill' => 'Camera',
            'ri-camera-2-fill' => 'Camera 2',
            'ri-video-fill' => 'Video',
            'ri-film-fill' => 'Film',
            'ri-movie-fill' => 'Movie',
            'ri-music-fill' => 'Music',
            'ri-music-2-fill' => 'Music 2',
            'ri-headphone-2-fill' => 'Headphone 2',
            'ri-mic-fill' => 'Mic',
            'ri-volume-up-fill' => 'Volume',
            'ri-play-fill' => 'Play',
            'ri-pause-fill' => 'Pause',
            'ri-stop-fill' => 'Stop',
            'ri-skip-forward-fill' => 'Skip Forward',
            'ri-skip-back-fill' => 'Skip Back',
            'ri-refresh-fill' => 'Refresh',
            'ri-restart-fill' => 'Restart',
            'ri-loader-fill' => 'Loader',
            'ri-loader-2-fill' => 'Loader 2',
            'ri-time-fill' => 'Time',
            'ri-timer-fill' => 'Timer',
            'ri-timer-flash-fill' => 'Timer Flash',
            'ri-hourglass-fill' => 'Hourglass',
            'ri-24-hours-fill' => '24 Hours',
        ];
    }
}
