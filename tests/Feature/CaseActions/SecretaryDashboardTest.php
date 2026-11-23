<?php

namespace Tests\Feature\CaseActions;

use App\Models\BarangayCase;
use App\Models\Hearing;
use App\Models\Summons;
use App\Models\User;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretaryDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_dashboard_displays_operational_metrics_and_lists(): void
    {
        $admin = User::factory()->admin()->create();

        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
            'filed_on' => now()->subDays(20)->toDateString(), // will trigger overdue
        ]);

        Hearing::factory()->create([
            'case_id' => $case->id,
            'scheduled_at' => now(), // today
        ]);

        Summons::factory()->create([
            'case_id' => $case->id,
            'service_result' => 'pending',
            'voided_at' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.secretary'));

        $response->assertOk()
            ->assertSee('Secretary Dashboard')
            ->assertSee("Today's Hearings", false)
            ->assertSee('Awaiting Service')
            ->assertSee('Overdue Cases');
    }
}
