<div>
    @if($isOpen && $task)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-4xl p-6 space-y-6">
                <div class="flex justify-between items-start border-b border-base-200 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="badge badge-primary">{{ $task->kanbanColumn->name ?? 'Unassigned' }}</span>
                            <span class="text-xs opacity-50">Task #{{ $task->id }}</span>
                        </div>
                        <h2 class="text-2xl font-bold mt-1">{{ $task->title }}</h2>
                    </div>
                    <button wire:click="closeModal" class="btn btn-sm btn-circle btn-ghost">✕</button>
                </div>

                @if(session()->has('modal_message'))
                    <div class="alert alert-success alert-sm">
                        <span>{{ session('modal_message') }}</span>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 space-y-6">
                        <div class="bg-base-200 p-4 rounded-xl border border-base-300">
                            <h3 class="font-bold text-sm mb-2 text-primary">Description</h3>
                            <p class="text-sm leading-relaxed whitespace-pre-line opacity-90">
                                {{ $task->description ?? 'No description provided.' }}
                            </p>
                        </div>

                        <div class="bg-base-200 p-4 rounded-xl border border-base-300 space-y-3">
                            <h3 class="font-bold text-sm text-primary flex justify-between items-center">
                                <span>Sub-tasks</span>
                                <span class="badge badge-sm badge-neutral">{{ $task->subtasks->count() }}</span>
                            </h3>

                            <form wire:submit.prevent="addSubtask" class="flex gap-2">
                                <input type="text" wire:model="newSubtaskTitle" placeholder="Add a sub-task..." class="input input-sm input-bordered w-full" required />
                                <button type="submit" class="btn btn-sm btn-primary">+ Add</button>
                            </form>

                            <div class="space-y-2 mt-3">
                                @foreach($task->subtasks as $sub)
                                    <div class="p-3 bg-base-100 rounded-lg border border-base-300 text-sm flex justify-between items-center">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" class="checkbox checkbox-xs" />
                                            <span class="font-medium">{{ $sub->title }}</span>
                                        </div>
                                        <span class="badge badge-ghost badge-xs">{{ $sub->kanbanColumn->name ?? 'Todo' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-base-200 p-4 rounded-xl border border-base-300">
                            <h3 class="font-bold text-sm mb-4 text-primary">Activity & History</h3>
                            <ul class="timeline timeline-vertical timeline-compact text-xs">
                                @forelse($task->activityLogs as $log)
                                    <li>
                                        <div class="timeline-middle">
                                            <div class="w-3 h-3 bg-primary rounded-full"></div>
                                        </div>
                                        <div class="timeline-end timeline-box bg-base-100 p-2 my-1 shadow-xs border border-base-300">
                                            <div class="font-semibold text-secondary">{{ $log->description }}</div>
                                            <div class="text-[10px] opacity-60">
                                                By {{ $log->pilot->name ?? 'System/Bot' }} • {{ $log->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                        <hr />
                                    </li>
                                @empty
                                    <li class="opacity-50 italic">No activity recorded yet.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-base-200 p-4 rounded-xl border border-base-300 space-y-3">
                            <h3 class="font-bold text-sm text-primary">Assignees</h3>
                            <div class="space-y-2">
                                @foreach($pilots as $p)
                                    <label class="flex items-center gap-2 text-xs cursor-pointer">
                                        <input type="checkbox" value="{{ $p->id }}" wire:model="selectedAssignees" wire:change="updateAssignees" class="checkbox checkbox-xs checkbox-primary" />
                                        <span>{{ $p->name }} ({{ $p->callsign }})</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-base-200 p-4 rounded-xl border border-base-300 space-y-3">
                            <h3 class="font-bold text-sm text-primary">Resolution User</h3>
                            <p class="text-[11px] opacity-70">User whom this task is solving something for.</p>
                            <select wire:model="selectedResolutionUserId" wire:change="updateResolutionUser" class="select select-bordered select-xs w-full">
                                <option value="">None</option>
                                @foreach($pilots as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if($customFields->count() > 0)
                            <div class="bg-base-200 p-4 rounded-xl border border-base-300 space-y-3">
                                <h3 class="font-bold text-sm text-primary">Custom Fields</h3>
                                <form wire:submit.prevent="saveCustomFields" class="space-y-3">
                                    @foreach($customFields as $field)
                                        <div>
                                            <label class="label-text text-xs font-semibold">{{ $field->name }}</label>
                                            <input type="text" wire:model="customFieldValues.{{ $field->id }}" class="input input-xs input-bordered w-full mt-1" />
                                        </div>
                                    @endforeach
                                    <button type="submit" class="btn btn-xs btn-primary w-full">Save Fields</button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modal-action border-t border-base-200 pt-3">
                    <button wire:click="closeModal" class="btn btn-sm">Close</button>
                </div>
            </div>
        </dialog>
    @endif
</div>
