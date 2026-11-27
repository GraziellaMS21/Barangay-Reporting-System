<?php

namespace Tests\Feature\Resident;

use App\Models\Category;
use App\Models\FiscalYear;
use App\Models\Purok;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentEntryScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FiscalYear::firstOrCreate(['year' => 2026], [
            'label' => 'FY 2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'status' => 'active',
        ]);

        Purok::create(['name' => 'Purok 1']);
        Category::create([
            'name' => 'Streetlight',
            'slug' => 'streetlight',
            'is_active' => true,
        ]);
    }

    public function test_root_url_loads_four_button_portal_page(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertViewIs('resident.index')
            ->assertSeeText('Emergency / 911')
            ->assertSeeText('Report a Community Problem')
            ->assertSeeText('File a Complaint')
            ->assertSeeText('Track a Report')
            ->assertSee(route('reports.create'))
            ->assertSee(route('complaint.create'))
            ->assertSee(route('track.index'));
    }

    public function test_resident_landing_screen_displays_three_action_pathways(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        // 1. Emergency action
        $response->assertSee('Emergency / 911');
        // 2. Community problem report action
        $response->assertSee('Report a Community Problem');
        // 3. Dispute complaint action
        $response->assertSee('File a Complaint');
        $response->assertSee(route('complaint.create'));
        // Track report action
        $response->assertSee(route('track.index'));
    }

    public function test_resident_landing_screen_displays_wrong_door_guidance_notice(): void
    {
        $response = $this->get(route('reports.create'));

        $response->assertOk();
        $response->assertSee('Is this a dispute with a specific person?');
        $response->assertSee('File a Dispute Complaint');
    }

    public function test_guest_can_submit_community_problem_without_login(): void
    {
        $purok = Purok::first();
        $category = Category::first();

        $response = $this->post(route('reports.store'), [
            'description' => 'Broken streetlight near the basketball court in Purok 1.',
            'category_id' => $category->id,
            'purok_id' => $purok->id,
            'consent' => '1',
            'has_consent_field' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reports', [
            'description' => 'Broken streetlight near the basketball court in Purok 1.',
            'purok_id' => $purok->id,
            'status' => 'submitted',
        ]);
    }

    public function test_anonymous_community_problem_submission_drops_contact_details(): void
    {
        $purok = Purok::first();
        $category = Category::first();

        $response = $this->post(route('reports.store'), [
            'description' => 'Garbage overflow behind the community hall in Purok 1.',
            'category_id' => $category->id,
            'purok_id' => $purok->id,
            'is_anonymous' => '1',
            'reporter_name' => 'Secret Citizen',
            'reporter_contact' => '09171234567',
            'reporter_email' => 'citizen@example.com',
            'consent' => '1',
            'has_consent_field' => '1',
        ]);

        $response->assertRedirect();

        $report = Report::where('description', 'Garbage overflow behind the community hall in Purok 1.')->first();
        $this->assertNotNull($report);
        $this->assertTrue((bool) $report->is_anonymous);
        $this->assertNull($report->reporter_name);
        $this->assertNull($report->reporter_contact);
        $this->assertNull($report->reporter_email);
    }
}
