<?php

namespace Tests\Feature;

use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DepartmentIconTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_can_be_created_with_custom_icon_upload(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('dept_icon.png', 100, 'image/png');

        Livewire::test('department-project-list')
            ->set('newDepartmentName', 'Flight Operations')
            ->set('newDepartmentIcon', $file)
            ->call('createDepartment')
            ->assertHasNoErrors();

        $dept = Department::where('name', 'Flight Operations')->first();
        $this->assertNotNull($dept);

        $this->assertNotNull($dept->icon_url);
        $this->assertStringContainsString('/storage/departments/', $dept->icon_url);
    }

    public function test_department_icon_can_be_updated_and_removed(): void
    {
        Storage::fake('public');

        $dept = Department::create([
            'name' => 'Safety & Compliance',
            'slug' => 'safety-compliance',
        ]);

        $this->assertNull($dept->icon_url);

        $file = UploadedFile::fake()->create('safety.png', 100, 'image/png');

        Livewire::test('department-project-list')
            ->call('openEditDepartment', $dept->id)
            ->set('editingDepartmentName', 'Safety & Quality')
            ->set('editingDepartmentIcon', $file)
            ->call('updateDepartment')
            ->assertHasNoErrors();

        $updatedDept = $dept->fresh();
        $this->assertEquals('Safety & Quality', $updatedDept->name);
        $this->assertNotNull($updatedDept->icon_url);

        Livewire::test('department-project-list')
            ->call('removeIcon', $dept->id);

        $this->assertNull($dept->fresh()->icon_url);
    }
}
