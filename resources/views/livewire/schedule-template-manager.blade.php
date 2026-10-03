<div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 sm:py-8">
    <x-toast :message="session('message')" />
    <header class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div><h1 class="text-3xl font-extrabold tracking-tight text-brand sm:text-4xl">Schedule templates</h1></div>
        <button data-modal-open="new-schedule-modal" class="btn btn-primary">+ New template</button>
    </header>

    <section class="grid grid-cols-2 gap-3 sm:grid-cols-3"><div class="stat-card"><span class="stat-label">Templates</span><strong>{{ $schedules->count() }}</strong><span class="stat-meta">Configured automations</span></div><div class="stat-card"><span class="stat-label">Active</span><strong>{{ $schedules->where('is_active', true)->count() }}</strong><span class="stat-meta">Running on schedule</span></div><div class="stat-card col-span-2 sm:col-span-1"><span class="stat-label">Last run</span><strong class="text-lg">{{ optional($schedules->whereNotNull('last_run_at')->sortByDesc('last_run_at')->first())->last_run_at?->diffForHumans() ?? 'Never' }}</strong><span class="stat-meta">Most recent generated task</span></div></section>

    <section class="surface-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-brand-border px-4 py-4 sm:px-6"><div><h2 class="font-extrabold text-main">Configured templates</h2><p class="text-xs text-muted">Run one now or pause it for later.</p></div><span class="badge badge-outline">{{ $schedules->count() }} total</span></div>
        <div class="divide-y divide-brand-border">
            @forelse($schedules as $sched)
                <article class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><h3 class="font-bold text-main">{{ $sched->title }}</h3><span class="badge badge-sm {{ $sched->is_active ? 'badge-success' : 'badge-ghost' }}">{{ $sched->is_active ? 'Active' : 'Paused' }}</span></div><div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-sub"><span>{{ $sched->project->name ?? 'No project' }}</span><code class="rounded bg-base-200 px-2 py-0.5 font-mono">{{ $sched->cron_expression }}</code><span>{{ count($sched->subtask_templates ?? []) }} sub-tasks</span><span class="text-muted">Last run: {{ $sched->last_run_at ? $sched->last_run_at->diffForHumans() : 'Never' }}</span></div></div>
                    <div class="flex w-full gap-2 sm:w-auto"><button wire:click="runScheduleNow({{ $sched->id }})" class="btn btn-primary btn-sm flex-1 sm:flex-none">Run now</button><button wire:click="toggleSchedule({{ $sched->id }})" class="btn btn-outline btn-sm flex-1 sm:flex-none">{{ $sched->is_active ? 'Pause' : 'Activate' }}</button></div>
                </article>
            @empty
                <div class="empty-state py-16"><span class="text-4xl">◷</span><h3>No templates configured</h3><p>Start with a recurring task template for routine work.</p><button data-modal-open="new-schedule-modal" class="btn btn-primary mt-3">Create template</button></div>
            @endforelse
        </div>
    </section>

    <x-modal id="new-schedule-modal" title="New schedule template" max-width="max-w-2xl">
        <form wire:submit.prevent="createSchedule" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><div><label class="field-label">Task template title</label><input type="text" wire:model="title" placeholder="e.g. Weekly content review" class="input input-bordered w-full" required /></div><div><label class="field-label">Target project</label><select wire:model="projectId" class="select select-bordered w-full" required><option value="">Select project</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div></div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><div><label class="field-label">Cron expression</label><input type="text" wire:model="cronExpression" placeholder="0 0 * * *" class="input input-bordered w-full font-mono" required /><p class="mt-1 text-[11px] text-muted">Daily at midnight UTC.</p></div><label class="flex items-center gap-3 self-end rounded-xl border border-brand-border bg-base-200 p-3 text-sm font-bold"><input type="checkbox" wire:model="isActive" class="toggle toggle-primary toggle-sm" /> Active immediately</label></div>
            <div><label class="field-label">Sub-task templates <span class="font-normal normal-case text-muted">(one per line)</span></label><textarea wire:model="subtaskTemplatesText" placeholder="Design review&#10;Publish update&#10;Follow up" class="textarea textarea-bordered min-h-28 w-full"></textarea></div>
            @error('title')<p class="text-error text-xs">{{ $message }}</p>@enderror @error('projectId')<p class="text-error text-xs">{{ $message }}</p>@enderror @error('cronExpression')<p class="text-error text-xs">{{ $message }}</p>@enderror
            <div class="modal-actions !px-0 !pb-0"><button type="button" data-modal-close class="btn">Cancel</button><button type="submit" class="btn btn-primary">Save template</button></div>
        </form>
    </x-modal>
</div>
