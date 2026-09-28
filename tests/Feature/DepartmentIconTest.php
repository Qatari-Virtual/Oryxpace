<?php

namespace Tests\Feature;

use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DepartmentIconTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_can_be_created_with_icon(): void
    {
        Livewire::test('department-project-list')
            ->set('newDepartmentName', 'Flight Operations')
            ->set('newDepartmentIcon', 'ri-flight-land-fill')
            ->call('createDepartment')
            ->assertHasNoErrors();

        $dept = Department::where('name', 'Flight Operations')->first();
        $this->assertNotNull($dept);
        $this->assertEquals('ri-flight-land-fill', $dept->icon);
    }

    public function test_department_icon_can_be_updated_and_removed(): void
    {
        $dept = Department::create([
            'name' => 'Safety & Compliance',
            'slug' => 'safety-compliance',
        ]);

        $this->assertNull($dept->icon);

        Livewire::test('department-project-list')
            ->call('openEditDepartment', $dept->id)
            ->set('editingDepartmentName', 'Safety & Quality')
            ->set('editingDepartmentIcon', 'ri-scales-3-fill')
            ->call('updateDepartment')
            ->assertHasNoErrors();

        $updatedDept = $dept->fresh();
        $this->assertEquals('Safety & Quality', $updatedDept->name);
        $this->assertEquals('ri-scales-3-fill', $updatedDept->icon);

        Livewire::test('department-project-list')
            ->call('removeIcon', $dept->id);

        $this->assertNull($dept->fresh()->icon);
    }

    public function test_edit_department_modal_stays_open_across_renders(): void
    {
        $dept = Department::create([
            'name' => 'Crew Center',
            'slug' => 'crew-center',
        ]);

        Livewire::test('department-project-list')
            ->call('openEditDepartment', $dept->id)
            ->assertSet('showEditModal', true)
            ->assertSeeHtml('data-modal open')
            ->call('removeIcon', $dept->id)
            ->assertSet('showEditModal', true)
            ->assertSeeHtml('data-modal open')
            ->call('closeEditDepartment')
            ->assertSet('showEditModal', false)
            ->assertDontSeeHtml('data-modal open');
    }
}
