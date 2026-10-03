<?php

namespace App\Services;

use App\Mail\TaskNotificationMail;
use App\Models\NotificationPreference;
use App\Models\Pilot;
use App\Models\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public static function notifyTaskUpdate(Task $task, string $eventType, string $message): void
    {
        $taskUrl = config('app.url')."/tasks/{$task->id}";

        $recipients = collect();

        foreach ($task->assignees as $pilot) {
            $recipients->put($pilot->id, $pilot);
        }

        if ($task->project) {
            foreach ($task->project->subscribers as $subscriber) {
                $recipients->put($subscriber->id, $subscriber);
            }
        }

        foreach ($recipients as $pilot) {
            self::sendToPilot($pilot, $task, $eventType, $message, $taskUrl);
        }
    }

    private static function sendToPilot(Pilot $pilot, Task $task, string $eventType, string $message, string $taskUrl): void
    {
        if (NotificationPreference::isEnabled($pilot->id, NotificationPreference::CHANNEL_DISCORD, $eventType)) {
            self::sendDiscordDm($pilot, $task, $eventType, $message, $taskUrl);
        }

        if (NotificationPreference::isEnabled($pilot->id, NotificationPreference::CHANNEL_EMAIL, $eventType)) {
            self::sendEmail($pilot, $task, $eventType, $message);
        }
    }

    private static function sendDiscordDm(Pilot $pilot, Task $task, string $eventType, string $message, string $taskUrl): void
    {
        $discordUserId = $pilot->discordid;

        if (! $discordUserId) {
            Log::info("Pilot {$pilot->id} has Discord notifications enabled but no Discord user ID set.");

            return;
        }

        $botEndpoint = config('services.discord.bot_endpoint');

        if (! $botEndpoint) {
            Log::warning('Discord bot endpoint not configured. Set DISCORD_BOT_ENDPOINT.');

            return;
        }

        try {
            Http::timeout(10)->post("{$botEndpoint}/notify", [
                'discord_user_id' => $discordUserId,
                'pilot_id' => $pilot->id,
                'pilot_name' => $pilot->name,
                'task_id' => $task->id,
                'task_title' => $task->title,
                'task_url' => $taskUrl,
                'project_name' => $task->project->name ?? null,
                'event_type' => $eventType,
                'message' => $message,
            ]);

            Log::info("Discord DM dispatched to Pilot {$pilot->id} (Discord: {$discordUserId}) for task #{$task->id}");
        } catch (\Exception $e) {
            Log::error('Failed to send Discord DM via bot: '.$e->getMessage());
        }
    }

    private static function sendEmail(Pilot $pilot, Task $task, string $eventType, string $message): void
    {
        if (empty($pilot->email)) {
            Log::info("Pilot {$pilot->id} has email notifications enabled but no email address.");

            return;
        }

        try {
            Mail::to($pilot->email)->send(new TaskNotificationMail($task, $eventType, $message));
            Log::info("Email dispatched to Pilot {$pilot->id} ({$pilot->email}) for task #{$task->id}");
        } catch (\Exception $e) {
            Log::error('Failed to send email notification: '.$e->getMessage());
        }
    }
}
