<?php

namespace Tests\Feature;

use App\Models\Purok;
use App\Models\Report;
use App\Models\StatusLog;
use App\Services\BoundaryService;
use Database\Seeders\MockPurokBoundarySeeder;
use Database\Seeders\PurokSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        // With mock Puroks seeded in testing env: shows development preview disclosure
        $this->seed(MockPurokBoundarySeeder::class);

        $this->get(route('reports.create'))
            ->assertOk()
            ->assertSee('id="mock-purok-disclosure"', false)
            ->assertSee('Development Preview:');
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
}
