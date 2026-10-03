<div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 sm:py-8">
    <x-toast :message="session('message')" />

    <header class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-brand sm:text-4xl">Permissions</h1>
        </div>
        <button wire:click="openCreateModal" class="btn btn-primary">+ Permission</button>
    </header>

    <section class="space-y-4">
        @forelse($permissions as $permission)
            <article class="surface-card overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-brand-border bg-brand-pale/60 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-extrabold text-main">{{ $permission->name }}</h2>
                        <p class="text-xs font-mono text-muted">{{ $permission->slug }}</p>
                        @if($permission->description)
                            <p class="mt-1 text-xs text-sub">{{ $permission->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 self-start sm:self-auto">
                        <span class="badge badge-outline text-xs">{{ $permission->pilots->count() }} pilots</span>
                        @if($permission->isTeamPermission())
                            <span class="badge badge-primary badge-outline text-xs">Team Permission</span>
                        @endif
                        <button wire:click="openStaffRolesModal({{ $permission->id }})" class="btn btn-ghost btn-xs text-xs font-semibold">Staff Roles</button>
                        <button wire:click="openPilotsModal({{ $permission->id }})" class="btn btn-ghost btn-xs text-xs font-semibold">Pilots</button>
                        <button wire:click="openAccessModal({{ $permission->id }})" class="btn btn-ghost btn-xs text-xs font-semibold">Access</button>
                        <button wire:click="openEditModal({{ $permission->id }})" class="btn btn-ghost btn-xs text-xs font-semibold">Edit</button>
                        <button wire:click="deletePermission({{ $permission->id }})" wire:confirm="Are you sure?" class="btn btn-ghost btn-xs text-xs font-semibold text-error">Delete</button>
                    </div>
                </div>

                <div class="p-4 sm:p-6">
                    @if($permission->access->isEmpty())
                        <div class="empty-state py-6">
                            <span class="text-2xl">◌</span>
                            <p class="text-sm text-sub">No access configured. Click "Access" to add department or project access.</p>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($permission->access as $access)
                                <div class="flex items-center justify-between rounded-lg border border-brand-border bg-card px-4 py-2">
                                    <div class="flex items-center gap-3">
                                        <span class="badge badge-sm {{ $access->isDepartmentLevel() ? 'badge-primary' : 'badge-secondary' }}">
                                            {{ $access->isDepartmentLevel() ? 'Dept' : 'Project' }}
                                        </span>
                                        <span class="text-sm font-semibold text-main">{{ $access->getScopeLabel() }}</span>
                                    </div>
                                    <button wire:click="removeAccess({{ $access->id }})" wire:confirm="Remove this access?" class="btn btn-ghost btn-xs text-error">Remove</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="surface-card empty-state py-16">
                <span class="text-4xl">🔒</span>
                <h3>No permissions yet</h3>
                <p>Create your first permission role to start managing access.</p>
                <button wire:click="openCreateModal" class="btn btn-primary mt-3">Create permission</button>
            </div>
        @endforelse
    </section>

    <x-modal id="create-permission-modal" title="Create permission" :open="$showCreateModal" close-action="closeCreateModal">
        <form wire:submit.prevent="createPermission" class="space-y-4">
            <p class="text-sm text-sub">Create a new permission role that can be assigned to pilots.</p>
            <div>
                <label class="field-label">Permission name</label>
                <input type="text" wire:model="permissionName" class="input input-bordered w-full" placeholder="e.g. Flight Operations Manager" required />
                @error('permissionName')<p class="text-error mt-1 text-xs">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label">Slug <span class="font-normal normal-case text-muted">(unique identifier, lowercase with underscores)</span></label>
                <input type="text" wire:model="permissionSlug" class="input input-bordered w-full font-mono" placeholder="e.g. flight_ops_manager" required />
                @error('permissionSlug')<p class="text-error mt-1 text-xs">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label">Description <span class="font-normal normal-case text-muted">(optional)</span></label>
                <textarea wire:model="permissionDescription" class="textarea textarea-bordered w-full" placeholder="What does this permission grant?"></textarea>
                @error('permissionDescription')<p class="text-error mt-1 text-xs">{{ $message }}</p>@enderror
            </div>
            <div class="modal-actions !px-0 !pb-0">
                <button type="button" wire:click="closeCreateModal" class="btn">Cancel</button>
                <button type="submit" class="btn btn-primary">Create permission</button>
            </div>
        </form>
    </x-modal>

    <x-modal id="edit-permission-modal" title="Edit permission" :open="$showEditModal" close-action="closeEditModal">
        <form wire:submit.prevent="updatePermission" class="space-y-4">
            <div>
                <label class="field-label">Permission name</label>
                <input type="text" wire:model="permissionName" class="input input-bordered w-full" required />
                @error('permissionName')<p class="text-error mt-1 text-xs">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label">Slug</label>
                <input type="text" wire:model="permissionSlug" class="input input-bordered w-full font-mono" required />
                @error('permissionSlug')<p class="text-error mt-1 text-xs">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label">Description</label>
                <textarea wire:model="permissionDescription" class="textarea textarea-bordered w-full"></textarea>
                @error('permissionDescription')<p class="text-error mt-1 text-xs">{{ $message }}</p>@enderror
            </div>
            <div class="modal-actions !px-0 !pb-0">
                <button type="button" wire:click="closeEditModal" class="btn">Cancel</button>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </x-modal>

    <x-modal id="access-modal" title="Manage access" max-width="max-w-2xl" :open="$showAccessModal" close-action="closeAccessModal">
        <div class="space-y-4">
            <p class="text-sm text-sub">Add department-level (all projects) or project-level (single project) access to this permission.</p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label">Add department access</label>
                    <input type="text" wire:model.live.debounce.200ms="deptSearch" class="input input-bordered input-sm w-full mb-2" placeholder="Search departments..." />
                    <div class="space-y-1 max-h-40 overflow-y-auto">
                        @forelse($departments as $dept)
                            <button type="button" wire:click="addDepartmentAccessById({{ $dept->id }})" class="flex w-full items-center gap-2 rounded-lg border border-brand-border bg-card px-3 py-2 text-left hover:border-brand/50">
                                @if($dept->icon)
                                    <i class="{{ $dept->icon }} text-brand"></i>
                                @else
                                    <span class="grid h-5 w-5 place-items-center rounded bg-brand/10 text-[10px] font-bold text-brand">▦</span>
                                @endif
                                <span class="text-sm font-semibold text-main">{{ $dept->name }}</span>
                            </button>
                        @empty
                            <p class="text-xs text-muted py-2 text-center">No departments found</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <label class="field-label">Add project access</label>
                    <input type="text" wire:model.live.debounce.200ms="projectSearch" class="input input-bordered input-sm w-full mb-2" placeholder="Search projects..." />
                    <div class="space-y-1 max-h-40 overflow-y-auto">
                        @forelse($projects as $proj)
                            <button type="button" wire:click="addProjectAccessById({{ $proj->id }})" class="flex w-full items-center gap-2 rounded-lg border border-brand-border bg-card px-3 py-2 text-left hover:border-brand/50">
                                @if($proj->department?->icon)
                                    <i class="{{ $proj->department->icon }} text-brand"></i>
                                @else
                                    <span class="grid h-5 w-5 place-items-center rounded bg-brand/10 text-[10px] font-bold text-brand">▦</span>
                                @endif
                                <div class="min-w-0">
                                    <span class="text-sm font-semibold text-main">{{ $proj->name }}</span>
                                    <span class="text-xs text-muted">({{ $proj->department?->name }})</span>
                                </div>
                            </button>
                        @empty
                            <p class="text-xs text-muted py-2 text-center">No projects found</p>
                        @endforelse
                    </div>
                </div>
            </div>

            @php
                $currentPermission = $permissions->firstWhere('id', $editingPermissionId);
            @endphp
            @if($currentPermission && $currentPermission->access->isNotEmpty())
                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-main">Current access</h3>
                    @foreach($currentPermission->access as $access)
                        <div class="flex items-center justify-between rounded-lg border border-brand-border bg-card px-4 py-2">
                            <div class="flex items-center gap-3">
                                <span class="badge badge-sm {{ $access->isDepartmentLevel() ? 'badge-primary' : 'badge-secondary' }}">
                                    {{ $access->isDepartmentLevel() ? 'Dept' : 'Project' }}
                                </span>
                                @if($access->isDepartmentLevel() && $access->department?->icon)
                                    <i class="{{ $access->department->icon }} text-brand"></i>
                                @elseif($access->isProjectLevel() && $access->project?->department?->icon)
                                    <i class="{{ $access->project->department->icon }} text-brand"></i>
                                @else
                                    <span class="grid h-5 w-5 place-items-center rounded bg-brand/10 text-[10px] font-bold text-brand">▦</span>
                                @endif
                                <span class="text-sm font-semibold text-main">{{ $access->getScopeLabel() }}</span>
                            </div>
                            <button wire:click="removeAccess({{ $access->id }})" wire:confirm="Remove this access?" class="btn btn-ghost btn-xs text-error">Remove</button>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="modal-actions !px-0 !pb-0">
                <button type="button" wire:click="closeAccessModal" class="btn">Close</button>
            </div>
        </div>
    </x-modal>

    <x-modal id="pilots-modal" title="Manage pilots" max-width="max-w-2xl" :open="$showPilotsModal" close-action="closePilotsModal">
        <div class="space-y-4">
            @php
                $currentPermission = $permissions->firstWhere('id', $editingPermissionId);
                $isTeamPermission = $currentPermission && $currentPermission->isTeamPermission();
            @endphp

            @if($isTeamPermission)
                <p class="text-sm text-sub">Team members are automatically derived from staff roles matching this permission's role types. No manual assignment needed.</p>

                @php
                    $teamPilotIds = \App\Models\StaffRole::where(function ($query) use ($currentPermission) {
                        foreach ($currentPermission->roleTypes as $rt) {
                            $query->orWhere(function ($q) use ($rt) {
                                $q->whereRaw('TRIM(role_name) = ?', [trim($rt->role_name)])
                                  ->whereRaw('TRIM(level) = ?', [trim($rt->level)]);
                            });
                        }
                    })->pluck('user_id')->unique();

                    $teamPilots = \App\Models\Pilot::whereIn('id', $teamPilotIds)->orderBy('name')->get();
                @endphp

                @if($teamPilots->isEmpty())
                    <div class="empty-state py-8">
                        <p class="text-sm text-sub">No pilots have matching staff roles yet.</p>
                    </div>
                @else
                    <div class="space-y-2">
                        <h3 class="text-sm font-bold text-main">Team members ({{ $teamPilots->count() }})</h3>
                        @foreach($teamPilots as $pilot)
                            <div class="flex items-center justify-between rounded-lg border border-brand-border bg-card px-4 py-2">
                                <div class="flex items-center gap-3">
                                    @if($pilot->getAvatarUrl(32))
                                        <img src="{{ $pilot->getAvatarUrl(32) }}" alt="{{ $pilot->callsign }}" class="h-8 w-8 rounded-lg object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                                    @endif
                                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand/10 text-sm font-bold text-brand" {{ $pilot->getAvatarUrl(32) ? 'style=display:none' : '' }}>
                                        {{ substr($pilot->callsign ?? $pilot->name, 0, 2) }}
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-main">{{ $pilot->name }}</p>
                                        <p class="text-xs font-mono text-muted">{{ $pilot->callsign }}</p>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach($currentPermission->roleTypes as $rt)
                                                @php
                                                    $hasRole = \App\Models\StaffRole::where('user_id', $pilot->id)
                                                        ->whereRaw('TRIM(role_name) = ?', [trim($rt->role_name)])
                                                        ->whereRaw('TRIM(level) = ?', [trim($rt->level)])
                                                        ->exists();
                                                @endphp
                                                @if($hasRole)
                                                    <span class="badge badge-xs {{ $rt->level === 'Executive' ? 'bg-[#5a1a3a] text-white border-0' : ($rt->level === 'Manager' ? 'bg-[#c46a2a] text-white border-0' : 'bg-[#b8963e] text-white border-0') }}">
                                                        {{ $rt->role_name }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <span class="badge badge-sm badge-primary">Auto-assigned</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <p class="text-sm text-sub">This is an access-only permission. Manually assign pilots below.</p>

                <div>
                    <label class="field-label">Search pilot</label>
                    <div class="flex gap-2">
                        <input type="text" wire:model="pilotSearch" class="input input-bordered flex-1" placeholder="Search by name or callsign..." />
                        <button wire:click="searchPilots" class="btn btn-outline">Search</button>
                    </div>
                </div>

                @if($searchedPilots !== null)
                    <div class="space-y-2">
                        <h3 class="text-sm font-bold text-main">Search results ({{ $searchedPilots->count() }})</h3>
                        @if($searchedPilots->isEmpty())
                            <p class="text-sm text-sub py-4 text-center">No pilots found matching "{{ $pilotSearch }}"</p>
                        @else
                            @foreach($searchedPilots as $pilot)
                                @php
                                    $isAssigned = $currentPermission && $currentPermission->pilots->contains('id', $pilot->id);
                                @endphp
                                <div class="flex items-center justify-between rounded-lg border border-brand-border bg-card px-4 py-2 {{ $isAssigned ? 'opacity-50' : '' }}">
                                    <div class="flex items-center gap-3">
                                        @if($pilot->getAvatarUrl(32))
                                            <img src="{{ $pilot->getAvatarUrl(32) }}" alt="{{ $pilot->callsign }}" class="h-8 w-8 rounded-lg object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                                        @endif
                                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand/10 text-sm font-bold text-brand" {{ $pilot->getAvatarUrl(32) ? 'style=display:none' : '' }}>
                                            {{ substr($pilot->callsign ?? $pilot->name, 0, 2) }}
                                        </span>
                                        <div>
                                            <p class="text-sm font-semibold text-main">{{ $pilot->name }}</p>
                                            <p class="text-xs font-mono text-muted">{{ $pilot->callsign }}</p>
                                        </div>
                                    </div>
                                    @if($isAssigned)
                                        <span class="badge badge-sm badge-primary">Assigned</span>
                                    @else
                                        <button wire:click="$set('newPilotId', {{ $pilot->id }}); addPilot()" class="btn btn-ghost btn-xs text-brand">Add</button>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endif

                @if($currentPermission && $currentPermission->pilots->isNotEmpty())
                    <div class="space-y-2">
                        <h3 class="text-sm font-bold text-main">Assigned pilots ({{ $currentPermission->pilots->count() }})</h3>
                        @foreach($currentPermission->pilots as $pilot)
                            <div class="flex items-center justify-between rounded-lg border border-brand-border bg-card px-4 py-2">
                                <div class="flex items-center gap-3">
                                    @if($pilot->getAvatarUrl(32))
                                        <img src="{{ $pilot->getAvatarUrl(32) }}" alt="{{ $pilot->callsign }}" class="h-8 w-8 rounded-lg object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                                    @endif
                                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand/10 text-sm font-bold text-brand" {{ $pilot->getAvatarUrl(32) ? 'style=display:none' : '' }}>
                                        {{ substr($pilot->callsign ?? $pilot->name, 0, 2) }}
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-main">{{ $pilot->name }}</p>
                                        <p class="text-xs font-mono text-muted">{{ $pilot->callsign }}</p>
                                    </div>
                                </div>
                                <button wire:click="removePilot({{ $pilot->id }})" wire:confirm="Remove this pilot?" class="btn btn-ghost btn-xs text-error">Remove</button>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

            <div class="modal-actions !px-0 !pb-0">
                <button type="button" wire:click="closePilotsModal" class="btn">Close</button>
            </div>
        </div>
    </x-modal>

    <x-modal id="staff-roles-modal" title="Link Staff Roles" max-width="max-w-2xl" :open="$showStaffRolesModal" close-action="closeStaffRolesModal">
        <div class="space-y-4">
            <p class="text-sm text-sub">Link staff roles to this permission. Pilots with this permission scoped to a department will become team members with these roles.</p>

            @php
                $currentPermission = $permissions->firstWhere('id', $editingPermissionId);
            @endphp

            <div class="space-y-2">
                @foreach($uniqueRoleTypes as $roleType)
                    @php
                        $roleKey = $roleType->role_name . '|' . $roleType->level;
                    @endphp
                    <label class="flex items-center gap-3 rounded-lg border border-brand-border bg-card px-4 py-3 cursor-pointer hover:border-brand/50">
                        <input type="checkbox" wire:model="selectedRoleTypes" value="{{ $roleKey }}" class="checkbox checkbox-primary checkbox-sm" />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="badge badge-sm {{ $roleType->level === 'Executive' ? 'bg-[#5a1a3a] text-white border-0' : ($roleType->level === 'Manager' ? 'bg-[#c46a2a] text-white border-0' : 'bg-[#b8963e] text-white border-0') }}">
                                    {{ $roleType->level }}
                                </span>
                                <span class="text-sm font-semibold text-main">{{ $roleType->role_name }}</span>
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="modal-actions !px-0 !pb-0">
                <button type="button" wire:click="closeStaffRolesModal" class="btn">Cancel</button>
                <button type="button" wire:click="saveStaffRoles" class="btn btn-primary">Save Roles</button>
            </div>
        </div>
    </x-modal>
</div>
