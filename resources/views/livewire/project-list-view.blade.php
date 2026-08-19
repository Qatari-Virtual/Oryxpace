<div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 sm:py-8">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <a href="/projects/{{ $project->id }}/kanban" class="mb-2 inline-flex text-xs font-bold text-brand hover:underline">← Board view</a>
            <h1 class="text-3xl font-extrabold tracking-tight text-brand sm:text-4xl">{{ $project->name }}<span class="text-sub"> / List</span></h1>
            <p class="mt-1 text-sm text-sub">{{ $project->department->name ?? 'No department' }} · {{ $tasks->count() }} tasks</p>
        </div>
        <a href="/projects/{{ $project->id }}/kanban" class="btn btn-primary">Open board</a>
    </div>

    <div class="surface-card p-4 sm:p-5">
        <div class="mb-3 flex items-center justify-between"><div><h2 class="font-extrabold text-main">Find tasks</h2><p class="text-xs text-muted">Filter the current project without leaving the list.</p></div><span class="text-xs font-mono text-muted">{{ $tasks->count() }} results</span></div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
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

    <div class="surface-card overflow-hidden">
        <table class="table table-zebra w-full">
            <thead>
                    <tr class="bg-brand-pale">
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
                        <td colspan="7" class="py-16 text-center"><div class="empty-state"><span class="text-3xl">⌕</span><h3>No tasks found</h3><p>Try a different search or filter combination.</p></div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <livewire:task-detail-modal />
</div>
