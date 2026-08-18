<div class="p-6 space-y-6">
    @if (session()->has('message'))
        <div class="alert alert-success shadow-sm">
            <span>{{ session('message') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-error shadow-sm">
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="flex justify-between items-center bg-base-200 p-4 rounded-xl shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <a href="/departments" class="btn btn-ghost btn-xs">← Back</a>
                <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
            </div>
            <p class="text-xs opacity-70 mt-1">Department: {{ $project->department->name ?? 'N/A' }} | Progress: {{ $project->progress_percentage }}%</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/projects/{{ $project->id }}/list" class="btn btn-sm btn-outline">List View</a>
            <button onclick="column_modal.showModal()" class="btn btn-sm btn-primary">+ Add Column</button>
        </div>
    </div>

    <div class="flex gap-4 overflow-x-auto pb-6 min-h-[600px]">
        @foreach($columns as $col)
            <div class="w-80 bg-base-200 rounded-xl p-4 flex flex-col shrink-0 border border-base-300 shadow-sm">
                <div class="flex justify-between items-center mb-3 pb-2 border-b border-base-300">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-base">{{ $col->name }}</span>
                        <span class="badge badge-sm badge-neutral">{{ $col->tasks->count() }}</span>
                    </div>
                    @if($col->required_permission)
                        <span class="badge badge-warning badge-xs" title="Requires {{ $col->required_permission }} permission">🔒 {{ $col->required_permission }}</span>
                    @endif
                </div>

                <div class="space-y-3 flex-1 overflow-y-auto">
                    @foreach($col->tasks as $task)
                        <div wire:click="openTaskDetail({{ $task->id }})" class="card bg-base-100 p-4 shadow-sm border border-base-300 cursor-pointer hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-semibold text-sm line-clamp-2">{{ $task->title }}</h3>
                                @if($task->subtasks->count() > 0)
                                    <span class="badge badge-ghost badge-xs">{{ $task->subtasks->count() }} subtasks</span>
                                @endif
                            </div>

                            @if($task->tags->count() > 0)
                                <div class="flex flex-wrap gap-1 mb-2">
                                    @foreach($task->tags as $tag)
                                        <span class="badge badge-xs text-white" style="background-color: {{ $tag->color }}">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="flex justify-between items-center text-xs opacity-70 mt-2 pt-2 border-t border-base-200">
                                <span>Assignees: {{ $task->assignees->count() }}</span>
                                @if($task->resolutionUser)
                                    <span class="text-accent font-medium">For: {{ $task->resolutionUser->name }}</span>
                                @endif
                            </div>

                            <div class="flex gap-1 mt-3" onclick="event.stopPropagation()">
                                @foreach($columns as $targetCol)
                                    @if($targetCol->id !== $col->id)
                                        <button wire:click="moveTask({{ $task->id }}, {{ $targetCol->id }})" class="btn btn-ghost btn-xs text-[10px] border border-base-300">
                                            → {{ $targetCol->name }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <dialog id="column_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">Add Custom Kanban Column</h3>
            <form wire:submit.prevent="addColumn" class="space-y-4">
                <div>
                    <label class="label-text">Column Name</label>
                    <input type="text" wire:model="newColumnName" class="input input-bordered w-full" placeholder="e.g. Code Review" required />
                </div>
                <div>
                    <label class="label-text">Required Permission / Rank (Optional)</label>
                    <input type="text" wire:model="newColumnPermission" class="input input-bordered w-full" placeholder="e.g. lead_developer" />
                </div>
                <div class="modal-action">
                    <button type="button" onclick="column_modal.close()" class="btn">Cancel</button>
                    <button type="submit" onclick="column_modal.close()" class="btn btn-primary">Add Column</button>
                </div>
            </form>
        </div>
    </dialog>
</div>
