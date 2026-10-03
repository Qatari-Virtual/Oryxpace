<?php

namespace App\Livewire;

use App\Models\NotificationPreference;
use App\Models\Pilot;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class NotificationSettings extends Component
{
    public $discordEnabled = true;

    public $emailEnabled = true;

    public $discordUserId = '';

    public $subscribedProjectIds = [];

    public function mount()
    {
        $pilot = auth()->user();

        if (! $pilot) {
            return;
        }

        $this->discordEnabled = NotificationPreference::isEnabled($pilot->id, NotificationPreference::CHANNEL_DISCORD);
        $this->emailEnabled = NotificationPreference::isEnabled($pilot->id, NotificationPreference::CHANNEL_EMAIL);
        $this->discordUserId = $pilot->discordid ?? '';

        $this->subscribedProjectIds = DB::table('oryxpace_project_subscriptions')
            ->where('pilot_id', $pilot->id)
            ->pluck('project_id')
            ->toArray();
    }

    public function updatedDiscordEnabled($value)
    {
        $pilotId = auth()->id();
        if (! $pilotId) {
            return;
        }

        NotificationPreference::setPreference($pilotId, NotificationPreference::CHANNEL_DISCORD, NotificationPreference::EVENT_ALL, $value);
        session()->flash('message', 'Discord notification preference saved.');
    }

    public function updatedEmailEnabled($value)
    {
        $pilotId = auth()->id();
        if (! $pilotId) {
            return;
        }

        NotificationPreference::setPreference($pilotId, NotificationPreference::CHANNEL_EMAIL, NotificationPreference::EVENT_ALL, $value);
        session()->flash('message', 'Email notification preference saved.');
    }

    public function saveDiscordUserId()
    {
        $pilot = auth()->user();
        if (! $pilot) {
            return;
        }

        $this->validate([
            'discordUserId' => 'nullable|string|max:50',
        ]);

        $trimmed = trim($this->discordUserId);

        Pilot::where('id', $pilot->id)->update(['discordid' => $trimmed ?: null]);

        session()->flash('message', empty($trimmed) ? 'Discord user ID removed.' : 'Discord user ID saved.');
    }

    public function toggleProjectSubscription(int $projectId)
    {
        $pilotId = auth()->id();
        if (! $pilotId) {
            return;
        }

        if (in_array($projectId, $this->subscribedProjectIds)) {
            DB::table('oryxpace_project_subscriptions')
                ->where('pilot_id', $pilotId)
                ->where('project_id', $projectId)
                ->delete();

            $this->subscribedProjectIds = array_values(array_diff($this->subscribedProjectIds, [$projectId]));
            session()->flash('message', 'Unsubscribed from project updates.');
        } else {
            DB::table('oryxpace_project_subscriptions')->insert([
                'pilot_id' => $pilotId,
                'project_id' => $projectId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->subscribedProjectIds[] = $projectId;
            session()->flash('message', 'Subscribed to project updates.');
        }
    }

    public function render()
    {
        $projects = Project::all();

        return view('livewire.notification-settings', [
            'projects' => $projects,
        ]);
    }
}
