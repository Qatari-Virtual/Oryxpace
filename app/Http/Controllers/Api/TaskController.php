<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KanbanColumn;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Task::withStandardRelations();

        if ($request->has('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->has('resolution_user_id')) {
            $query->where('resolution_user_id', $request->input('resolution_user_id'));
        }

        $tasks = $query->whereNull('parent_id')->get();

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:oryxpace_projects,id',
            'parent_id' => 'nullable|exists:oryxpace_tasks,id',
            'kanban_column_id' => 'nullable|exists:oryxpace_kanban_columns,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'resolution_user_id' => 'nullable|integer',
            'created_by_pilot_id' => 'nullable|integer',
            'due_date' => 'nullable|date',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'integer',
        ]);

        if (empty($validated['kanban_column_id'])) {
            $defaultCol = KanbanColumn::where('project_id', $validated['project_id'])
                ->where('is_default', true)
                ->first();

            $validated['kanban_column_id'] = $defaultCol ? $defaultCol->id : null;
        }

        $task = Task::create($validated);

        if (!empty($validated['assignee_ids'])) {
            $task->assignees()->sync($validated['assignee_ids']);
        }

        $task->logActivity(
            $validated['created_by_pilot_id'] ?? null,
            'created',
            "Task '{$task->title}' created via API."
        );

        return response()->json([
            'success' => true,
            'data' => $task->loadStandardRelations()
        ], 201);
    }

    public function show(Task $task): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $task->loadStandardRelations()
        ]);
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'kanban_column_id' => 'required|exists:oryxpace_kanban_columns,id',
            'pilot_id' => 'nullable|integer',
        ]);

        $oldColumn = $task->kanbanColumn ? $task->kanbanColumn->name : 'None';
        $newColumnRecord = KanbanColumn::findOrFail($validated['kanban_column_id']);

        $task->update(['kanban_column_id' => $newColumnRecord->id]);

        $task->logActivity(
            $validated['pilot_id'] ?? null,
            'status_changed',
            "Status changed from '{$oldColumn}' to '{$newColumnRecord->name}'.",
            $oldColumn,
            $newColumnRecord->name
        );

        return response()->json([
            'success' => true,
            'data' => $task->fresh()->loadStandardRelations()
        ]);
    }
}
