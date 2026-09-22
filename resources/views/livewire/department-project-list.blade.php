<div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 sm:py-8">
    <x-toast :message="session('message')" />

    <header class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="eyebrow !ml-0">Operations / portfolio</p>
            <h1 class="text-3xl font-extrabold tracking-tight text-brand sm:text-4xl">Departments & Projects</h1>
            <p class="mt-1 text-sm text-sub">Keep teams aligned and move active work forward.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <label class="flex min-h-10 items-center justify-between gap-3 rounded-xl border border-brand-border bg-card px-3 text-xs font-bold text-sub">
                <span>Show archived</span>
                <input type="checkbox" wire:model.live="showArchived" class="toggle toggle-primary toggle-sm" />
            </label>
            <button data-modal-open="new-department-modal" class="btn btn-outline btn-primary">+ Department</button>
            <button data-modal-open="new-project-modal" class="btn btn-primary">+ Project</button>
        </div>
    </header>

    <section class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="stat-card"><span class="stat-label">Departments</span><strong>{{ $departments->count() }}</strong><span class="stat-meta">Organized teams</span></div>
        <div class="stat-card"><span class="stat-label">Projects</span><strong>{{ $departments->sum(fn ($department) => $department->projects->count()) }}</strong><span class="stat-meta">{{ $showArchived ? 'Including archived' : 'Active portfolio' }}</span></div>
        <div class="stat-card col-span-2 sm:col-span-1"><span class="stat-label">Workspace mode</span><strong class="text-lg">{{ $showArchived ? 'Full history' : 'Active only' }}</strong><span class="stat-meta">Use the toggle to change scope</span></div>
    </section>

    <section class="space-y-5">
        @forelse($departments as $dept)
            <article class="surface-card overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-brand-border bg-brand-pale/60 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="flex min-w-0 items-center gap-3">
                        @if($dept->icon_url)
                            <img src="{{ $dept->icon_url }}" alt="{{ $dept->name }}" class="h-10 w-10 shrink-0 rounded-xl object-cover border border-brand-border" />
                        @else
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand text-lg text-white">▦</span>
                        @endif
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-extrabold text-main">{{ $dept->name }}</h2>
                            <p class="text-xs text-muted">Department workspace</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 self-start sm:self-auto">
                        <button wire:click="openEditDepartment({{ $dept->id }})" data-modal-open="edit-department-modal" class="btn btn-ghost btn-xs text-xs font-semibold" title="Edit department & custom icon">
                            ✏ Edit
                        </button>
                        <span class="badge badge-outline text-xs">{{ $dept->projects->count() }} projects</span>
                    </div>
                </div>

                <div class="p-4 sm:p-6">
                    @if($dept->projects->isEmpty())
                        <div class="empty-state py-10">
                            <span class="text-3xl">◌</span>
                            <h3>No projects here yet</h3>
                            <p>Create a project to give this department a shared work queue.</p>
                            <button data-modal-open="new-project-modal" class="btn btn-sm btn-outline btn-primary mt-3">Add project</button>
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach($dept->projects as $proj)
                                <div class="project-card">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <a href="/projects/{{ $proj->id }}/kanban" class="block truncate text-base font-extrabold text-brand hover:underline">{{ $proj->name }}</a>
                                            <span class="mt-1 inline-flex rounded-full bg-base-200 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-muted">{{ $proj->is_archived ? 'Archived' : 'Active' }}</span>
                                        </div>
                                        <button wire:click="toggleArchive({{ $proj->id }})" class="btn btn-ghost btn-xs shrink-0">{{ $proj->is_archived ? 'Restore' : 'Archive' }}</button>
                                    </div>
                                    <p class="mt-3 line-clamp-2 min-h-10 text-xs leading-relaxed text-sub">{{ $proj->description ?? 'No description provided.' }}</p>
                                    <div class="mt-4 grid grid-cols-2 gap-3 border-y border-brand-border py-3 text-xs">
                                        <div><span class="data-label">Start</span><strong>{{ $proj->start_date?->format('M d, Y') ?? 'Not set' }}</strong></div>
                                        <div><span class="data-label">Target</span><strong>{{ $proj->completion_date?->format('M d, Y') ?? 'Not set' }}</strong></div>
                                    </div>
                                    <div class="mt-4">
                                        <div class="mb-1 flex justify-between text-[10px] font-black uppercase tracking-wider text-muted"><span>Progress</span><span class="font-mono text-brand">{{ $proj->progress_percentage }}%</span></div>
                                        <progress class="progress progress-primary w-full" value="{{ $proj->progress_percentage }}" max="100"></progress>
                                    </div>
                                    <div class="mt-4 flex gap-2">
                                        <a href="/projects/{{ $proj->id }}/kanban" class="btn btn-primary btn-sm flex-1">Open board</a>
                                        <a href="/projects/{{ $proj->id }}/list" class="btn btn-outline btn-sm">List</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="surface-card empty-state py-16"><span class="text-4xl">▦</span><h3>No departments yet</h3><p>Start by creating the first operational team.</p><button data-modal-open="new-department-modal" class="btn btn-primary mt-3">Create department</button></div>
        @endforelse
    </section>

    <x-modal id="new-department-modal" title="Create department">
        <form wire:submit.prevent="createDepartment" class="space-y-4">
            <p class="text-sm text-sub">Create a focused workspace for a team or operational function.</p>
            <div>
                <label class="field-label">Department name</label>
                <input type="text" wire:model="newDepartmentName" class="input input-bordered w-full" placeholder="e.g. Product Operations" required />
                @error('newDepartmentName')<p class="text-error mt-1 text-xs">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label">Department Icon <span class="font-normal normal-case text-muted">(optional, upload image from computer)</span></label>
                <input type="file" wire:model="newDepartmentIcon" accept="image/*" class="file-input file-input-bordered file-input-sm w-full" />
                @error('newDepartmentIcon')<p class="text-error mt-1 text-xs">{{ $message }}</p>@enderror
                @if ($newDepartmentIcon)
                    <div class="mt-2 flex items-center gap-3">
                        <span class="text-xs text-muted">Preview:</span>
                        <img src="{{ $newDepartmentIcon->temporaryUrl() }}" class="h-10 w-10 rounded-xl object-cover border border-brand-border">
                    </div>
                @endif
            </div>
            <div class="modal-actions !px-0 !pb-0">
                <button type="button" data-modal-close class="btn">Cancel</button>
                <button type="submit" class="btn btn-primary">Create department</button>
            </div>
        </form>
    </x-modal>

    <x-modal id="edit-department-modal" title="Edit department">
        <form wire:submit.prevent="updateDepartment" class="space-y-4">
            <p class="text-sm text-sub">Update department details or customize its icon from your computer library.</p>
            <div>
                <label class="field-label">Department name</label>
                <input type="text" wire:model="editingDepartmentName" class="input input-bordered w-full" required />
                @error('editingDepartmentName')<p class="text-error mt-1 text-xs">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label">Department Icon <span class="font-normal normal-case text-muted">(select image from computer)</span></label>
                <input type="file" wire:model="editingDepartmentIcon" accept="image/*" class="file-input file-input-bordered file-input-sm w-full" />
                @error('editingDepartmentIcon')<p class="text-error mt-1 text-xs">{{ $message }}</p>@enderror
                @if ($editingDepartmentIcon)
                    <div class="mt-2 flex items-center gap-3">
                        <span class="text-xs text-muted">New preview:</span>
                        <img src="{{ $editingDepartmentIcon->temporaryUrl() }}" class="h-10 w-10 rounded-xl object-cover border border-brand-border">
                    </div>
                @endif
            </div>
            <div class="modal-actions !px-0 !pb-0 flex items-center justify-between">
                @if($editingDepartmentId && \App\Models\Department::find($editingDepartmentId)?->icon_url)
                    <button type="button" wire:click="removeIcon({{ $editingDepartmentId }})" class="btn btn-ghost btn-xs text-error">Remove custom icon</button>
                @else
                    <div></div>
                @endif
                <div class="flex gap-2">
                    <button type="button" data-modal-close class="btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </form>
    </x-modal>

    <x-modal id="new-project-modal" title="Create project" max-width="max-w-2xl">
        <form wire:submit.prevent="createProject" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><div><label class="field-label">Project name</label><input type="text" wire:model="newProjectName" class="input input-bordered w-full" placeholder="Project name" required /></div><div><label class="field-label">Department</label><select wire:model="newProjectDepartmentId" class="select select-bordered w-full" required><option value="">Select department</option>@foreach($departments as $dept)<option value="{{ $dept->id }}">{{ $dept->name }}</option>@endforeach</select></div></div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><div><label class="field-label">Start date</label><input type="date" wire:model="newProjectStartDate" class="input input-bordered w-full" /></div><div><label class="field-label">Target completion</label><input type="date" wire:model="newProjectCompletionDate" class="input input-bordered w-full" /></div></div>
            <div><label class="field-label">Description <span class="font-normal normal-case text-muted">(optional)</span></label><textarea wire:model="newProjectDescription" class="textarea textarea-bordered min-h-24 w-full" placeholder="What is this project responsible for?"></textarea></div>
            @error('newProjectName')<p class="text-error text-xs">{{ $message }}</p>@enderror
            @error('newProjectDepartmentId')<p class="text-error text-xs">{{ $message }}</p>@enderror
            @error('newProjectCompletionDate')<p class="text-error text-xs">{{ $message }}</p>@enderror
            <div class="modal-actions !px-0 !pb-0"><button type="button" data-modal-close class="btn">Cancel</button><button type="submit" class="btn btn-primary">Create project</button></div>
        </form>
    </x-modal>
</div>
