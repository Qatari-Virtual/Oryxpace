<div class="p-6 space-y-6">
    @if (session()->has('message'))
        <div class="alert alert-success shadow-sm">
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-base-200 p-4 rounded-xl shadow-sm">
        <h1 class="text-2xl font-bold">Schedule & Template Manager</h1>
        <p class="text-xs opacity-70 mt-1">Configure automated recurring task creation for events, socials (POTW/LOTW), route renewals, and development routines.</p>
    </div>

    <!-- Create Schedule Template Form -->
    <div class="card bg-base-100 p-5 border border-base-200 shadow-md">
        <h2 class="card-title text-lg mb-4">New Task Schedule Template</h2>
        <form wire:submit.prevent="createSchedule" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="label-text">Task Template Title</label>
                    <input type="text" wire:model="title" placeholder="e.g. POTW/LOTW Social Media Post" class="input input-bordered w-full mt-1" required />
                </div>
                <div>
                    <label class="label-text">Target Project</label>
                    <select wire:model="projectId" class="select select-bordered w-full mt-1" required>
                        <option value="">Select Project</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="label-text">Cron Schedule Expression</label>
                    <input type="text" wire:model="cronExpression" placeholder="0 0 * * * (Daily) or 0 0 * * 1 (Weekly)" class="input input-bordered w-full mt-1" required />
                </div>
                <div class="flex items-center gap-3 pt-6">
                    <label class="label cursor-pointer gap-2">
                        <span class="label-text font-medium">Is Active</span>
                        <input type="checkbox" wire:model="isActive" class="toggle toggle-primary" />
                    </label>
                </div>
            </div>

            <div>
                <label class="label-text">Sub-task Templates (One per line)</label>
                <textarea wire:model="subtaskTemplatesText" placeholder="e.g.&#10;Graphic Creation&#10;Thread Creation&#10;Social Posting&#10;Follow Up" class="textarea textarea-bordered w-full h-24 mt-1"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Save Schedule Template</button>
        </form>
    </div>

    <!-- Existing Schedules List -->
    <div class="card bg-base-100 border border-base-200 shadow-md p-5">
        <h2 class="card-title text-lg mb-4">Active Schedules</h2>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr class="bg-base-200">
                        <th>Template Title</th>
                        <th>Project</th>
                        <th>Cron Schedule</th>
                        <th>Sub-tasks</th>
                        <th>Last Executed</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $sched)
                        <tr>
                            <td class="font-bold">{{ $sched->title }}</td>
                            <td>{{ $sched->project->name ?? 'N/A' }}</td>
                            <td><code class="badge badge-neutral badge-sm">{{ $sched->cron_expression }}</code></td>
                            <td>
                                <span class="badge badge-ghost badge-sm">{{ count($sched->subtask_templates ?? []) }} subtasks</span>
                            </td>
                            <td class="text-xs opacity-70">{{ $sched->last_run_at ? $sched->last_run_at->diffForHumans() : 'Never' }}</td>
                            <td>
                                <span class="badge {{ $sched->is_active ? 'badge-success' : 'badge-error' }} badge-xs">
                                    {{ $sched->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="flex gap-2">
                                <button wire:click="runScheduleNow({{ $sched->id }})" class="btn btn-xs btn-primary">Run Now</button>
                                <button wire:click="toggleSchedule({{ $sched->id }})" class="btn btn-xs btn-ghost">Toggle</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-6 opacity-50">No schedule templates created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
