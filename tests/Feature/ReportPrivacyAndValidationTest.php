<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Report;
use App\Models\ReportPhoto;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoReportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportPrivacyAndValidationTest extends TestCase
{
    use RefreshDatabase;

    private function staffWithReportsView(): User
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $staff->permissions()->sync([
            Permission::firstOrCreate(['slug' => 'reports.view'], ['label' => 'View Resident Reports Queue', 'group' => 'reports'])->id,
        ]);

        return $staff;
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.key' => '']);
    }

    /**
     * @return array<string, array{array<string, float>, string}>
     */
    public static function partialCoordinates(): array
    {
        return [
            'latitude without longitude' => [['lat' => 6.9214], 'lng'],
            'longitude without latitude' => [['lng' => 122.079], 'lat'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function validPhilippineContactNumbers(): array
    {
        return [
            'local format' => ['0917 123-4567', '09171234567'],
            'international format' => ['+63-917-123-4567', '+639171234567'],
        ];
    }

    #[DataProvider('partialCoordinates')]
    public function test_report_submission_requires_coordinate_pairs(
        array $coordinates,
        string $missingCoordinate
    ): void {
        $this->post(route('reports.store'), array_merge([
            'description' => 'A drainage problem needs to be fixed.',
        ], $coordinates))
            ->assertSessionHasErrors($missingCoordinate);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_report_submission_requires_a_location_method(): void
    {
        $this->seed(CategorySeeder::class);

        $this->post(route('reports.store'), [
            'category_id' => Category::query()->where('slug', 'drainage')->value('id'),
            'description' => 'A drainage problem needs to be fixed.',
        ])
            ->assertSessionHasErrors([
                'location' => 'Please set a pin on the map, choose a purok, or enter a landmark.',
            ]);

        $this->assertDatabaseCount('reports', 0);
    }

    #[DataProvider('validPhilippineContactNumbers')]
    public function test_report_submission_normalizes_optional_reporter_contact_numbers(
        string $contactNumber,
        string $normalizedNumber
    ): void {
        $this->seed(CategorySeeder::class);

        $this->from(route('reports.create'))
            ->post(route('reports.store'), [
                'category_id' => Category::query()->where('slug', 'drainage')->value('id'),
                'description' => 'A drainage problem needs to be fixed.',
                'landmark' => 'Near the community hall',
                'reporter_name' => 'Juan Dela Cruz',
                'reporter_contact' => $contactNumber,
            ])
            ->assertRedirect(route('reports.success', Report::query()->value('tracking_code')));

        $this->assertDatabaseHas('reports', [
            'reporter_name' => 'Juan Dela Cruz',
            'reporter_contact' => $normalizedNumber,
        ]);
    }

    public function test_report_submission_allows_omitting_optional_reporter_details(): void
    {
        $this->seed(CategorySeeder::class);

        $response = $this->post(route('reports.store'), [
            'category_id' => Category::query()->where('slug', 'drainage')->value('id'),
            'description' => 'A drainage problem needs to be fixed.',
            'landmark' => 'Near the community hall',
        ]);
        $report = Report::query()->firstOrFail();

        $response->assertRedirect(route('reports.success', $report->tracking_code));
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'reporter_name' => null,
            'reporter_contact' => null,
        ]);
    }

    public function test_report_submission_rejects_invalid_contact_number_and_preserves_input(): void
    {
        $this->from(route('reports.create'))
            ->post(route('reports.store'), [
                'description' => 'A drainage problem needs to be fixed.',
                'landmark' => 'Near the community hall',
                'reporter_name' => 'Juan Dela Cruz',
                'reporter_contact' => '12345',
            ])
            ->assertRedirect(route('reports.create'))
            ->assertSessionHasErrors([
                'reporter_contact' => 'Enter a Philippine mobile number, such as 09171234567 or +639171234567. Spaces and dashes are okay.',
            ]);

        $this->get(route('reports.create'))
            ->assertOk()
            ->assertSee('value="Juan Dela Cruz"', false)
            ->assertSee('value="12345"', false);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_contact_can_be_made_required_with_one_configuration_value(): void
    {
        $this->seed(CategorySeeder::class);
        config(['reports.require_contact' => true]);

        $this->post(route('reports.store'), [
            'category_id' => Category::query()->where('slug', 'drainage')->value('id'),
            'description' => 'A drainage problem needs to be fixed.',
            'landmark' => 'Near the community hall',
        ])
            ->assertSessionHasErrors([
                'reporter_contact' => 'Please enter a contact number so barangay staff can reach you.',
            ]);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_reporter_details_are_only_available_on_staff_review_pages(): void
    {
        $reporterName = 'Private Demo Reporter';
        $reporterContact = '+639170000001';
        $report = Report::factory()->create([
            'reporter_name' => $reporterName,
            'reporter_contact' => $reporterContact,
        ]);
        $staff = $this->staffWithReportsView();

        $this->actingAs($staff)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee($reporterName)
            ->assertSee($reporterContact);

        $this->get(route('admin.reports.index'))
            ->assertOk()
            ->assertDontSee($reporterName)
            ->assertDontSee($reporterContact)
            ->assertViewHas('reports', fn ($reports): bool => $reports->getCollection()->every(
                fn (Report $queueReport): bool => ! array_key_exists('reporter_name', $queueReport->getAttributes())
                    && ! array_key_exists('reporter_contact', $queueReport->getAttributes())
            ));

        $this->get(route('track.show', $report->tracking_code))
            ->assertOk()
            ->assertDontSee($reporterName)
            ->assertDontSee($reporterContact)
            ->assertViewHas('report', fn (Report $publicReport): bool => ! array_key_exists('reporter_name', $publicReport->getAttributes())
                && ! array_key_exists('reporter_contact', $publicReport->getAttributes()));

        $this->get(route('reports.success', $report->tracking_code))
            ->assertOk()
            ->assertDontSee($reporterName)
            ->assertDontSee($reporterContact);

        $this->get(route('map.index'))
            ->assertOk()
            ->assertDontSee($reporterName)
            ->assertDontSee($reporterContact)
            ->assertViewHas('reports', fn ($publicReports): bool => $publicReports->every(
                fn (Report $publicReport): bool => ! array_key_exists('reporter_name', $publicReport->getAttributes())
                    && ! array_key_exists('reporter_contact', $publicReport->getAttributes())
            ));
    }

    public function test_staff_review_omits_missing_reporter_details(): void
    {
        $staff = $this->staffWithReportsView();
        $report = Report::factory()->create([
            'reporter_name' => null,
            'reporter_contact' => null,
            'reporter_email' => null,
        ]);

        $this->actingAs($staff)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSeeText('Not stated (anonymous report)')
            ->assertDontSeeText('Name:')
            ->assertDontSeeText('Phone:')
            ->assertDontSeeText('Email:');
    }

    public function test_staff_review_shows_reporter_contact_below_name(): void
    {
        $staff = $this->staffWithReportsView();
        $report = Report::factory()->create([
            'reporter_name' => 'Juan Dela Cruz',
            'reporter_contact' => '09170000001',
            'reporter_email' => 'juan@example.test',
        ]);

        $this->actingAs($staff)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSeeText('Juan Dela Cruz')
            ->assertSeeText('09170000001')
            ->assertSeeText('juan@example.test');
    }

    public function test_staff_review_escapes_reporter_name_output(): void
    {
        $staff = $this->staffWithReportsView();
        $report = Report::factory()->create([
            'reporter_name' => "<script>alert('xss')</script>",
        ]);

        $this->actingAs($staff)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee("<script>alert('xss')</script>", false);
    }

    public function test_demo_report_seeder_includes_fake_and_unprovided_reporter_details(): void
    {
        $this->seed(DemoReportSeeder::class);

        $this->assertDatabaseHas('reports', [
            'tracking_code' => 'BRG-DEMO01',
            'reporter_name' => 'Juan Dela Cruz',
            'reporter_contact' => '09170000001',
        ]);
        $this->assertDatabaseHas('reports', [
            'tracking_code' => 'BRG-DEMO04',
            'reporter_name' => null,
            'reporter_contact' => null,
        ]);
    }

    public function test_uploaded_report_photos_are_stored_on_the_private_disk(): void
    {
        $this->seed(CategorySeeder::class);
        Storage::fake('local');
        Storage::fake('public');

        $response = $this->post(route('reports.store'), [
            'category_id' => Category::query()->where('slug', 'drainage')->value('id'),
            'description' => 'A drainage problem needs to be fixed.',
            'lat' => 6.9214,
            'lng' => 122.079,
            'photos' => [UploadedFile::fake()->image('report.png')],
        ]);
        $report = Report::query()->firstOrFail();

        $response->assertRedirect(route('reports.success', $report->tracking_code));
        Storage::disk('local')->assertExists($report->photo_path);
        Storage::disk('public')->assertMissing($report->photo_path);
        $this->assertDatabaseHas('status_logs', [
            'report_id' => $report->id,
            'to_status' => 'submitted',
            'user_id' => null,
        ]);
    }

    public function test_resident_can_submit_multiple_photos_and_staff_can_review_them(): void
    {
        $this->seed(CategorySeeder::class);
        Storage::fake('local');
        $staff = $this->staffWithReportsView();

        $response = $this->post(route('reports.store'), [
            'category_id' => Category::query()->where('slug', 'drainage')->value('id'),
            'description' => 'A drainage problem needs to be fixed.',
            'landmark' => 'Near the community hall',
            'photos' => [
                UploadedFile::fake()->image('drainage-one.jpg')->size(2000),
                UploadedFile::fake()->image('drainage-two.jpg')->size(2000),
            ],
        ]);
        $report = Report::query()->firstOrFail();
        $photos = ReportPhoto::query()->where('report_id', $report->id)->get();

        $response->assertRedirect(route('reports.success', $report->tracking_code));
        $this->assertCount(2, $photos);
        $this->assertSame($photos->first()->photo_path, $report->photo_path);

        foreach ($photos as $photo) {
            Storage::disk('local')->assertExists($photo->photo_path);
        }

        $this->get(route('admin.reports.photos.show', [$report, $photos[0]]))
            ->assertRedirect(route('login'));

        $this->actingAs($staff)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee(route('admin.reports.photos.show', [$report, $photos[0]]), false)
            ->assertSee(route('admin.reports.photos.show', [$report, $photos[1]]), false);

        $otherReport = Report::factory()->create();

        $this->get(route('admin.reports.photos.show', [$otherReport, $photos[0]]))
            ->assertNotFound();
    }

    public function test_resident_photo_upload_is_rejected_when_combined_size_exceeds_five_megabytes(): void
    {
        $this->seed(CategorySeeder::class);
        Storage::fake('local');

        $this->from(route('reports.create'))
            ->post(route('reports.store'), [
                'category_id' => Category::query()->where('slug', 'drainage')->value('id'),
                'description' => 'A drainage problem needs to be fixed.',
                'landmark' => 'Near the community hall',
                'photos' => [
                    UploadedFile::fake()->image('drainage-one.jpg')->size(3000),
                    UploadedFile::fake()->image('drainage-two.jpg')->size(3000),
                ],
            ])
            ->assertRedirect(route('reports.create'))
            ->assertSessionHasErrors([
                'photos' => 'The combined size of all photos must not exceed 5 MB.',
            ]);

        $this->assertDatabaseCount('reports', 0);
        $this->assertDatabaseCount('report_photos', 0);
        Storage::disk('local')->assertDirectoryEmpty('reports');
    }

    public function test_report_submission_rejects_svg_photos(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->post(route('reports.store'), [
            'description' => 'A drainage problem needs to be fixed.',
            'lat' => 6.9214,
            'lng' => 122.079,
            'photos' => [UploadedFile::fake()->createWithContent(
                'report.svg',
                '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
            )],
        ])
            ->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_staff_status_changes_require_a_note_for_override_actions(): void
    {
        $staff = $this->staffWithReportsView();
        $report = Report::factory()->create();

        $this->actingAs($staff)
            ->patch(route('admin.reports.status', $report), [
                'status' => 'no_action',
                'no_action_reason' => 'not_barangay_matter',
                'note' => '',
            ])
            ->assertSessionHasErrors([
                'note' => 'A note is required for this status change.',
            ]);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'submitted',
        ]);
        $this->assertDatabaseMissing('status_logs', [
            'report_id' => $report->id,
            'to_status' => 'no_action',
        ]);
    }

    public function test_staff_status_changes_allow_an_optional_note_for_review_transitions(): void
    {
        $staff = $this->staffWithReportsView();
        $report = Report::factory()->create();

        $this->actingAs($staff)
            ->patch(route('admin.reports.status', $report), [
                'status' => 'under_review',
                'note' => '',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Report status updated successfully.');

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'under_review',
        ]);
        $this->assertDatabaseHas('status_logs', [
            'report_id' => $report->id,
            'user_id' => $staff->id,
            'from_status' => 'submitted',
            'to_status' => 'under_review',
            'note' => null,
        ]);
    }

    public function test_staff_status_changes_record_the_staff_member_and_note(): void
    {
        $staff = $this->staffWithReportsView();
        $report = Report::factory()->create();
        $note = 'Reviewed and assigned for inspection.';

        $this->actingAs($staff)
            ->patch(route('admin.reports.status', $report), [
                'status' => 'under_review',
                'note' => $note,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Report status updated successfully.');

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'under_review',
        ]);
        $this->assertDatabaseHas('status_logs', [
            'report_id' => $report->id,
            'user_id' => $staff->id,
            'from_status' => 'submitted',
            'to_status' => 'under_review',
            'note' => $note,
        ]);
    }

    public function test_report_photos_are_only_available_to_staff_through_the_private_route(): void
    {
        Storage::fake('local');

        $report = Report::factory()->create([
            'photo_path' => 'reports/private.jpg',
        ]);
        Storage::disk('local')->put($report->photo_path, 'private photo contents');

        $this->get(route('admin.reports.photo', $report))
            ->assertRedirect(route('login'));

        $staff = $this->staffWithReportsView();

        $this->actingAs($staff)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee(route('admin.reports.photo', $report), false);

        $this->get(route('admin.reports.photo', $report))
            ->assertOk()
            ->assertStreamedContent('private photo contents');

        $reportWithoutPhoto = Report::factory()->create(['photo_path' => null]);

        $this->get(route('admin.reports.photo', $reportWithoutPhoto))
            ->assertNotFound();
    }

    /**
     * Phase C Amendment: Category is now explicitly optional on the resident report form ("Choose a category (optional)").
     * Submitting without category is accepted with category_id = null; downstream AI pipeline suggests category for staff review.
     */
    public function test_report_submission_allows_optional_category_id(): void
    {
        $response = $this->post(route('reports.store'), [
            'description' => 'A valid description of more than 10 characters.',
            'landmark' => 'Near the basketball court',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('reports', 1);
        $this->assertNull(Report::latest('id')->first()->category_id);
    }

    public function test_rapid_duplicate_report_submission_redirects_to_existing_report(): void
    {
        $category = Category::create([
            'name' => 'Flooding',
            'slug' => 'flooding',
            'is_active' => true,
            'risk_weight' => 2,
            'sla_hours' => 24,
        ]);

        $payload = [
            'description' => 'Duplicate issue description for flooding test.',
            'category_id' => $category->id,
            'landmark' => 'Near Purok 1 chapel',
        ];

        $firstResponse = $this->post(route('reports.store'), $payload);
        $firstResponse->assertRedirect();

        $this->assertDatabaseCount('reports', 1);
        $firstReport = Report::first();

        // Immediate duplicate submission within 20s
        $secondResponse = $this->post(route('reports.store'), $payload);
        $secondResponse->assertRedirect(route('reports.success', $firstReport->tracking_code));

        $this->assertDatabaseCount('reports', 1);
    }

    public function test_resident_tracking_page_renders_equal_resolution_choices_and_accessible_rating(): void
    {
        $category = Category::create([
            'name' => 'Road Hazard',
            'slug' => 'road-hazard',
            'is_active' => true,
            'risk_weight' => 2,
            'sla_hours' => 24,
        ]);

        $report = Report::factory()->create([
            'category_id' => $category->id,
            'status' => 'awaiting_confirmation',
        ]);

        $this->get(route('track.show', $report->tracking_code))
            ->assertOk()
            ->assertSee('Please verify: Is the problem actually fixed?')
            ->assertSee('Yes, Issue is Fixed')
            ->assertSee('No, Still Needs Work')
            ->assertSee('role="radiogroup"', false)
            ->assertSee('role="radio"', false);
    }

    public function test_resident_submission_and_lookup_views_render_enhanced_controls(): void
    {
        $this->seed(CategorySeeder::class);

        $createResponse = $this->get(route('reports.create'));
        $createResponse->assertOk()
            ->assertSee('id="description-char-counter"', false)
            ->assertSee('id="btn-quick-locate"', false)
            ->assertSee('id="anon-notice-banner"', false)
            ->assertSee('data-i18n="btn_quick_locate"', false)
            ->assertSee('data-i18n="anon_notice_text"', false);

        $trackResponse = $this->get(route('track.index'));
        $trackResponse->assertOk()
            ->assertSee('id="tracking_code"', false)
            ->assertSee('placeholder="e.g. BRG-AB12CD"', false)
            ->assertSee('id="recent-codes-wrap"', false)
            ->assertSee('BRG-XXXXXX')
            ->assertSee('data-i18n="track_title"', false)
            ->assertSee('js-track-lang-fil', false);

        $searchResponse = $this->post(route('track.search'), [
            'tracking_code' => 'brg-ab12cd',
        ]);
        $searchResponse->assertRedirect(route('track.show', 'BRG-AB12CD'));
    }

    public function test_resident_tracking_view_renders_lifecycle_stepper_and_before_after_comparison(): void
    {
        $category = Category::create([
            'name' => 'Drainage Works',
            'slug' => 'drainage-works',
            'is_active' => true,
            'risk_weight' => 2,
            'sla_hours' => 24,
        ]);

        $report = Report::factory()->create([
            'category_id' => $category->id,
            'status' => 'awaiting_confirmation',
            'description' => 'Blocked drainage culvert on corner lot.',
            'photo_path' => 'reports/test_before.jpg',
        ]);

        $staffUser = User::factory()->create(['role' => 'staff']);

        $report->resolutionProofs()->create([
            'user_id' => $staffUser->id,
            'note' => 'Debris cleared from drainage culvert.',
            'photo_path' => 'resolution_proofs/test_after.jpg',
        ]);

        $response = $this->get(route('track.show', $report->tracking_code));

        $response->assertOk()
            ->assertSee('Lifecycle Progress')
            ->assertSee('track-stepper-card', false)
            ->assertSee('1. Received & Review', false)
            ->assertSee('2. Work In Progress')
            ->assertSee('3. Verification')
            ->assertSee('Before vs. After Photo Comparison')
            ->assertSee('Before / Initial Report')
            ->assertSee('After / Staff Resolution Proof')
            ->assertSee('Blocked drainage culvert on corner lot.')
            ->assertSee('Print');

        $successResponse = $this->get(route('reports.success', $report->tracking_code));
        $successResponse->assertOk()
            ->assertSee('data-i18n="btn_print_slip"', false)
            ->assertSee('Print Slip');
    }

    public function test_resident_public_map_view_renders_interactive_filters_and_purok_selector(): void
    {
        $category = Category::create([
            'name' => 'Garbage Collection',
            'slug' => 'garbage-collection',
            'is_active' => true,
            'risk_weight' => 1,
            'sla_hours' => 48,
        ]);

        $report = Report::factory()->create([
            'category_id' => $category->id,
            'status' => 'in_progress',
            'lat' => 6.921,
            'lng' => 122.078,
            'is_emergency' => false,
        ]);

        $emergencyReport = Report::factory()->create([
            'category_id' => $category->id,
            'status' => 'emergency_flagged',
            'lat' => 6.925,
            'lng' => 122.080,
            'is_emergency' => true,
            'tracking_code' => 'BRG-EMERG1',
        ]);

        $response = $this->get(route('map.index'));

        $response->assertOk()
            ->assertSee('Public Map')
            ->assertSee('Active Barangay Reports')
            ->assertSee('data-cat-filter="all"', false)
            ->assertSee('id="purok-zoom-select"', false)
            ->assertSee('Public Transparency Mode')
            ->assertSee($report->tracking_code)
            ->assertDontSee('BRG-EMERG1');
    }
}
