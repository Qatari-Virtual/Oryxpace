<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Department;
use App\Models\KanbanColumn;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAndPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_recursive_subtasks_eager_loading_and_n_plus_one_prevention(): void
    {
        $dept = Department::create(['name' => 'Operations', 'slug' => 'operations']);
        $project = Project::create(['department_id' => $dept->id, 'name' => 'Flight Operations']);
        $col = KanbanColumn::create(['project_id' => $project->id, 'name' => 'Todo', 'slug' => 'todo', 'is_default' => true]);

        $parent = Task::create([
            'project_id' => $project->id,
            'kanban_column_id' => $col->id,
            'title' => 'Parent Task',
        ]);

        $subtask1 = Task::create([
            'project_id' => $project->id,
            'parent_id' => $parent->id,
            'kanban_column_id' => $col->id,
            'title' => 'Subtask Level 1',
        ]);

        $subtask2 = Task::create([
            'project_id' => $project->id,
            'parent_id' => $subtask1->id,
            'kanban_column_id' => $col->id,
            'title' => 'Subtask Level 2',
        ]);

        $fetched = Task::withStandardRelations()->find($parent->id);

        $this->assertEquals(1, $fetched->subtasks->count());
        $this->assertEquals('Subtask Level 1', $fetched->subtasks->first()->title);
        $this->assertEquals('Subtask Level 2', $fetched->subtasks->first()->subtasksRecursive->first()->title);
    }

    public function test_api_key_middleware_and_task_endpoints(): void
    {
        $apiKey = ApiKey::create([
            'name' => 'Crew Center',
            'key' => 'test-secret-key-12345',
        ]);

        $dept = Department::create(['name' => 'Dev', 'slug' => 'dev']);
        $project = Project::create(['department_id' => $dept->id, 'name' => 'Web App']);
        $col = KanbanColumn::create(['project_id' => $project->id, 'name' => 'Backlog', 'slug' => 'backlog', 'is_default' => true]);

        // Unauthorized request without API key
        $this->postJson('/tasks', ['project_id' => $project->id, 'title' => 'Unauth Task'])
            ->assertStatus(401);

        // Authorized request
        $response = $this->withHeader('X-API-Key', 'test-secret-key-12345')
            ->postJson('/tasks', [
                'project_id' => $project->id,
                'title' => 'Api Created Task',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Api Created Task');
    }

    public function test_pipeline_controller_application_and_recruitment_webhooks(): void
    {
        ApiKey::create(['name' => 'Oryxie Bot', 'key' => 'oryxie-key-67890']);

        // Application webhook
        $appResp = $this->withHeader('X-API-Key', 'oryxie-key-67890')
            ->postJson('/pipelines/application', [
                'applicant_name' => 'John Doe',
                'applicant_email' => 'john@example.com',
                'ifc_user' => 'JohnDoe_IFC',
            ]);

        $appResp->assertStatus(201)
            ->assertJsonPath('success', true);

        // Recruitment webhook
        $recResp = $this->withHeader('X-API-Key', 'oryxie-key-67890')
            ->postJson('/pipelines/recruitment', [
                'discord_user_id' => '123456789',
                'discord_username' => 'JohnDiscord',
                'discord_channel_id' => '987654321',
            ]);

        $recResp->assertStatus(201)
            ->assertJsonPath('success', true);
    }
}
