<?php

namespace Tests\Feature\CaseActions;

use App\Models\BarangayCase;
use App\Models\Hearing;
use App\Models\Personnel;
use App\Models\User;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HearingTest extends TestCase
{
    use RefreshDatabase;

    public function test_hearings_index_displays_hearings_list(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);
        Hearing::factory()->create([
            'case_id' => $case->id,
            'scheduled_at' => now()->addDay(),
            'venue' => 'Room A',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.hearings.index'));

        $response->assertOk()
            ->assertSee('Conciliation Hearings')
            ->assertSee('Room A');
    }

    public function test_hearing_can_be_scheduled_and_transitions_filed_case_to_in_mediation(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::FILED,
        ]);
        $personnel = Personnel::factory()->create();

        $scheduleTime = now()->addDays(2)->setTime(9, 0)->format('Y-m-d\TH:i');

        $response = $this->actingAs($admin)->post(route('admin.cases.workflow.schedule-and-issue-summons', $case), [
            'scheduled_at' => $scheduleTime,
            'venue' => 'Main Barangay Hall',
            'presided_by' => $personnel->id,
        ]);

        $response->assertRedirect(route('admin.cases.show', $case));

        $this->assertDatabaseHas('hearings', [
            'case_id' => $case->id,
            'venue' => 'Main Barangay Hall',
            'presided_by_personnel_id' => $personnel->id,
            'status' => 'scheduled',
        ]);

        $this->assertDatabaseHas('summons', [
            'case_id' => $case->id,
            'venue' => 'Main Barangay Hall',
            'sequence_no' => 1,
        ]);

        $case->refresh();
        $this->assertSame(CaseStatus::IN_MEDIATION, $case->status);
    }

    public function test_hearing_can_be_updated(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);
        $hearing = Hearing::factory()->create([
            'case_id' => $case->id,
            'scheduled_at' => now()->addDay(),
            'venue' => 'Old Room',
        ]);

        $newTime = now()->addDays(3)->format('Y-m-d H:i:s');

        $response = $this->actingAs($admin)->put(route('admin.cases.hearings.update', [$case, $hearing]), [
            'scheduled_at' => $newTime,
            'venue' => 'New Room B',
            'status' => 'scheduled',
        ]);

        $response->assertRedirect(route('admin.cases.show', ['case' => $case, 'tab' => 'hearings']));
        $this->assertDatabaseHas('hearings', [
            'id' => $hearing->id,
            'venue' => 'New Room B',
        ]);
    }

    public function test_hearing_outcome_and_proceedings_can_be_recorded(): void
    {
        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
        ]);
        $hearing = Hearing::factory()->create([
            'case_id' => $case->id,
            'status' => 'scheduled',
            'outcome_note' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.cases.hearings.outcome', [$case, $hearing]), [
            'outcome_note' => 'Both parties appeared. Respondent agreed to repair the damaged fence within 7 days.',
            'status' => 'held',
        ]);

        $response->assertRedirect(route('admin.cases.show', ['case' => $case, 'tab' => 'hearings']));
        $this->assertDatabaseHas('hearings', [
            'id' => $hearing->id,
            'status' => 'held',
            'outcome_note' => 'Both parties appeared. Respondent agreed to repair the damaged fence within 7 days.',
        ]);
    }
}
