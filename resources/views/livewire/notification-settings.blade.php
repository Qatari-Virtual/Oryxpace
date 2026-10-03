<div class="mx-auto max-w-4xl space-y-6 px-4 py-6 sm:px-6 sm:py-8">
    <x-toast :message="session('message')" />
    <header><h1 class="text-3xl font-extrabold tracking-tight text-brand sm:text-4xl">Notifications</h1></header>

    <section class="surface-card overflow-hidden">
        <div class="border-b border-brand-border px-5 py-4 sm:px-6"><h2 class="font-extrabold text-main">Delivery channels</h2><p class="mt-1 text-xs text-muted">Choose how you want to receive task notifications.</p></div>
        <div class="divide-y divide-brand-border">
            <label class="flex cursor-pointer items-center justify-between gap-4 p-5 sm:px-6"><div class="min-w-0"><span class="block text-sm font-bold text-main">Discord DMs</span><span class="mt-1 block text-xs text-sub">Receive direct messages from the bot for task alerts.</span></div><input type="checkbox" wire:model.live="discordEnabled" class="toggle toggle-primary shrink-0" /></label>
            <label class="flex cursor-pointer items-center justify-between gap-4 p-5 sm:px-6"><div class="min-w-0"><span class="block text-sm font-bold text-main">Email summaries</span><span class="mt-1 block text-xs text-sub">Periodic summaries for assigned tasks and updates.</span></div><input type="checkbox" wire:model.live="emailEnabled" class="toggle toggle-primary shrink-0" /></label>
        </div>
    </section>

    <section class="surface-card overflow-hidden">
        <div class="border-b border-brand-border px-5 py-4 sm:px-6"><h2 class="font-extrabold text-main">Discord connection</h2><p class="mt-1 text-xs text-muted">Link your Discord account so the bot can send you DMs.</p></div>
        <div class="p-5 sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="discordUserId" class="mb-1 block text-sm font-bold text-main">Discord User ID</label>
                    <input type="text" id="discordUserId" wire:model="discordUserId" placeholder="e.g. 123456789012345678" class="input input-bordered w-full" />
                    <p class="mt-1 text-xs text-muted">Enable Developer Mode in Discord, then right-click your name and copy ID.</p>
                </div>
                <button wire:click="saveDiscordUserId" class="btn btn-primary w-full sm:w-auto">Save</button>
            </div>
        </div>
    </section>

    <section class="surface-card overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-brand-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"><div><h2 class="font-extrabold text-main">Project subscriptions</h2><p class="mt-1 text-xs text-muted">Receive every update from selected projects.</p></div><span class="badge badge-primary">{{ count($subscribedProjectIds) }} subscribed</span></div>
        <div class="divide-y divide-brand-border">
            @forelse($projects as $p)
                @php($subscribed = in_array($p->id, $subscribedProjectIds))
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"><div class="min-w-0"><h3 class="truncate text-sm font-bold text-main">{{ $p->name }}</h3><p class="mt-1 truncate text-xs text-muted">{{ $p->department->name ?? 'No department' }}</p></div><button wire:click="toggleProjectSubscription({{ $p->id }})" class="btn btn-sm {{ $subscribed ? 'btn-success' : 'btn-outline' }} w-full sm:w-auto">{{ $subscribed ? 'Subscribed ✓' : '+ Subscribe' }}</button></div>
            @empty
                <div class="empty-state py-14"><span class="text-4xl">◌</span><h3>No projects available</h3><p>Projects will appear here when they are created.</p></div>
            @endforelse
        </div>
    </section>
</div>
