<div class="p-6 space-y-6">
    <div class="flex justify-between items-center bg-base-200 p-4 rounded-xl shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <a href="/departments" class="btn btn-ghost btn-xs">← Back</a>
                <h1 class="text-2xl font-bold">{{ $project->name }} (List View)</h1>
            </div>
            <p class="text-xs opacity-70 mt-1">Department: {{ $project->department->name ?? 'N/A' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="/projects/{{ $project->id }}/kanban" class="btn btn-sm btn-outline">Kanban View</a>
        </div>
    </div>

    <div class="card bg-base-100 p-4 border border-base-200 shadow-sm space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search tasks..." class="input input-bordered input-sm w-full" />

            <select wire:model.live="filterTagId" class="select select-bordered select-sm w-full">
                <option value="">All Tags</option>
                @foreach($tags as $tag)
                    <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterAssigneeId" class="select select-bordered select-sm w-full">
                <option value="">All Assignees</option>
                @foreach($pilots as $pilot)
                    <option value="{{ $pilot->id }}">{{ $pilot->name }} ({{ $pilot->callsign }})</option>
                @endforeach
            </select>

            <select wire:model.live="filterResolutionUserId" class="select select-bordered select-sm w-full">
                <option value="">All Resolution Users</option>
                @foreach($pilots as $pilot)
                    <option value="{{ $pilot->id }}">{{ $pilot->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="overflow-x-auto card bg-base-100 border border-base-200 shadow-sm">
        <table class="table table-zebra w-full">
            <thead>
                <tr class="bg-base-200">
                    <th>Task Title</th>
                    <th>Column / Status</th>
                    <th>Tags</th>
                    <th>Assignees</th>
                    <th>Resolution User</th>
                    <th>Due Date</th>
                    <th>Subtasks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                    <tr class="hover cursor-pointer" wire:click="openTaskDetail({{ $task->id }})">
                        <td class="font-bold text-secondary">{{ $task->title }}</td>
                        <td>
                            <span class="badge badge-neutral badge-sm">{{ $task->kanbanColumn->name ?? 'None' }}</span>
                        </td>
                        <td>
                            <div class="flex gap-1">
                                @foreach($task->tags as $tag)
                                    <span class="badge badge-xs text-white" style="background-color: {{ $tag->color }}">{{ $tag->name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            <div class="flex -space-x-2">
                                @foreach($task->assignees as $assignee)
                                    <span class="badge badge-outline badge-xs" title="{{ $assignee->name }}">{{ $assignee->callsign }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            @if($task->resolutionUser)
                                <span class="text-xs font-semibold text-accent">{{ $task->resolutionUser->name }}</span>
                            @else
                                <span class="text-xs opacity-40">-</span>
                            @endif
                        </td>
                        <td class="text-xs opacity-80">{{ $task->due_date ? $task->due_date->format('M d, Y') : '-' }}</td>
                        <td>
                            <span class="badge badge-ghost badge-sm">{{ $task->subtasks->count() }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-6 opacity-50">No tasks found matching filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
