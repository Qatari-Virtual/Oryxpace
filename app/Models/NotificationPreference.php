<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $table = 'oryxpace_user_notifications_settings';

    protected $fillable = [
        'pilot_id',
        'channel',
        'event_type',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public const CHANNEL_DISCORD = 'discord';

    public const CHANNEL_EMAIL = 'email';

    public const EVENT_ALL = 'all';

    public const EVENT_TASK_ASSIGNED = 'task_assigned';

    public const EVENT_TASK_UPDATED = 'task_updated';

    public const EVENT_TASK_MOVED = 'task_moved';

    public static function isEnabled(int $pilotId, string $channel, string $eventType = self::EVENT_ALL): bool
    {
        return self::where('pilot_id', $pilotId)
            ->where('channel', $channel)
            ->whereIn('event_type', [$eventType, self::EVENT_ALL])
            ->where('is_enabled', true)
            ->exists();
    }

    public static function setPreference(int $pilotId, string $channel, string $eventType, bool $enabled): void
    {
        self::updateOrCreate(
            [
                'pilot_id' => $pilotId,
                'channel' => $channel,
                'event_type' => $eventType,
            ],
            ['is_enabled' => $enabled]
        );
    }
}
