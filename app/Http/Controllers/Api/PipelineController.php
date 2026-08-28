<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PipelineController extends Controller
{
    private function getOrCreateProject(string $projectName): Project
    {
        $defaultDept = Department::firstOrCreate(
            ['slug' => 'general'],
            ['name' => 'General']
        );

        return Project::firstOrCreate(
            ['name' => $projectName],
            ['department_id' => $defaultDept->id, 'description' => "Auto-created project for {$projectName}"]
        );
    }

    private function getDefaultColumn(Project $project, string $preferredSlug = 'in-progress'): KanbanColumn
    {
        $col = KanbanColumn::where('project_id', $project->id)
            ->where(function ($q) use ($preferredSlug) {
                $q->where('slug', $preferredSlug)->orWhere('is_default', true);
            })->first();

        if (!$col) {
            $col = KanbanColumn::create([
                'project_id' => $project->id,
                'name' => ucfirst(str_replace('-', ' ', $preferredSlug)),
                'slug' => $preferredSlug,
                'order' => 1,
                'is_default' => true,
            ]);
        }

        return $col;
    }

    public function handleApplication(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'applicant_name' => 'required|string',
            'applicant_email' => 'required|email',
            'resolution_user_id' => 'nullable|integer',
            'ifc_user' => 'nullable|string',
        ]);

        $project = $this->getOrCreateProject('Applications');
        $column = $this->getDefaultColumn($project, 'new');

        $task = Task::create([
            'project_id' => $project->id,
            'kanban_column_id' => $column->id,
            'title' => "Application: {$validated['applicant_name']}",
            'description' => "New application submitted for {$validated['applicant_email']}. IFC: " . ($validated['ifc_user'] ?? 'N/A'),
            'resolution_user_id' => $validated['resolution_user_id'] ?? null,
        ]);

        $task->logActivity(null, 'pipeline_trigger', 'Created from Crew Center Application pipeline.');

        return response()->json(['success' => true, 'task' => $task->loadStandardRelations()], 201);
    }

    public function handleRecruitment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'discord_user_id' => 'required|string',
            'discord_username' => 'required|string',
            'discord_channel_id' => 'required|string',
        ]);

        $project = $this->getOrCreateProject('Recruitment & Training');
        $column = $this->getDefaultColumn($project, 'recruitment');

        $task = Task::create([
            'project_id' => $project->id,
            'kanban_column_id' => $column->id,
            'title' => "Recruitment: {$validated['discord_username']}",
            'description' => "Recruitment channel created: <#{$validated['discord_channel_id']}>. Discord User ID: {$validated['discord_user_id']}",
        ]);

        $task->logActivity(null, 'pipeline_trigger', 'Recruitment pipeline initiated by Oryxie Discord bot.');

        return response()->json(['success' => true, 'task' => $task->loadStandardRelations()], 201);
    }

    public function handleWrittenTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:oryxpace_tasks,id',
            'passed' => 'required|boolean',
            'score' => 'nullable|numeric',
        ]);

        $task = Task::findOrFail($validated['task_id']);
        $statusText = $validated['passed'] ? 'PASSED' : 'FAILED';
        $scoreText = isset($validated['score']) ? " Score: {$validated['score']}%" : '';

        $task->logActivity(null, 'written_test_result', "Written test {$statusText}.{$scoreText}");

        if ($validated['passed']) {
            $nextColumn = KanbanColumn::where('project_id', $task->project_id)
                ->where('slug', 'checkride')
                ->first();

            if ($nextColumn) {
                $task->update(['kanban_column_id' => $nextColumn->id]);
            }

            Task::create([
                'project_id' => $task->project_id,
                'parent_id' => $task->id,
                'title' => 'Send CR1 Message to Candidate',
                'description' => 'Written test passed. Send CR1 instructions.',
            ]);
        }

        return response()->json(['success' => true, 'task' => $task->fresh()->loadStandardRelations()]);
    }

    public function handleReplayReview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_id' => 'nullable|exists:oryxpace_tasks,id',
            'pilot_name' => 'required|string',
            'replay_url' => 'required|url',
            'checkride_num' => 'nullable|integer',
        ]);

        $project = $this->getOrCreateProject('Recruitment & Training');
        $column = $this->getDefaultColumn($project, 'checkride');

        $crNum = $validated['checkride_num'] ?? 1;

        if (!empty($validated['task_id'])) {
            $parentTask = Task::findOrFail($validated['task_id']);
            $task = Task::create([
                'project_id' => $project->id,
                'parent_id' => $parentTask->id,
                'kanban_column_id' => $column->id,
                'title' => "Replay Review CR{$crNum}: {$validated['pilot_name']}",
                'description' => "Replay submitted in CC: {$validated['replay_url']}",
            ]);
        } else {
            $task = Task::create([
                'project_id' => $project->id,
                'kanban_column_id' => $column->id,
                'title' => "Replay Review CR{$crNum}: {$validated['pilot_name']}",
                'description' => "Replay submitted in CC: {$validated['replay_url']}",
            ]);
        }

        $task->logActivity(null, 'replay_submitted', "Replay review task created for CR{$crNum}.");

        return response()->json(['success' => true, 'task' => $task->loadStandardRelations()], 201);
    }

    public function handleDiscordTicket(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticket_id' => 'required|string',
            'action' => 'required|in:opened,closed',
            'creator_name' => 'required|string',
            'channel_name' => 'required|string',
        ]);

        $project = $this->getOrCreateProject('Discord Tickets');

        if ($validated['action'] === 'opened') {
            $col = $this->getDefaultColumn($project, 'open');
            $task = Task::create([
                'project_id' => $project->id,
                'kanban_column_id' => $col->id,
                'title' => "Ticket #{$validated['ticket_id']}: {$validated['channel_name']}",
                'description' => "Ticket opened by {$validated['creator_name']} in channel {$validated['channel_name']}.",
            ]);
            $task->logActivity(null, 'ticket_opened', "Discord ticket opened.");
        } else {
            $col = $this->getDefaultColumn($project, 'closed');
            $task = Task::where('project_id', $project->id)
                ->where('title', 'like', "%Ticket #{$validated['ticket_id']}%")
                ->first();

            if ($task) {
                $task->update(['kanban_column_id' => $col->id]);
                $task->logActivity(null, 'ticket_closed', "Discord ticket closed via Oryxie.");
            }
        }

        return response()->json(['success' => true]);
    }

    public function handlePirepReview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pirep_id' => 'required|integer',
            'pilot_id' => 'required|integer',
            'flightnum' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        $project = $this->getOrCreateProject('Pirep Reviews');
        $column = $this->getDefaultColumn($project, 'pending-review');

        $task = Task::create([
            'project_id' => $project->id,
            'kanban_column_id' => $column->id,
            'title' => "Pirep Review: Flight {$validated['flightnum']} (PIREP #{$validated['pirep_id']})",
            'description' => "Flagged by Oryxie for review. Reason: " . ($validated['reason'] ?? 'Flagged automatically'),
            'resolution_user_id' => $validated['pilot_id'],
        ]);

        $task->logActivity(null, 'pirep_flagged', 'Pirep review task created from CC/Oryxie pipeline.');

        return response()->json(['success' => true, 'task' => $task->loadStandardRelations()], 201);
    }
}
