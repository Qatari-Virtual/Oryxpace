<div class="p-6 space-y-6">
    @if (session()->has('message'))
        <div class="alert alert-success shadow-lg">
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <div class="flex justify-between items-center bg-base-200 p-4 rounded-xl shadow-sm">
        <div>
            <h1 class="text-2xl font-bold">Departments & Projects</h1>
            <p class="text-sm opacity-70">Manage organizational departments and track project completion</p>
        </div>
        <div class="flex items-center gap-4">
            <label class="label cursor-pointer gap-2">
                <span class="label-text font-medium">Show Archived</span>
                <input type="checkbox" wire:model.live="showArchived" class="toggle toggle-primary" />
            </label>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="card bg-base-100 shadow-md border border-base-200 p-5">
            <h2 class="card-title text-lg mb-3">Add Department</h2>
            <form wire:submit.prevent="createDepartment" class="flex gap-3">
                <input type="text" wire:model="newDepartmentName" placeholder="Department Name" class="input input-bordered w-full" required />
                <button type="submit" class="btn btn-primary">Create</button>
            </form>
        </div>

        <div class="card bg-base-100 shadow-md border border-base-200 p-5">
            <h2 class="card-title text-lg mb-3">Add Project</h2>
            <form wire:submit.prevent="createProject" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <select wire:model="newProjectDepartmentId" class="select select-bordered w-full" required>
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" wire:model="newProjectName" placeholder="Project Name" class="input input-bordered w-full" required />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <input type="date" wire:model="newProjectStartDate" class="input input-bordered w-full" placeholder="Start Date" />
                    <input type="date" wire:model="newProjectCompletionDate" class="input input-bordered w-full" placeholder="Completion Date" />
                </div>
                <textarea wire:model="newProjectDescription" class="textarea textarea-bordered w-full" placeholder="Project Description"></textarea>
                <button type="submit" class="btn btn-primary w-full">Add Project</button>
            </form>
        </div>
    </div>

    <div class="space-y-6">
        @foreach($departments as $dept)
            <div class="card bg-base-100 shadow-lg border border-base-300">
                <div class="card-body p-6">
                    <h2 class="card-title text-xl text-primary border-b border-base-200 pb-2 mb-4 flex justify-between">
                        <span>📁 {{ $dept->name }}</span>
                        <span class="badge badge-outline text-xs">{{ $dept->projects->count() }} Projects</span>
                    </h2>

                    @if($dept->projects->isEmpty())
                        <p class="text-sm opacity-50 italic">No active projects in this department.</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($dept->projects as $proj)
                                <div class="card bg-base-200 p-4 border border-base-300 hover:shadow-md transition">
                                    <div class="flex justify-between items-start mb-2">
                                        <a href="/projects/{{ $proj->id }}/kanban" class="font-bold text-lg hover:underline text-secondary">
                                            {{ $proj->name }}
                                        </a>
                                        <button wire:click="toggleArchive({{ $proj->id }})" class="btn btn-ghost btn-xs text-xs">
                                            {{ $proj->is_archived ? 'Unarchive' : 'Archive' }}
                                        </button>
                                    </div>
                                    <p class="text-xs opacity-70 mb-3 line-clamp-2">{{ $proj->description ?? 'No description provided.' }}</p>

                                    <div class="text-xs space-y-1 mb-3 opacity-80">
                                        <div><strong>Start:</strong> {{ $proj->start_date ? $proj->start_date->format('M d, Y') : 'N/A' }}</div>
                                        <div><strong>Target Completion:</strong> {{ $proj->completion_date ? $proj->completion_date->format('M d, Y') : 'N/A' }}</div>
                                    </div>

                                    <div class="space-y-1">
                                        <div class="flex justify-between text-xs font-semibold">
                                            <span>Progress</span>
                                            <span>{{ $proj->progress_percentage }}%</span>
                                        </div>
                                        <progress class="progress progress-primary w-full" value="{{ $proj->progress_percentage }}" max="100"></progress>
                                    </div>

                                    <div class="flex gap-2 mt-4 pt-2 border-t border-base-300">
                                        <a href="/projects/{{ $proj->id }}/kanban" class="btn btn-xs btn-outline btn-primary flex-1">Kanban View</a>
                                        <a href="/projects/{{ $proj->id }}/list" class="btn btn-xs btn-outline btn-secondary flex-1">List View</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
