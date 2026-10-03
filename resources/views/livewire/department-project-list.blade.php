<div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 sm:py-8">
    <x-toast :message="session('message')" />

    <header class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-brand sm:text-4xl">Departments & Projects</h1>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <button wire:click="$toggle('showArchived')" class="btn btn-outline btn-sm">
                @if($showArchived)
                    Hide archived
                @else
                    Show archived
                @endif
            </button>
            <button wire:click="openNewDepartmentModal" class="btn btn-outline btn-primary">+ Department</button>
            <button wire:click="openNewProjectModal" class="btn btn-primary">+ Project</button>
        </div>
    </header>

    <section class="space-y-5">
        @forelse($departments as $dept)
            <article class="surface-card overflow-hidden">
                <div class="flex items-center gap-3 border-b border-brand-border bg-brand-pale/60 px-4 py-3">
                    <div class="flex min-w-0 items-center gap-3 flex-1">
                        @if($dept->icon)
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-brand text-base text-white">
                                <i class="{{ $dept->icon }} text-base"></i>
                            </span>
                        @else
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-brand text-base text-white">▦</span>
                        @endif
                        <div class="min-w-0">
                            <h2 class="truncate text-base font-extrabold text-main">{{ $dept->name }}</h2>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button wire:click="openEditDepartment({{ $dept->id }})" class="btn btn-ghost btn-xs text-xs font-semibold" title="Edit department & custom icon">
                            ✏ Edit
                        </button>
                        <span class="badge badge-outline text-xs">{{ $dept->projects->count() }} projects</span>
                    </div>
                </div>

                <div class="p-3">
                    @php
                        $teamMembers = $dept->getTeamMembers();
                    @endphp
                    @if($teamMembers->isNotEmpty())
                        <details class="team-section group">
                            <summary class="team-summary cursor-pointer select-none py-1">
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <span class="team-chevron text-muted">▸</span>
                                        <h3 class="text-xs font-bold text-main">Team</h3>
                                        <span class="text-[10px] font-semibold text-sub">{{ $teamMembers->count() }}</span>
                                    </div>
                                    <div class="flex -space-x-1.5">
                                        @foreach($teamMembers as $member)
                                            @if($member['pilot']->getAvatarUrl(28))
                                                <img src="{{ $member['pilot']->getAvatarUrl(28) }}" alt="{{ $member['pilot']->name }}" class="team-avatar h-6 w-6 rounded-full object-cover border border-card" title="{{ $member['pilot']->name }}">
                                            @else
                                                <span class="team-avatar grid h-6 w-6 place-items-center rounded-full bg-brand/20 text-[8px] font-bold text-brand border border-card" title="{{ $member['pilot']->name }}">
                                                    {{ substr($member['pilot']->callsign ?? $member['pilot']->name, 0, 2) }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </summary>
                            <div class="team-content mt-1 space-y-2">
                                @php
                                    $grouped = $teamMembers->groupBy(function ($m) {
                                        return $m['roles']->first()?->role_name ?? 'Other';
                                    });
                                @endphp
                                @foreach($grouped as $roleName => $members)
                                    <div>
                                        <h4 class="text-[10px] font-bold uppercase tracking-wider text-sub mb-1">{{ $roleName }}s</h4>
                                        <div class="space-y-1">
                                            @foreach($members as $member)
                                                @php
                                                    $level = $member['highest_level'] ?? 'Staff';
                                                    $bgClass = match($level) {
                                                        'Executive' => 'bg-[#5a1a3a] text-white',
                                                        'Manager' => 'bg-[#c46a2a] text-white',
                                                        default => 'bg-[#b8963e] text-white',
                                                    };
                                                @endphp
                                                <div class="flex items-center gap-2 rounded-lg {{ $bgClass }} px-2.5 py-1.5 shadow-sm">
                                                    @if($member['pilot']->getAvatarUrl(32))
                                                        <img src="{{ $member['pilot']->getAvatarUrl(32) }}" alt="{{ $member['pilot']->callsign }}" class="h-8 w-8 rounded-full object-cover border-2 border-white/30" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                                                    @endif
                                                    <span class="grid h-8 w-8 place-items-center rounded-full bg-white/20 text-[10px] font-bold text-white" {{ $member['pilot']->getAvatarUrl(32) ? 'style=display:none' : '' }}>
                                                        {{ substr($member['pilot']->callsign ?? $member['pilot']->name, 0, 2) }}
                                                    </span>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-[11px] font-bold text-white truncate">{{ $member['pilot']->name }}</p>
                                                        <p class="text-[9px] text-white/80 uppercase tracking-wide">{{ $roleName }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif

                    @if($dept->projects->isEmpty())
                        <div class="empty-state py-6">
                            <span class="text-2xl">◌</span>
                            <h3 class="text-sm">No projects here yet</h3>
                            <p class="text-xs">Create a project to give this department a shared work queue.</p>
                            <button wire:click="openNewProjectModal({{ $dept->id }})" class="btn btn-xs btn-outline btn-primary mt-2">Add project</button>
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach($dept->projects as $proj)
                                <div class="project-card">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <a href="/projects/{{ $proj->id }}/kanban" class="block truncate text-sm font-extrabold text-brand hover:underline">{{ $proj->name }}</a>
                                            <span class="mt-0.5 inline-flex rounded-full bg-base-200 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-muted">{{ $proj->is_archived ? 'Archived' : 'Active' }}</span>
                                        </div>
                                        <button wire:click="toggleArchive({{ $proj->id }})" class="btn btn-ghost btn-xs shrink-0 text-[10px]">{{ $proj->is_archived ? 'Restore' : 'Archive' }}</button>
                                    </div>
                                    <div class="mt-2">
                                        <div class="mb-0.5 flex justify-between text-[9px] font-black uppercase tracking-wider text-muted"><span>Progress</span><span class="font-mono text-brand">{{ $proj->progress_percentage }}%</span></div>
                                        <progress class="progress progress-primary w-full h-1.5" value="{{ $proj->progress_percentage }}" max="100"></progress>
                                    </div>
                                    <div class="mt-2 flex gap-1.5">
                                        <a href="/projects/{{ $proj->id }}/kanban" class="btn btn-primary btn-xs flex-1">Open board</a>
                                        <a href="/projects/{{ $proj->id }}/list" class="btn btn-outline btn-xs">List</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="surface-card empty-state py-16"><span class="text-4xl">▦</span><h3>No departments yet</h3><p>Start by creating the first operational team.</p><button wire:click="openNewDepartmentModal" class="btn btn-primary mt-3">Create department</button></div>
        @endforelse
    </section>

    <x-modal id="new-department-modal" title="Create department" max-width="max-w-2xl" :open="$showNewDepartmentModal" close-action="closeNewDepartmentModal">
        <form wire:submit.prevent="createDepartment" class="space-y-4">
            <p class="text-sm text-sub">Create a focused workspace for a team or operational function.</p>
            <div>
                <label class="field-label">Department name</label>
                <input type="text" wire:model="newDepartmentName" class="input input-bordered w-full" placeholder="e.g. Product Operations" required />
                @error('newDepartmentName')<p class="text-error mt-1 text-xs">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label">Department Icon <span class="font-normal normal-case text-muted">(optional)</span></label>
                <input type="text" wire:model.live.debounce.300ms="newIconSearch" class="input input-bordered input-sm w-full mt-2" placeholder="Search icons..." />
                <div class="mt-2 grid grid-cols-8 gap-2 max-h-48 overflow-y-auto">
                    <button type="button" wire:click="$set('newDepartmentIcon', '')" class="grid h-10 w-10 place-items-center rounded-lg border-2 {{ $newDepartmentIcon === '' ? 'border-brand bg-brand/10' : 'border-brand-border' }}" title="No icon">
                        <span class="text-xs text-muted">—</span>
                    </button>
                    @foreach($newIcons as $iconClass => $iconLabel)
                        <button type="button" wire:click="$set('newDepartmentIcon', '{{ $iconClass }}')" class="grid h-10 w-10 place-items-center rounded-lg border-2 {{ $newDepartmentIcon === $iconClass ? 'border-brand bg-brand/10' : 'border-brand-border hover:border-brand/50' }}" title="{{ $iconLabel }}">
                            <i class="{{ $iconClass }} text-lg {{ $newDepartmentIcon === $iconClass ? 'text-brand' : 'text-main' }}"></i>
                        </button>
                    @endforeach
                </div>
                @error('newDepartmentIcon')<p class="text-error mt-1 text-xs">{{ $message }}</p>@enderror
            </div>
            <div class="modal-actions !px-0 !pb-0">
                <button type="button" wire:click="closeNewDepartmentModal" class="btn">Cancel</button>
                <button type="submit" class="btn btn-primary">Create department</button>
            </div>
        </form>
    </x-modal>

    <x-modal id="edit-department-modal" title="Edit department" max-width="max-w-2xl" :open="$showEditModal" close-action="closeEditDepartment">
        <form wire:submit.prevent="updateDepartment" class="space-y-4">
            <p class="text-sm text-sub">Update department details or customize its icon.</p>
            <div>
                <label class="field-label">Department name</label>
                <input type="text" wire:model="editingDepartmentName" class="input input-bordered w-full" required />
                @error('editingDepartmentName')<p class="text-error mt-1 text-xs">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label">Department Icon</label>
                <input type="text" wire:model.live.debounce.300ms="editIconSearch" class="input input-bordered input-sm w-full mt-2" placeholder="Search icons..." />
                <div class="mt-2 grid grid-cols-8 gap-2 max-h-48 overflow-y-auto">
                    <button type="button" wire:click="$set('editingDepartmentIcon', '')" class="grid h-10 w-10 place-items-center rounded-lg border-2 {{ $editingDepartmentIcon === '' ? 'border-brand bg-brand/10' : 'border-brand-border' }}" title="No icon">
                        <span class="text-xs text-muted">—</span>
                    </button>
                    @foreach($editIcons as $iconClass => $iconLabel)
                        <button type="button" wire:click="$set('editingDepartmentIcon', '{{ $iconClass }}')" class="grid h-10 w-10 place-items-center rounded-lg border-2 {{ $editingDepartmentIcon === $iconClass ? 'border-brand bg-brand/10' : 'border-brand-border hover:border-brand/50' }}" title="{{ $iconLabel }}">
                            <i class="{{ $iconClass }} text-lg {{ $editingDepartmentIcon === $iconClass ? 'text-brand' : 'text-main' }}"></i>
                        </button>
                    @endforeach
                </div>
                @error('editingDepartmentIcon')<p class="text-error mt-1 text-xs">{{ $message }}</p>@enderror
            </div>
            <div class="modal-actions !px-0 !pb-0 flex items-center justify-between">
                @if($editingDepartmentId && \App\Models\Department::find($editingDepartmentId)?->icon)
                    <button type="button" wire:click="removeIcon({{ $editingDepartmentId }})" class="btn btn-ghost btn-xs text-error">Remove icon</button>
                @else
                    <div></div>
                @endif
                <div class="flex gap-2">
                    <button type="button" wire:click="closeEditDepartment" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </form>
    </x-modal>

    <x-modal id="new-project-modal" title="Create project" max-width="max-w-2xl" :open="$showNewProjectModal" close-action="closeNewProjectModal">
        <form wire:submit.prevent="createProject" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><div><label class="field-label">Project name</label><input type="text" wire:model="newProjectName" class="input input-bordered w-full" placeholder="Project name" required /></div><div><label class="field-label">Department</label><select wire:model="newProjectDepartmentId" class="select select-bordered w-full" required><option value="">Select department</option>@foreach($departments as $dept)<option value="{{ $dept->id }}">{{ $dept->name }}</option>@endforeach</select></div></div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><div><label class="field-label">Start date</label><input type="date" wire:model="newProjectStartDate" class="input input-bordered w-full" /></div><div><label class="field-label">Target completion</label><input type="date" wire:model="newProjectCompletionDate" class="input input-bordered w-full" /></div></div>
            <div><label class="field-label">Description <span class="font-normal normal-case text-muted">(optional)</span></label><textarea wire:model="newProjectDescription" class="textarea textarea-bordered min-h-24 w-full" placeholder="What is this project responsible for?"></textarea></div>
            @error('newProjectName')<p class="text-error text-xs">{{ $message }}</p>@enderror
            @error('newProjectDepartmentId')<p class="text-error text-xs">{{ $message }}</p>@enderror
            @error('newProjectCompletionDate')<p class="text-error text-xs">{{ $message }}</p>@enderror
            <div class="modal-actions !px-0 !pb-0"><button type="button" wire:click="closeNewProjectModal" class="btn">Cancel</button><button type="submit" class="btn btn-primary">Create project</button></div>
        </form>
    </x-modal>
</div>
