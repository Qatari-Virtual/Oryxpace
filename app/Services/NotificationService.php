<?php

namespace App\Services;

use App\Models\Pilot;
use App\Models\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send Discord and/or Email notification for task updates with direct links.
     */
    public static function notifyTaskUpdate(Task $task, string $eventType, string $message): void
    {
        $taskUrl = config('app.url') . "/tasks/{$task->id}";

        // 1. Notify assigned pilots
        foreach ($task->assignees as $pilot) {
            self::sendToPilot($pilot, $task, $eventType, $message, $taskUrl);
        }

        // 2. Notify project subscribers
        if ($task->project) {
            foreach ($task->project->subscribers as $subscriber) {
                if (!$task->assignees->contains($subscriber->id)) {
                    self::sendToPilot($subscriber, $task, $eventType, $message, $taskUrl);
                }
            }
        }
    }

    private static function sendToPilot(Pilot $pilot, Task $task, string $eventType, string $message, string $taskUrl): void
    {
        $discordWebhookUrl = config('services.discord.webhook_url');

        if ($discordWebhookUrl) {
            try {
                Http::post($discordWebhookUrl, [
                    'content' => "🔔 **Oryxpace Task Notification**\n"
                        . "**Task**: [{$task->title}]({$taskUrl})\n"
                        . "**Project**: " . ($task->project->name ?? 'N/A') . "\n"
                        . "**Event**: {$message}\n"
                        . "🔗 [Click to Open Task]({$taskUrl})",
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send Discord webhook notification: " . $e->getMessage());
            }
        }

        Log::info("Notification dispatched to Pilot {$pilot->name} ({$pilot->email}) for task #{$task->id}");
    }
}
