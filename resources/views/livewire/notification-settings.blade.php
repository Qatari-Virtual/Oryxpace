<div class="p-6 space-y-6 max-w-4xl mx-auto">
    @if (session()->has('message'))
        <div class="alert alert-success shadow-sm">
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-base-200 p-4 rounded-xl shadow-sm">
        <h1 class="text-2xl font-bold">Notification & Subscription Preferences</h1>
        <p class="text-xs opacity-70 mt-1">Manage channels for task reminders and subscribe to specific projects to receive all updates.</p>
    </div>

    <!-- Notification Channels Toggles -->
    <div class="card bg-base-100 p-5 border border-base-200 shadow-md space-y-4">
        <h2 class="card-title text-lg">Notification Channels</h2>
        <div class="space-y-3">
            <label class="flex justify-between items-center cursor-pointer p-3 bg-base-200 rounded-lg">
                <div>
                    <span class="font-bold text-sm block">Discord Webhook / Oryxie Reminders</span>
                    <span class="text-xs opacity-70">Receive instant task alerts and status changes via Discord</span>
                </div>
                <input type="checkbox" wire:model.live="discordEnabled" class="toggle toggle-primary" />
            </label>

            <label class="flex justify-between items-center cursor-pointer p-3 bg-base-200 rounded-lg">
                <div>
                    <span class="font-bold text-sm block">Email Notifications</span>
                    <span class="text-xs opacity-70">Receive email summaries for assigned tasks and updates</span>
                </div>
                <input type="checkbox" wire:model.live="emailEnabled" class="toggle toggle-primary" />
            </label>
        </div>
    </div>

    <!-- Project Subscriptions Card -->
    <div class="card bg-base-100 p-5 border border-base-200 shadow-md space-y-4">
        <h2 class="card-title text-lg">Project Subscriptions</h2>
        <p class="text-xs opacity-70">By default, you receive notifications for tasks assigned to you. Subscribing to a project sends you all updates for every task in that project.</p>

        <div class="space-y-3">
            @foreach($projects as $p)
                <div class="flex justify-between items-center p-3 bg-base-200 rounded-lg border border-base-300">
                    <div>
                        <span class="font-bold text-sm block">{{ $p->name }}</span>
                        <span class="text-xs opacity-60">Department: {{ $p->department->name ?? 'N/A' }}</span>
                    </div>
                    <button wire:click="toggleProjectSubscription({{ $p->id }})" class="btn btn-sm {{ in_array($p->id, $subscribedProjectIds) ? 'btn-success' : 'btn-outline' }}">
                        {{ in_array($p->id, $subscribedProjectIds) ? 'Subscribed ✓' : '+ Subscribe' }}
                    </button>
                </div>
            @endforeach
        </div>
    </div>
</div>
