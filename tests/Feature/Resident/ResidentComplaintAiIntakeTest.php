<?php

namespace Tests\Feature\Resident;

use App\Models\CaseType;
use App\Models\ComplaintIntake;
use App\Models\FiscalYear;
use App\Models\Permission;
use App\Models\Purok;
use App\Models\User;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResidentComplaintAiIntakeTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected CaseType $caseType;

    protected Purok $purok;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RequiredDataSeeder::class);

        FiscalYear::firstOrCreate(['year' => 2026], [
            'label' => 'FY 2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'status' => 'active',
        ]);

        $this->caseType = CaseType::firstOrCreate(
            ['slug' => 'neighborhood-dispute'],
            [
                'name' => 'Neighborhood Dispute',
                'code' => 'ND',
                'is_active' => true,
            ]
        );

        $this->purok = Purok::firstOrCreate(['name' => 'Purok 1']);

        $this->staffUser = User::factory()->staff()->create([
            'is_active' => true,
        ]);

        $permissions = Permission::whereIn('slug', ['cases.view', 'cases.create', 'cases.edit'])->get();
        $this->staffUser->permissions()->sync($permissions);

        Config::set('services.gemini.key', 'fake-gemini-key');
        Config::set('services.gemini.model', 'gemini-2.5-flash');
    }

    public function test_resident_can_submit_complaint_with_natural_language_narratives(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'what_happened' => 'Boundary wall encroachment and drainage obstruction.',
                                        'who_involved' => 'Pedro Santos and neighbors',
                                        'incident_datetime' => '2026-11-20 14:00',
                                        'incident_location' => 'Sitio Lower Calarian',
                                        'circumstances' => 'Heavy rainfall caused water overflow due to newly erected fence.',
                                        'rights_violated' => 'Property boundary and water drainage rights violated.',
                                        'relief_requested' => 'Demolition of obstruction and amicable settlement.',
                                        'suggested_category' => 'Neighborhood Dispute',
                                        'detected_language' => 'English / Tagalog',
                                        'confidence' => 0.92,
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
                'modelVersion' => 'gemini-2.5-flash',
            ], 200),
        ]);

        $response = $this->post(route('complaint.store'), [
            'complainant_name' => 'Juan Dela Cruz',
            'complainant_alias' => 'Johnny',
            'complainant_contact' => '09171234567',
            'complainant_address' => 'Sitio Lower Calarian, Zamboanga City',
            'complainant_residency_claim' => 'resident',
            'respondent_name' => 'Pedro Santos',
            'respondent_alias' => 'Pete',
            'respondent_address' => 'Sitio Lower Calarian, near basketball court',
            'respondent_contact' => '09187654321',
            'incident_narrative' => 'Last Thursday afternoon around 2 PM, my neighbor Pedro built a concrete fence that encroached our boundary and blocked the communal canal.',
            'rights_and_relief' => 'This violates our easement rights and damages my lawn. I want him to remove the concrete block and restore the natural canal line.',
            'case_type_suggestion' => 'Neighborhood Dispute',
            'consent' => '1',
        ]);

        $response->assertRedirect(route('complaint.received'));

        $this->assertDatabaseHas('complaint_intakes', [
            'complainant_name' => 'Juan Dela Cruz',
            'respondent_name' => 'Pedro Santos',
            'status' => 'pending',
            'ai_status' => 'done',
            'detected_language' => 'English / Tagalog',
        ]);

        $intake = ComplaintIntake::first();
        $this->assertEquals('Last Thursday afternoon around 2 PM, my neighbor Pedro built a concrete fence that encroached our boundary and blocked the communal canal.', $intake->incident_narrative);
        $this->assertNotNull($intake->ai_suggestions);
        $this->assertEquals('ai', $intake->field_provenance['what_happened']);
    }

    public function test_pii_is_strictly_omitted_from_gemini_payload(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'what_happened' => 'Summary of noise complaint.',
                                        'who_involved' => 'Witnesses',
                                        'incident_datetime' => 'Not specified in narrative',
                                        'incident_location' => 'Barangay Calarian',
                                        'circumstances' => 'Videoke at late hours.',
                                        'rights_violated' => 'Right to peaceful living.',
                                        'relief_requested' => 'Lower volume.',
                                        'suggested_category' => 'Neighborhood Dispute',
                                        'detected_language' => 'English',
                                        'confidence' => 0.88,
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->post(route('complaint.store'), [
            'complainant_name' => 'SECRET_COMPLAINANT_NAME_XYZ',
            'complainant_contact' => 'SECRET_PHONE_9999999',
            'complainant_address' => 'SECRET_HOME_ADDRESS_123',
            'respondent_name' => 'SECRET_RESPONDENT_NAME_ABC',
            'respondent_address' => 'SECRET_RESPONDENT_STREET_456',
            'incident_narrative' => 'There has been excessive loud videoke disturbance past midnight every Friday.',
            'rights_and_relief' => 'Our peace and rest is violated. We want an agreement to observe quiet hours.',
            'consent' => '1',
        ]);

        // STRICT RA 10173 AUDIT: verify that no PII strings were sent to Gemini
        Http::assertSent(function (Request $request) {
            $body = $request->body();

            $this->assertStringNotContainsString('SECRET_COMPLAINANT_NAME_XYZ', $body);
            $this->assertStringNotContainsString('SECRET_PHONE_9999999', $body);
            $this->assertStringNotContainsString('SECRET_HOME_ADDRESS_123', $body);
            $this->assertStringNotContainsString('SECRET_RESPONDENT_NAME_ABC', $body);
            $this->assertStringNotContainsString('SECRET_RESPONDENT_STREET_456', $body);

            // Raw narrative must be present
            $this->assertStringContainsString('excessive loud videoke disturbance', $body);

            return true;
        });
    }

    public function test_ai_failure_is_non_blocking_and_marks_status_failed(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(null, 500),
        ]);

        $response = $this->post(route('complaint.store'), [
            'complainant_name' => 'Maria Santos',
            'complainant_contact' => '09171234567',
            'complainant_address' => 'Upper Calarian',
            'respondent_name' => 'Juan Luna',
            'respondent_address' => 'Lower Calarian',
            'incident_narrative' => 'Dispute regarding unpaid repayment of motorcycle loan from last month.',
            'rights_and_relief' => 'Failure to honor signed promissory note. Requesting full payment or settlement schedule.',
            'consent' => '1',
        ]);

        $response->assertRedirect(route('complaint.received'));

        $this->assertDatabaseHas('complaint_intakes', [
            'complainant_name' => 'Maria Santos',
            'ai_status' => 'failed',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_view_intake_review_page(): void
    {
        $intake = ComplaintIntake::create([
            'complainant_name' => 'Rosa Parks',
            'complainant_contact' => '09171234567',
            'complainant_address' => 'Calarian Purok 2',
            'respondent_name' => 'James Blake',
            'respondent_address' => 'Calarian Purok 3',
            'incident_narrative' => 'Disagreement regarding passenger queueing at the tricycle terminal.',
            'rights_and_relief' => 'Violation of fair turn rotation. Requesting terminal guidelines enforcement.',
            'what_happened' => 'Queue jumping and heated argument.',
            'status' => 'pending',
            'ai_status' => 'done',
            'ai_confidence' => '0.85',
            'detected_language' => 'English',
            'ai_suggestions' => [
                'what_happened' => 'Queue jumping argument at terminal.',
                'who_involved' => 'Tricycle operators',
                'incident_datetime' => 'Yesterday morning',
                'incident_location' => 'Calarian Tricycle Terminal',
                'circumstances' => 'Skipped turn in line.',
                'rights_violated' => 'Queue rotation rights.',
                'relief_requested' => 'Follow terminal order.',
                'suggested_category' => 'Neighborhood Dispute',
                'detected_language' => 'English',
                'confidence' => 0.85,
            ],
            'confidentiality_screen_passed' => true,
            'consent_at' => now(),
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get(route('admin.cases.intakes.show', $intake));

        $response->assertOk();
        $response->assertSee('Intake CMP-00001');
        $response->assertSee('Rosa Parks');
        $response->assertSee('James Blake');
        $response->assertSee('AI-Assisted Intake Review');
        $response->assertSee('Katarungang Pambarangay (KP) Eligibility Screening');
        $response->assertSee('Confirm and Docket Case');
    }

    public function test_admin_can_docket_intake_into_official_case(): void
    {
        $intake = ComplaintIntake::create([
            'complainant_name' => 'Ana Rizal',
            'complainant_contact' => '09170001111',
            'complainant_address' => 'Calarian',
            'complainant_residency_claim' => 'resident',
            'respondent_name' => 'Jose Mercado',
            'respondent_address' => 'Calarian',
            'incident_narrative' => 'Boundary wall encroachment onto flower garden.',
            'rights_and_relief' => 'Property boundary encroached. Demolition requested.',
            'what_happened' => 'Encroachment onto garden.',
            'status' => 'pending',
            'ai_status' => 'done',
            'confidentiality_screen_passed' => true,
            'consent_at' => now(),
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->staffUser)
            ->post(route('admin.cases.intakes.docket', $intake), [
                'case_type_id' => $this->caseType->id,
                'purok_id' => $this->purok->id,
                'assigned_to' => $this->staffUser->id,
                'what_happened' => 'Encroachment of concrete wall onto complainant property.',
                'who_involved' => 'Ana Rizal, Jose Mercado',
                'when_happened' => now()->subDays(3)->format('Y-m-d H:i:s'),
                'where_happened' => 'Purok 1, Calarian',
                'why_how_happened' => 'Construction without proper survey.',
                'rights_violated' => 'Civil property rights under Civil Code.',
                'relief_requested' => 'Adjustment of boundary wall.',
                'field_provenance' => [
                    'what_happened' => 'staff_edited',
                    'where_happened' => 'ai',
                ],
                'kp_eligibility' => [
                    'same_barangay' => '1',
                    'kp_matter' => '1',
                    'non_confidential' => '1',
                    'residency_verified' => '1',
                ],
            ]);

        $intake->refresh();
        $this->assertEquals('docketed', $intake->status);
        $this->assertNotNull($intake->case_id);
        $this->assertEquals($this->staffUser->id, $intake->reviewed_by);

        $this->assertDatabaseHas('cases', [
            'id' => $intake->case_id,
            'source' => 'complaint_intake',
            'case_type_id' => $this->caseType->id,
            'status' => 'filed',
        ]);

        $response->assertRedirect(route('admin.cases.show', $intake->case_id));
    }

    public function test_admin_can_docket_intake_with_unspecified_datetime_string(): void
    {
        $intake = ComplaintIntake::create([
            'complainant_name' => 'Maria Makiling',
            'complainant_contact' => '09170002222',
            'complainant_address' => 'Calarian',
            'respondent_name' => 'Bernardo Carpio',
            'respondent_address' => 'Calarian',
            'incident_narrative' => 'Dispute regarding noise disturbance late at night.',
            'rights_and_relief' => 'Quiet living violated. Stop loud karaoke.',
            'what_happened' => 'Late night noise.',
            'status' => 'pending',
            'ai_status' => 'done',
            'confidentiality_screen_passed' => true,
            'consent_at' => now(),
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->staffUser)
            ->post(route('admin.cases.intakes.docket', $intake), [
                'case_type_id' => $this->caseType->id,
                'what_happened' => 'Late night karaoke noise disturbance.',
                'when_happened' => 'Not specified in narrative',
                'where_happened' => 'Not specified in narrative',
                'why_how_happened' => 'Neighbor sings videoke.',
                'rights_violated' => 'Right to rest.',
                'relief_requested' => 'Lower volume.',
                'kp_eligibility' => [
                    'same_barangay' => '1',
                    'kp_matter' => '1',
                    'non_confidential' => '1',
                    'residency_verified' => '1',
                ],
            ]);

        $intake->refresh();
        $this->assertEquals('docketed', $intake->status);
        $this->assertNotNull($intake->case_id);
        $response->assertRedirect(route('admin.cases.show', $intake->case_id));
        $response->assertSessionHas('success');
    }

    public function test_docketing_fails_if_kp_eligibility_checklist_is_incomplete(): void
    {
        $intake = ComplaintIntake::create([
            'complainant_name' => 'Ana Rizal',
            'complainant_contact' => '09170001111',
            'complainant_address' => 'Calarian',
            'respondent_name' => 'Jose Mercado',
            'respondent_address' => 'Calarian',
            'incident_narrative' => 'Dispute narrative sample.',
            'rights_and_relief' => 'Rights and relief sample.',
            'status' => 'pending',
            'confidentiality_screen_passed' => true,
            'consent_at' => now(),
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->staffUser)
            ->post(route('admin.cases.intakes.docket', $intake), [
                'case_type_id' => $this->caseType->id,
                'what_happened' => 'Encroachment of concrete wall.',
                'kp_eligibility' => [
                    'same_barangay' => '1',
                    // Missing kp_matter, non_confidential, residency_verified
                ],
            ]);

        $response->assertSessionHasErrors(['kp_eligibility.kp_matter', 'kp_eligibility.non_confidential', 'kp_eligibility.residency_verified']);
        $this->assertEquals('pending', $intake->fresh()->status);
    }
}
