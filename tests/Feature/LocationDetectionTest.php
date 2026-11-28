<?php

namespace Tests\Feature;

use App\Models\Purok;
use App\Models\Report;
use App\Models\StatusLog;
use App\Services\BoundaryService;
use Database\Seeders\MockPurokBoundarySeeder;
use Database\Seeders\PurokSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class LocationDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PurokSeeder::class);
    }

    public function test_default_geojson_path_points_to_tracked_resource_and_detects_inside_and_outside_points(): void
    {
        $expectedPath = resource_path('gis/calarian_simplified.geojson');
        $this->assertSame($expectedPath, config('reports.gis.barangay_boundary_path'));
        $this->assertFileExists($expectedPath);

        // Inside supplied provisional polygon: lng 122.0300, lat 6.9250
        $this->postJson(route('api.detect-location'), [
            'lat' => 6.9250,
            'lng' => 122.0300,
        ])->assertOk()
            ->assertJson([
                'status' => 'boundary_data_unavailable',
                'in_barangay' => true,
                'purok' => null,
                'candidates' => [],
            ]);

        // Outside supplied provisional polygon: lng 122.1000, lat 6.9250
        $this->postJson(route('api.detect-location'), [
            'lat' => 6.9250,
            'lng' => 122.1000,
        ])->assertOk()
            ->assertJson([
                'status' => 'outside_barangay',
                'in_barangay' => false,
                'purok' => null,
                'candidates' => [],
            ]);

        $this->assertSame(0, Report::count());
        $this->assertSame(0, StatusLog::count());
    }

    public function test_environment_override_and_missing_geojson_fallback_work_safely(): void
    {
        config(['reports.gis.barangay_boundary_path' => resource_path('gis/does_not_exist.geojson')]);

        $missingResp = $this->postJson(route('api.detect-location'), [
            'lat' => 6.9250,
            'lng' => 122.0300,
        ])->assertOk()
            ->assertJson([
                'status' => 'boundary_data_unavailable',
                'in_barangay' => null,
                'purok' => null,
            ]);

        $this->assertStringNotContainsString('resources', (string) $missingResp->getContent());
    }

    public function test_endpoint_validates_coordinate_ranges(): void
    {
        $this->postJson(route('api.detect-location'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lat', 'lng']);

        $this->postJson(route('api.detect-location'), ['lat' => 95, 'lng' => 185])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lat', 'lng']);
    }

    public function test_mock_purok_seeder_creates_seven_valid_non_overlapping_mocks_inside_provisional_calarian(): void
    {
        $this->seed(MockPurokBoundarySeeder::class);

        $officialPuroks = Purok::query()->where('code', 'like', 'purok-%')->get();
        $this->assertCount(7, $officialPuroks);
        foreach ($officialPuroks as $official) {
            $this->assertNull($official->boundary_geometry);
        }

        $mockPuroks = Purok::query()->where('code', 'like', 'mock-purok-%')->orderBy('code')->get();
        $this->assertCount(7, $mockPuroks);

        $boundaryService = app(BoundaryService::class);
        $calarianGeo = json_decode((string) file_get_contents(resource_path('gis/calarian_simplified.geojson')), true);
        $calarianPolygon = $calarianGeo['features'][0]['geometry'];

        foreach ($mockPuroks as $mock) {
            $this->assertStringStartsWith('MOCK PUROK ', $mock->name);
            $this->assertTrue($boundaryService->validateGeometry($mock->boundary_geometry)['valid']);

            $ring = $mock->boundary_geometry['coordinates'][0];
            $centerLng = ($ring[0][0] + $ring[2][0]) / 2;
            $centerLat = ($ring[0][1] + $ring[2][1]) / 2;

            $inCalarian = $boundaryService->isPointInGeometry($centerLat, $centerLng, $calarianPolygon);
            $this->assertTrue($inCalarian['inside'], "{$mock->name} centroid must be inside provisional Calarian polygon.");

            $detect = $boundaryService->detectLocation($centerLat, $centerLng, $calarianPolygon);
            $this->assertSame('exact', $detect['status'], "{$mock->name} centroid must match exactly one non-overlapping mock Purok.");
            $this->assertSame($mock->id, $detect['purok']['id']);
        }

        // Supplied inside point (lng 122.0300, lat 6.9250) falls inside MOCK PUROK 3
        $this->postJson(route('api.detect-location'), [
            'lat' => 6.9250,
            'lng' => 122.0300,
        ])->assertOk()
            ->assertJson([
                'status' => 'exact',
                'in_barangay' => true,
                'purok' => [
                    'code' => 'mock-purok-3',
                    'name' => 'MOCK PUROK 3',
                ],
            ]);
    }

    public function test_mock_puroks_are_excluded_from_detection_and_dropdown_in_production_even_if_present_in_db(): void
    {
        // Seed mock Puroks while in testing environment to simulate a copied DB.
        $this->seed(MockPurokBoundarySeeder::class);
        $this->assertSame(7, Purok::query()->where('code', 'like', 'mock-purok-%')->count());

        $originalEnv = $this->app->environment();
        $this->app->detectEnvironment(fn () => 'production');

        try {
            // Seeder refuses to run in production
            (new MockPurokBoundarySeeder)->run();

            $calarianGeo = json_decode((string) file_get_contents(resource_path('gis/calarian_simplified.geojson')), true);
            $calarianPolygon = $calarianGeo['features'][0]['geometry'];

            // BoundaryService::detectLocation() ignores mock Puroks in production
            $detection = app(BoundaryService::class)->detectLocation(6.9250, 122.0300, $calarianPolygon);
            $this->assertSame('boundary_data_unavailable', $detection['status']);
            $this->assertTrue($detection['in_barangay']);
            $this->assertNull($detection['purok']);
            $this->assertSame([], $detection['candidates']);

            // Purok dropdown scope used by ReportController::create() excludes mock Puroks while preserving official Puroks
            $visiblePuroks = Purok::query()
                ->visibleInCurrentEnvironment()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->all();

            $this->assertContains('Purok 1', $visiblePuroks);
            $this->assertContains('Purok 7', $visiblePuroks);
            $this->assertNotContains('MOCK PUROK 1', $visiblePuroks);
            $this->assertNotContains('MOCK PUROK 3', $visiblePuroks);
            $this->assertFalse(Purok::allowsMockPuroks());
        } finally {
            $this->app->detectEnvironment(fn () => $originalEnv);
        }
    }

    public function test_resident_create_view_renders_location_assistance_provisional_messaging_and_mock_disclosure(): void
    {
        // Without mock Puroks: no mock disclosure banner, includes provisional-boundary messaging
        $this->get(route('reports.create'))
            ->assertOk()
            ->assertSee('id="location-detection-panel"', false)
            ->assertSee(route('api.detect-location'), false)
            ->assertSee('Inside provisional Barangay Calarian boundary')
            ->assertDontSee('id="mock-purok-disclosure"', false);

        // With mock Puroks seeded in testing env: shows development preview disclosure and detected location panel
        $this->seed(MockPurokBoundarySeeder::class);

        $this->get(route('reports.create'))
            ->assertOk()
            ->assertSee('id="mock-purok-disclosure"', false)
            ->assertSee('Development Preview:')
            ->assertSee('id="detected-location-card"', false)
            ->assertSee('Detected Location Information')
            ->assertSee('id="detected-barangay-val"', false)
            ->assertSee('id="detected-city-val"', false)
            ->assertSee('id="detected-purok-val"', false)
            ->assertSee('id="detected-coords-val"', false)
            ->assertSee('Zamboanga City');
    }

    public function test_mock_purok_seeder_is_repeatable(): void
    {
        $this->seed(MockPurokBoundarySeeder::class);
        $initialCount = Purok::query()->where('code', 'like', 'mock-%')->count();
        $this->assertSame(7, $initialCount);

        // Run seeder a second time
        (new MockPurokBoundarySeeder)->run();
        $this->assertSame(7, Purok::query()->where('code', 'like', 'mock-%')->count());
    }

    public function test_boundary_service_detects_every_individual_mock_purok(): void
    {
        $this->seed(MockPurokBoundarySeeder::class);
        $calarianGeo = json_decode((string) file_get_contents(resource_path('gis/calarian_simplified.geojson')), true);
        $calarianPolygon = $calarianGeo['features'][0]['geometry'];
        $boundaryService = app(BoundaryService::class);

        $mockCentroids = [
            'mock-purok-1' => ['lat' => 6.9290, 'lng' => 122.02325],
            'mock-purok-2' => ['lat' => 6.92475, 'lng' => 122.0275],
            'mock-purok-3' => ['lat' => 6.92475, 'lng' => 122.0306],
            'mock-purok-4' => ['lat' => 6.9250, 'lng' => 122.03385],
            'mock-purok-5' => ['lat' => 6.92525, 'lng' => 122.03785],
            'mock-purok-6' => ['lat' => 6.9215, 'lng' => 122.03435],
            'mock-purok-7' => ['lat' => 6.92075, 'lng' => 122.03885],
        ];

        foreach ($mockCentroids as $code => $coords) {
            $detection = $boundaryService->detectLocation($coords['lat'], $coords['lng'], $calarianPolygon);
            $this->assertSame('exact', $detection['status'], "Failed for {$code}");
            $this->assertTrue($detection['in_barangay']);
            $this->assertNotNull($detection['purok']);
            $this->assertSame($code, $detection['purok']['code']);

            $this->postJson(route('api.detect-location'), $coords)
                ->assertOk()
                ->assertJson([
                    'status' => 'exact',
                    'in_barangay' => true,
                    'purok' => ['code' => $code],
                ]);
        }
    }

    public function test_boundary_service_detects_unresolved_and_ambiguous_scenarios(): void
    {
        $this->seed(MockPurokBoundarySeeder::class);
        $calarianGeo = json_decode((string) file_get_contents(resource_path('gis/calarian_simplified.geojson')), true);
        $calarianPolygon = $calarianGeo['features'][0]['geometry'];
        $boundaryService = app(BoundaryService::class);

        // Inside Calarian, outside all 7 mock puroks -> purok_unresolved
        $unresolved = $boundaryService->detectLocation(6.925, 122.020, $calarianPolygon);
        $this->assertSame('purok_unresolved', $unresolved['status']);
        $this->assertTrue($unresolved['in_barangay']);
        $this->assertNull($unresolved['purok']);

        $this->postJson(route('api.detect-location'), ['lat' => 6.925, 'lng' => 122.020])
            ->assertOk()
            ->assertJson([
                'status' => 'purok_unresolved',
                'in_barangay' => true,
                'purok' => null,
            ]);

        // Boundary edge proximity -> boundary_ambiguous
        $ambiguous = $boundaryService->detectLocation(6.920, 122.035, $calarianPolygon);
        $this->assertSame('boundary_ambiguous', $ambiguous['status']);
        $this->assertTrue($ambiguous['in_barangay']);
        $this->assertNull($ambiguous['purok']);

        $this->postJson(route('api.detect-location'), ['lat' => 6.920, 'lng' => 122.035])
            ->assertOk()
            ->assertJson([
                'status' => 'boundary_ambiguous',
                'in_barangay' => true,
                'purok' => null,
            ]);
    }

    public function test_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->postJson(route('api.detect-location'), ['lat' => 6.9250, 'lng' => 122.0300])
                ->assertOk();
        }

        $this->postJson(route('api.detect-location'), ['lat' => 6.9250, 'lng' => 122.0300])
            ->assertStatus(429);
    }

    public function test_existing_manual_report_submission_remains_unaffected(): void
    {
        $purok = Purok::query()->where('code', 'purok-3')->firstOrFail();

        $this->post(route('reports.store'), [
            'description' => 'Uncollected garbage bags piled up near the corner.',
            'purok_id' => $purok->id,
            'landmark' => 'Near the basketball court',
            'consent' => '1',
            'has_consent_field' => '1',
        ])->assertRedirect();

        $report = Report::firstOrFail();
        $this->assertSame($purok->id, $report->purok_id);
        $this->assertSame($purok->id, $report->resident_purok_id);
        $this->assertNull($report->lat);
        $this->assertNull($report->lng);
    }

    public function test_purok_boundaries_endpoint_returns_provisional_calarian_boundary_and_all_seven_mock_puroks(): void
    {
        $this->seed(MockPurokBoundarySeeder::class);

        $response = $this->getJson(route('api.purok-boundaries'))
            ->assertOk()
            ->assertJsonStructure([
                'barangay' => [
                    'name',
                    'is_provisional',
                    'geometry' => [
                        'type',
                        'coordinates',
                    ],
                ],
                'puroks' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'is_mock',
                        'geometry' => [
                            'type',
                            'coordinates',
                        ],
                    ],
                ],
            ]);

        $data = $response->json();
        $this->assertSame('Barangay Calarian', $data['barangay']['name']);
        $this->assertTrue($data['barangay']['is_provisional']);
        $this->assertSame('Polygon', $data['barangay']['geometry']['type']);

        $this->assertCount(7, $data['puroks']);
        $codes = collect($data['puroks'])->pluck('code')->all();
        $this->assertSame([
            'mock-purok-1',
            'mock-purok-2',
            'mock-purok-3',
            'mock-purok-4',
            'mock-purok-5',
            'mock-purok-6',
            'mock-purok-7',
        ], $codes);

        foreach ($data['puroks'] as $purok) {
            $this->assertTrue($purok['is_mock']);
            $this->assertSame('Polygon', $purok['geometry']['type']);
            $this->assertNotEmpty($purok['geometry']['coordinates']);
        }
    }

    public function test_resident_create_form_renders_boundary_legend_and_script_elements(): void
    {
        $this->seed(MockPurokBoundarySeeder::class);

        $response = $this->get(route('reports.create'))
            ->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('id="map-boundary-legend"', $content);
        $this->assertStringContainsString('id="legend-purok-chips"', $content);
        $this->assertStringContainsString('data-i18n="legend_title"', $content);
        $this->assertStringContainsString('data-i18n="legend_calarian_boundary"', $content);
        $this->assertStringContainsString('loadMapBoundaries', $content);
        $this->assertStringContainsString('highlightPurokPolygon', $content);
    }

    public function test_submission_with_coordinates_and_detected_or_overridden_purok(): void
    {
        $this->seed(MockPurokBoundarySeeder::class);
        $purok = Purok::query()->where('code', 'mock-purok-1')->firstOrFail();

        $this->post(route('reports.store'), [
            'description' => 'Water pipe burst along the street near mock purok 1.',
            'lat' => 6.9290,
            'lng' => 122.0232,
            'purok_id' => $purok->id,
            'consent' => '1',
            'has_consent_field' => '1',
        ])->assertRedirect();

        $report = Report::latest('id')->firstOrFail();
        $this->assertSame($purok->id, $report->purok_id);
        $this->assertSame(6.9290, (float) $report->lat);
        $this->assertSame(122.0232, (float) $report->lng);
    }

    public function test_submission_with_landmark_only_without_coordinates_or_purok(): void
    {
        $this->post(route('reports.store'), [
            'description' => 'Streetlight not working near the local bakery landmark.',
            'landmark' => 'In front of Calarian Golden Bakery',
            'consent' => '1',
            'has_consent_field' => '1',
        ])->assertRedirect();

        $report = Report::latest('id')->firstOrFail();
        $this->assertNull($report->purok_id);
        $this->assertNull($report->lat);
        $this->assertNull($report->lng);
        $this->assertSame('In front of Calarian Golden Bakery', $report->landmark);
    }

    public function test_submission_with_coordinates_outside_provisional_boundary(): void
    {
        // Resident submits pin outside boundary: backend must save it for staff review
        $this->post(route('reports.store'), [
            'description' => 'Flooding near the border canal outside boundary.',
            'lat' => 6.9000,
            'lng' => 122.0100,
            'landmark' => 'Near coastal marker',
            'consent' => '1',
            'has_consent_field' => '1',
        ])->assertRedirect();

        $report = Report::latest('id')->firstOrFail();
        $this->assertSame(6.9000, (float) $report->lat);
        $this->assertSame(122.0100, (float) $report->lng);
        $this->assertSame('Near coastal marker', $report->landmark);
    }

    public function test_submission_fails_when_all_location_assistance_options_are_omitted(): void
    {
        $this->post(route('reports.store'), [
            'description' => 'Test report with no coordinates, purok, or landmark provided.',
            'consent' => '1',
            'has_consent_field' => '1',
        ])->assertSessionHasErrors(['location']);
    }

    public function test_submission_rejects_inactive_purok(): void
    {
        $inactive = Purok::create([
            'name' => 'Inactive Purok Test',
            'code' => 'purok-inactive-test',
            'is_active' => false,
        ]);

        $this->post(route('reports.store'), [
            'description' => 'Report referencing an inactive purok that should be rejected.',
            'purok_id' => $inactive->id,
            'consent' => '1',
            'has_consent_field' => '1',
        ])->assertSessionHasErrors(['purok_id']);

        $this->assertDatabaseMissing('reports', [
            'description' => 'Report referencing an inactive purok that should be rejected.',
        ]);
    }

    public function test_submission_accepts_coordinate_and_purok_mismatch_without_blocking(): void
    {
        $this->seed(MockPurokBoundarySeeder::class);
        $purok1 = Purok::query()->where('code', 'mock-purok-1')->firstOrFail();
        $purok2 = Purok::query()->where('code', 'mock-purok-2')->firstOrFail();

        // Coordinates fall squarely inside Mock Purok 1 [6.9290, 122.0232]
        // But resident manually selected Purok 2
        $this->post(route('reports.store'), [
            'description' => 'Discrepancy test: coordinates in purok 1, dropdown set to purok 2.',
            'lat' => 6.9290,
            'lng' => 122.0232,
            'purok_id' => $purok2->id,
            'consent' => '1',
            'has_consent_field' => '1',
        ])->assertRedirect();

        $report = Report::latest('id')->firstOrFail();
        $this->assertSame($purok2->id, $report->purok_id);
        $this->assertSame($purok2->id, $report->resident_purok_id);
        $this->assertSame(6.9290, (float) $report->lat);
        $this->assertSame(122.0232, (float) $report->lng);
    }

    public function test_submission_rejects_out_of_range_coordinates(): void
    {
        $purok = Purok::firstOrFail();

        $this->post(route('reports.store'), [
            'description' => 'Out of range latitude submission test.',
            'lat' => 95.0,
            'lng' => 122.0300,
            'purok_id' => $purok->id,
            'consent' => '1',
            'has_consent_field' => '1',
        ])->assertSessionHasErrors(['lat']);

        $this->post(route('reports.store'), [
            'description' => 'Out of range longitude submission test.',
            'lat' => 6.9250,
            'lng' => 195.0,
            'purok_id' => $purok->id,
            'consent' => '1',
            'has_consent_field' => '1',
        ])->assertSessionHasErrors(['lng']);

        $this->assertDatabaseMissing('reports', [
            'description' => 'Out of range latitude submission test.',
        ]);
        $this->assertDatabaseMissing('reports', [
            'description' => 'Out of range longitude submission test.',
        ]);
    }

    public function test_resident_dropdown_is_deduplicated_and_displays_clean_labels(): void
    {
        $this->seed(MockPurokBoundarySeeder::class);

        $response = $this->get(route('reports.create'));
        $response->assertOk();

        $content = (string) $response->getContent();

        // Extract select element with id="purok_id"
        preg_match('/<select[^>]*id="purok_id"[^>]*>(.*?)<\/select>/s', $content, $selectMatch);
        $this->assertNotEmpty($selectMatch, 'Purok select dropdown must exist in HTML.');

        preg_match_all('/<option[^>]*value="([^"]*)"[^>]*>(.*?)<\/option>/s', $selectMatch[1], $optionMatches, PREG_SET_ORDER);

        // Filter out empty placeholder option
        $purokOptions = array_values(array_filter($optionMatches, fn ($m) => $m[1] !== ''));

        // Exactly 7 options for Purok 1 through Purok 7
        $this->assertCount(7, $purokOptions, 'Dropdown must have exactly 7 options without duplicates.');

        $seenIds = [];
        for ($i = 1; $i <= 7; $i++) {
            $expectedName = "Purok {$i}";
            $option = $purokOptions[$i - 1];
            $optValue = (int) $option[1];
            $optText = trim(strip_tags($option[2]));

            $this->assertSame($expectedName, $optText, "Option {$i} label must be clean '{$expectedName}'.");
            $this->assertStringNotContainsString('MOCK', $optText);
            $this->assertStringNotContainsString('Simulated', $optText);

            // Confirm option value points to an active Purok record
            $dbPurok = Purok::query()->findOrFail($optValue);
            $this->assertTrue($dbPurok->is_active);
            $this->assertNotNull($dbPurok->boundary_geometry);

            $seenIds[] = $optValue;
        }

        $this->assertCount(7, array_unique($seenIds), 'Dropdown options must have unique record IDs.');
    }

    public function test_purok_validation_rule_rejects_mock_puroks_in_production(): void
    {
        $this->seed(MockPurokBoundarySeeder::class);
        $mockPurok = Purok::query()->where('code', 'mock-purok-1')->firstOrFail();

        $originalEnv = $this->app->environment();
        $this->app->detectEnvironment(fn () => 'production');

        try {
            $rule = Rule::exists('puroks', 'id')
                ->where('is_active', true)
                ->when(! Purok::allowsMockPuroks(), static function ($query): void {
                    $query->where(static function ($q): void {
                        $q->whereNull('code')
                            ->orWhere('code', 'not like', 'mock-%');
                    });
                });

            $validator = Validator::make(
                ['purok_id' => $mockPurok->id],
                ['purok_id' => ['nullable', $rule]]
            );

            $this->assertTrue($validator->fails());
            $this->assertSame(['purok_id'], $validator->errors()->keys());
        } finally {
            $this->app->detectEnvironment(fn () => $originalEnv);
        }
    }
}
