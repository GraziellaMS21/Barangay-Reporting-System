<?php

namespace Tests\Unit;

use App\Models\Purok;
use App\Services\BoundaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for BoundaryService using clearly labeled SYNTHETIC polygon fixtures.
 * NOTE: Coordinates below are synthetic geometric shapes for algorithm verification only,
 * NOT official Barangay Calarian boundary data.
 */
class BoundaryServiceTest extends TestCase
{
    use RefreshDatabase;

    private BoundaryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BoundaryService;
    }

    /**
     * Synthetic 0.01 x 0.01 degree square polygon around (10.0, 10.0) in [lng, lat] order.
     *
     * @return array<string, mixed>
     */
    private function syntheticSquarePolygon(float $minLng = 10.0, float $minLat = 10.0, float $maxLng = 10.01, float $maxLat = 10.01): array
    {
        return [
            'type' => 'Polygon',
            'coordinates' => [[
                [$minLng, $minLat],
                [$maxLng, $minLat],
                [$maxLng, $maxLat],
                [$minLng, $maxLat],
                [$minLng, $minLat],
            ]],
        ];
    }

    public function test_point_inside_and_outside_synthetic_polygon(): void
    {
        $poly = $this->syntheticSquarePolygon();

        $inside = $this->service->isPointInGeometry(10.005, 10.005, $poly, 5.0);
        $this->assertTrue($inside['inside']);
        $this->assertFalse($inside['on_boundary']);

        $outside = $this->service->isPointInGeometry(10.02, 10.02, $poly, 5.0);
        $this->assertFalse($outside['inside']);
        $this->assertFalse($outside['on_boundary']);
    }

    public function test_point_on_edge_and_on_vertex_are_flagged_as_boundary(): void
    {
        $poly = $this->syntheticSquarePolygon();

        // Exactly on bottom edge
        $edge = $this->service->isPointInGeometry(10.0, 10.005, $poly, 5.0);
        $this->assertTrue($edge['inside']);
        $this->assertTrue($edge['on_boundary']);

        // Exactly on bottom-left vertex
        $vertex = $this->service->isPointInGeometry(10.0, 10.0, $poly, 5.0);
        $this->assertTrue($vertex['inside']);
        $this->assertTrue($vertex['on_boundary']);
    }

    public function test_point_inside_polygon_hole_is_outside_geometry(): void
    {
        $polyWithHole = [
            'type' => 'Polygon',
            'coordinates' => [
                // Outer ring
                [[10.0, 10.0], [10.04, 10.0], [10.04, 10.04], [10.0, 10.04], [10.0, 10.0]],
                // Hole ring
                [[10.01, 10.01], [10.03, 10.01], [10.03, 10.03], [10.01, 10.03], [10.01, 10.01]],
            ],
        ];

        $inHole = $this->service->isPointInGeometry(10.02, 10.02, $polyWithHole, 5.0);
        $this->assertFalse($inHole['inside']);

        $inOuterRing = $this->service->isPointInGeometry(10.005, 10.005, $polyWithHole, 5.0);
        $this->assertTrue($inOuterRing['inside']);
        $this->assertFalse($inOuterRing['on_boundary']);
    }

    public function test_point_inside_multipolygon(): void
    {
        $multiPoly = [
            'type' => 'MultiPolygon',
            'coordinates' => [
                [[[10.0, 10.0], [10.01, 10.0], [10.01, 10.01], [10.0, 10.01], [10.0, 10.0]]],
                [[[10.05, 10.05], [10.06, 10.05], [10.06, 10.06], [10.05, 10.06], [10.05, 10.05]]],
            ],
        ];

        $inSecond = $this->service->isPointInGeometry(10.055, 10.055, $multiPoly, 5.0);
        $this->assertTrue($inSecond['inside']);
        $this->assertFalse($inSecond['on_boundary']);

        $between = $this->service->isPointInGeometry(10.03, 10.03, $multiPoly, 5.0);
        $this->assertFalse($between['inside']);
    }

    public function test_geojson_validation_rejects_malformed_unclosed_and_out_of_range_geometries(): void
    {
        // Malformed FeatureCollection
        $this->assertFalse($this->service->validateFeatureCollection(['type' => 'Invalid'])['valid']);

        // Unclosed polygon ring
        $unclosed = [
            'type' => 'Polygon',
            'coordinates' => [[[10.0, 10.0], [10.01, 10.0], [10.01, 10.01], [10.0, 10.01]]],
        ];
        $this->assertFalse($this->service->validateGeometry($unclosed)['valid']);

        // Incorrect coordinate structure (< 4 points)
        $tooFew = [
            'type' => 'Polygon',
            'coordinates' => [[[10.0, 10.0], [10.01, 10.0], [10.0, 10.0]]],
        ];
        $this->assertFalse($this->service->validateGeometry($tooFew)['valid']);

        // Invalid longitude range (> 180)
        $badLng = $this->syntheticSquarePolygon(181.0, 10.0, 181.01, 10.01);
        $this->assertFalse($this->service->validateGeometry($badLng)['valid']);

        // Invalid latitude range (< -90)
        $badLat = $this->syntheticSquarePolygon(10.0, -91.0, 10.01, -90.5);
        $this->assertFalse($this->service->validateGeometry($badLat)['valid']);

        // Empty coordinates
        $this->assertFalse($this->service->validateGeometry(['type' => 'Polygon', 'coordinates' => []])['valid']);
    }

    public function test_detect_location_returns_unavailable_when_no_boundaries_configured(): void
    {
        Purok::create(['name' => 'Synthetic Purok A', 'code' => 'synthetic-a', 'boundary_geometry' => null]);

        $res = $this->service->detectLocation(10.005, 10.005);
        $this->assertSame('boundary_data_unavailable', $res['status']);
        $this->assertNull($res['purok']);
        $this->assertNull($res['in_barangay']);
    }

    public function test_detect_location_handles_exact_overlapping_unresolved_outside_and_no_coordinates(): void
    {
        $this->assertSame('no_coordinates', $this->service->detectLocation(null, null)['status']);

        $outerBarangay = $this->syntheticSquarePolygon(10.0, 10.0, 10.05, 10.05);

        $p1 = Purok::create([
            'name' => 'Synthetic Purok 1',
            'code' => 'synthetic-1',
            'boundary_geometry' => $this->syntheticSquarePolygon(10.0, 10.0, 10.02, 10.02),
            'is_active' => true,
        ]);

        // Overlapping synthetic Purok 2 from 10.015..10.035
        $p2 = Purok::create([
            'name' => 'Synthetic Purok 2',
            'code' => 'synthetic-2',
            'boundary_geometry' => $this->syntheticSquarePolygon(10.015, 10.015, 10.035, 10.035),
            'is_active' => true,
        ]);

        // Exact match in Purok 1
        $exact = $this->service->detectLocation(10.005, 10.005, $outerBarangay);
        $this->assertSame('exact', $exact['status']);
        $this->assertTrue($exact['in_barangay']);
        $this->assertSame($p1->id, $exact['purok']['id']);

        // Overlapping point (inside both Purok 1 and Purok 2)
        $overlap = $this->service->detectLocation(10.017, 10.017, $outerBarangay);
        $this->assertSame('boundary_ambiguous', $overlap['status']);
        $this->assertNull($overlap['purok']);
        $this->assertCount(2, $overlap['candidates']);
        $this->assertSame([$p1->id, $p2->id], array_column($overlap['candidates'], 'id'));

        // Inside outer barangay, outside all Purok polygons
        $unresolved = $this->service->detectLocation(10.045, 10.045, $outerBarangay);
        $this->assertSame('purok_unresolved', $unresolved['status']);
        $this->assertTrue($unresolved['in_barangay']);
        $this->assertNull($unresolved['purok']);

        // Outside outer barangay polygon
        $outside = $this->service->detectLocation(11.0, 11.0, $outerBarangay);
        $this->assertSame('outside_barangay', $outside['status']);
        $this->assertFalse($outside['in_barangay']);
        $this->assertNull($outside['purok']);
    }
}
