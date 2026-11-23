<?php

namespace Tests\Feature\CaseActions;

use App\Models\EscalationDestination;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EscalationDestinationTest extends TestCase
{
    use RefreshDatabase;

    public function test_escalation_destinations_crud(): void
    {
        $admin = User::factory()->admin()->create();
        $office = Office::create([
            'name' => 'PNP Station 8',
            'type' => 'external',
        ]);

        // 1. Index
        $this->actingAs($admin)
            ->get(route('admin.escalation-destinations.index'))
            ->assertOk()
            ->assertSee('Escalation Destinations');

        // 2. Create form
        $this->actingAs($admin)
            ->get(route('admin.escalation-destinations.create'))
            ->assertOk()
            ->assertSee('Add Escalation Destination');

        // 3. Store
        $storeResponse = $this->actingAs($admin)
            ->post(route('admin.escalation-destinations.store'), [
                'name' => 'MTCC Branch 3',
                'office_id' => $office->id,
                'sort_order' => 1,
                'is_active' => '1',
            ]);

        $storeResponse->assertRedirect(route('admin.escalation-destinations.index'));
        $this->assertDatabaseHas('escalation_destinations', [
            'name' => 'MTCC Branch 3',
            'office_id' => $office->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $destination = EscalationDestination::where('name', 'MTCC Branch 3')->firstOrFail();

        // 4. Edit form
        $this->actingAs($admin)
            ->get(route('admin.escalation-destinations.edit', $destination))
            ->assertOk()
            ->assertSee('MTCC Branch 3');

        // 5. Update
        $updateResponse = $this->actingAs($admin)
            ->put(route('admin.escalation-destinations.update', $destination), [
                'name' => 'MTCC Branch 3 - Updated',
                'office_id' => null,
                'sort_order' => 2,
                'is_active' => '0',
            ]);

        $updateResponse->assertRedirect(route('admin.escalation-destinations.index'));
        $this->assertDatabaseHas('escalation_destinations', [
            'id' => $destination->id,
            'name' => 'MTCC Branch 3 - Updated',
            'is_active' => false,
        ]);

        // 6. Destroy
        $deleteResponse = $this->actingAs($admin)
            ->delete(route('admin.escalation-destinations.destroy', $destination));

        $deleteResponse->assertRedirect(route('admin.escalation-destinations.index'));
        $this->assertDatabaseMissing('escalation_destinations', [
            'id' => $destination->id,
        ]);
    }
}
