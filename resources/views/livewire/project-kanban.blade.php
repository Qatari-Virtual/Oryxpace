<div class="space-y-6 px-4 py-6 sm:px-6 sm:py-8">
    <x-toast :message="session('message')" />
    <x-toast type="error" :message="session('error')" />

    <header class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div><a href="/departments" class="mb-2 inline-flex text-xs font-bold text-brand hover:underline">← Departments</a><h1 class="text-3xl font-extrabold tracking-tight text-brand sm:text-4xl">{{ $project->name }}</h1><p class="mt-1 text-sm text-sub">{{ $project->department->name ?? 'No department' }} <span class="mx-1 text-muted">·</span> {{ $project->progress_percentage }}% complete</p></div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <a href="/projects/{{ $project->id }}/list" class="btn btn-outline btn-sm sm:btn-md">List view</a>
            <button data-modal-open="new-task-modal" class="btn btn-primary btn-sm sm:btn-md">+ Add task</button>
            <button data-modal-open="column-modal" class="btn btn-outline btn-sm sm:btn-md">+ Column</button>
        </div>
    </header>

    <div class="flex items-center justify-between rounded-2xl border border-brand-border bg-brand-pale px-4 py-3 text-xs text-sub"><span><strong class="text-brand">Board view</strong> · Select a task to inspect details.</span><span class="font-mono text-muted">{{ $columns->sum(fn ($column) => $column->tasks->count()) }} tasks</span></div>

    <div class="flex min-h-[560px] flex-col gap-4 md:flex-row md:overflow-x-auto md:pb-6" id="kanban-board">
        @foreach($columns as $col)
            <div class="w-full shrink-0 rounded-2xl border border-base-300 bg-base-200 p-3 shadow-sm md:w-80 md:p-4" data-column-id="{{ $col->id }}">
                <div class="flex justify-between items-center mb-3 pb-2 border-b border-base-300">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-base">{{ $col->name }}</span>
                        <span class="badge badge-neutral px-2.5 py-1">{{ $col->tasks->count() }}</span>
                    </div>
                    @if($col->required_permission)
                        <span class="badge badge-warning badge-xs" title="Requires {{ $col->required_permission }} permission">🔒 {{ $col->required_permission }}</span>
                    @endif
                </div>

                <div class="space-y-3 flex-1 overflow-y-auto kanban-column" data-column-id="{{ $col->id }}">
                    @foreach($col->tasks as $task)
                        <div data-task-id="{{ $task->id }}" wire:click="openTaskDetail({{ $task->id }})" class="card bg-base-100 p-3 shadow-sm border border-base-300 cursor-pointer hover:shadow-md transition sortable-card md:p-4">
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
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @script
    <script>
        import Sortable from 'sortablejs';

        const columns = document.querySelectorAll('.kanban-column');
        const sortables = [];

        columns.forEach(column => {
            const sortable = Sortable.create(column, {
                group: 'kanban',
                animation: 150,
                ghostClass: 'opacity-30',
                draggable: '.sortable-card',
                delay: 150,
                delayOnTouchOnly: true,
                touchStartThreshold: 5,
                onEnd: function(evt) {
                    const taskId = evt.item.dataset.taskId;
                    const targetColumnId = evt.to.dataset.columnId;
                    const newOrder = evt.newIndex;
                    $wire.moveTask(parseInt(taskId), parseInt(targetColumnId), newOrder);
                }
            });
            sortables.push(sortable);
        });

        $wire.$on('task-moved', () => {
            sortables.forEach(s => s.sort(Array.from(s.el.children).map(el => el), true));
        });
    </script>
    @endscript

    <x-modal id="new-task-modal" title="Create task" max-width="max-w-2xl">
        <form wire:submit.prevent="createTask" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3"><div class="sm:col-span-2"><label class="field-label">Task title</label><input type="text" wire:model="newTaskTitle" placeholder="What needs to happen?" class="input input-bordered w-full" required /></div><div><label class="field-label">Column</label><select wire:model="newTaskColumnId" class="select select-bordered w-full" required><option value="">Select</option>@foreach($columns as $column)<option value="{{ $column->id }}">{{ $column->name }}</option>@endforeach</select></div></div>
            <div><label class="field-label">Due date <span class="font-normal normal-case text-muted">(optional)</span></label><input type="date" wire:model="newTaskDueDate" class="input input-bordered w-full" /></div>
            <div><label class="field-label">Description <span class="font-normal normal-case text-muted">(optional)</span></label><textarea wire:model="newTaskDescription" placeholder="Add useful context for the assignee." class="textarea textarea-bordered min-h-24 w-full"></textarea></div>
            @error('newTaskTitle')<p class="text-error text-xs">{{ $message }}</p>@enderror @error('newTaskColumnId')<p class="text-error text-xs">{{ $message }}</p>@enderror
            <div class="modal-actions !px-0 !pb-0"><button type="button" data-modal-close class="btn">Cancel</button><button type="submit" class="btn btn-primary">Create task</button></div>
        </form>
    </x-modal>

    <x-modal id="column-modal" title="Add custom Kanban column">
            <form wire:submit.prevent="addColumn" class="space-y-4">
                <div>
                    <label class="label-text">Column Name</label>
                    <input type="text" wire:model="newColumnName" class="input input-bordered w-full" placeholder="e.g. Code Review" required />
                </div>
                <div>
                    <label class="label-text">Required Permission / Rank (Optional)</label>
                    <input type="text" wire:model="newColumnPermission" class="input input-bordered w-full" placeholder="e.g. lead_developer" />
                </div>
                <div class="modal-actions">
                    <button type="button" data-modal-close class="btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Column</button>
                </div>
            </form>
    </x-modal>

    <livewire:task-detail-modal />
</div>
