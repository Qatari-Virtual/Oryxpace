<?php

namespace App\Livewire;

use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class NotificationSettings extends Component
{
    public $discordEnabled = true;
    public $emailEnabled = true;
    public $subscribedProjectIds = [];

    public function mount()
    {
        $pilotId = auth()->id() ?? 1;

        $subscribed = DB::table('oryxpace_project_subscriptions')
            ->where('pilot_id', $pilotId)
            ->pluck('project_id')
            ->toArray();

        $this->subscribedProjectIds = $subscribed;
    }

    public function toggleProjectSubscription(int $projectId)
    {
        $pilotId = auth()->id() ?? 1;

        if (in_array($projectId, $this->subscribedProjectIds)) {
            DB::table('oryxpace_project_subscriptions')
                ->where('pilot_id', $pilotId)
                ->where('project_id', $projectId)
                ->delete();

            $this->subscribedProjectIds = array_diff($this->subscribedProjectIds, [$projectId]);
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
