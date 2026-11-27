<?php

namespace Tests\Feature;

use App\Models\Purok;
use App\Models\Report;
use App\Models\StatusLog;
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

    public function test_endpoint_returns_boundary_data_unavailable_until_official_boundaries_are_imported(): void
    {
        $response = $this->postJson(route('api.detect-location'), [
            'lat' => 6.921,
            'lng' => 122.078,
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'boundary_data_unavailable',
                'in_barangay' => null,
                'purok' => null,
                'candidates' => [],
            ]);

        $this->assertSame(0, Report::count());
        $this->assertSame(0, StatusLog::count());
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

    public function test_endpoint_detects_synthetic_purok_and_preserves_zero_db_side_effects(): void
    {
        // Synthetic test fixture attached solely for endpoint integration testing.
        $purok = Purok::query()->where('code', 'purok-1')->firstOrFail();
        $purok->update([
            'boundary_geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[[20.0, 20.0], [20.02, 20.0], [20.02, 20.02], [20.0, 20.02], [20.0, 20.0]]],
            ],
        ]);

        $this->postJson(route('api.detect-location'), [
            'lat' => 20.01,
            'lng' => 20.01,
        ])->assertOk()
            ->assertJson([
                'status' => 'exact',
                'purok' => [
                    'id' => $purok->id,
                    'code' => 'purok-1',
                    'name' => 'Purok 1',
                ],
            ]);

        $this->assertSame(0, Report::count());
    }

    public function test_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->postJson(route('api.detect-location'), ['lat' => 6.921, 'lng' => 122.078])
                ->assertOk();
        }

        $this->postJson(route('api.detect-location'), ['lat' => 6.921, 'lng' => 122.078])
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
