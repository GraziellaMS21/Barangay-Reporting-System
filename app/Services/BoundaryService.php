<?php

namespace App\Services;

use App\Models\Purok;

/**
 * Pure-PHP geographic boundary validation and Point-in-Polygon detection engine.
 *
 * Supports GeoJSON Polygon (with outer ring and optional holes) and MultiPolygon
 * using [longitude, latitude] coordinate ordering without external GIS dependencies.
 */
class BoundaryService
{
    private const EARTH_METERS_PER_DEGREE_LAT = 111320.0;

    private const RING_CLOSE_EPSILON = 1e-9;

    /**
     * Validate a GeoJSON Polygon or MultiPolygon geometry array.
     *
     * @return array{valid: bool, error: ?string}
     */
    public function validateGeometry(mixed $geometry): array
    {
        if (! is_array($geometry)) {
            return ['valid' => false, 'error' => 'Geometry must be an object/array.'];
        }

        $type = $geometry['type'] ?? null;
        $coordinates = $geometry['coordinates'] ?? null;

        if (! in_array($type, ['Polygon', 'MultiPolygon'], true)) {
            return ['valid' => false, 'error' => 'Geometry type must be Polygon or MultiPolygon.'];
        }

        if (! is_array($coordinates) || $coordinates === [] || ! array_is_list($coordinates)) {
            return ['valid' => false, 'error' => 'Geometry coordinates must be a non-empty list.'];
        }

        if ($type === 'Polygon') {
            return $this->validatePolygonRings($coordinates);
        }

        foreach ($coordinates as $polyIndex => $polygonRings) {
            if (! is_array($polygonRings) || $polygonRings === [] || ! array_is_list($polygonRings)) {
                return ['valid' => false, 'error' => "MultiPolygon element {$polyIndex} must be a non-empty list of rings."];
            }

            $res = $this->validatePolygonRings($polygonRings, "MultiPolygon element {$polyIndex}");
            if (! $res['valid']) {
                return $res;
            }
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate a GeoJSON FeatureCollection and every feature geometry inside it.
     *
     * @return array{valid: bool, features: list<array<string, mixed>>, error: ?string}
     */
    public function validateFeatureCollection(mixed $geojson): array
    {
        if (! is_array($geojson) || ($geojson['type'] ?? null) !== 'FeatureCollection') {
            return ['valid' => false, 'features' => [], 'error' => 'GeoJSON root must be a FeatureCollection.'];
        }

        $features = $geojson['features'] ?? null;
        if (! is_array($features) || ! array_is_list($features)) {
            return ['valid' => false, 'features' => [], 'error' => 'FeatureCollection must contain a features list.'];
        }

        foreach ($features as $index => $feature) {
            if (! is_array($feature) || ($feature['type'] ?? null) !== 'Feature') {
                return ['valid' => false, 'features' => [], 'error' => "Feature {$index} is not a valid GeoJSON Feature."];
            }

            $geomCheck = $this->validateGeometry($feature['geometry'] ?? null);
            if (! $geomCheck['valid']) {
                return ['valid' => false, 'features' => [], 'error' => "Feature {$index}: {$geomCheck['error']}"];
            }
        }

        return ['valid' => true, 'features' => $features, 'error' => null];
    }

    /**
     * Evaluate whether (lat, lng) lies inside or on the boundary of a validated GeoJSON geometry.
     *
     * @param  array<string, mixed>  $geometry
     * @return array{inside: bool, on_boundary: bool}
     */
    public function isPointInGeometry(float $lat, float $lng, array $geometry, ?float $toleranceMeters = null): array
    {
        $tolerance = $toleranceMeters ?? (float) config('reports.gis.boundary_tolerance_meters', 5.0);
        $type = $geometry['type'] ?? null;
        $coordinates = $geometry['coordinates'] ?? [];

        if ($type === 'Polygon') {
            return $this->evaluatePolygon($lat, $lng, $coordinates, $tolerance);
        }

        if ($type === 'MultiPolygon') {
            $onBoundaryAny = false;
            foreach ($coordinates as $polygonRings) {
                $res = $this->evaluatePolygon($lat, $lng, $polygonRings, $tolerance);
                if ($res['on_boundary']) {
                    $onBoundaryAny = true;
                }
                if ($res['inside'] && ! $res['on_boundary']) {
                    return ['inside' => true, 'on_boundary' => false];
                }
            }

            if ($onBoundaryAny) {
                return ['inside' => true, 'on_boundary' => true];
            }
        }

        return ['inside' => false, 'on_boundary' => false];
    }

    /**
     * Detect the Purok (and optional Barangay coverage) for a coordinate pair.
     *
     * @param  array<string, mixed>|null  $barangayBoundary  Optional official outer Barangay Polygon/MultiPolygon
     * @return array{
     *     status: string,
     *     in_barangay: ?bool,
     *     purok: array{id: int, code: ?string, name: string}|null,
     *     candidates: list<array{id: int, code: ?string, name: string}>,
     *     message: string
     * }
     */
    public function detectLocation(?float $lat, ?float $lng, ?array $barangayBoundary = null): array
    {
        if ($lat === null || $lng === null) {
            return [
                'status' => 'no_coordinates',
                'in_barangay' => null,
                'purok' => null,
                'candidates' => [],
                'message' => 'No coordinates were provided.',
            ];
        }

        $puroks = Purok::query()
            ->visibleInCurrentEnvironment()
            ->where('is_active', true)
            ->whereNotNull('boundary_geometry')
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'boundary_geometry']);

        if ($puroks->isEmpty() && $barangayBoundary === null) {
            return [
                'status' => 'boundary_data_unavailable',
                'in_barangay' => null,
                'purok' => null,
                'candidates' => [],
                'message' => 'Official Purok boundary data is not yet configured.',
            ];
        }

        if ($barangayBoundary !== null) {
            $barValidation = $this->validateGeometry($barangayBoundary);
            if (! $barValidation['valid']) {
                return [
                    'status' => 'invalid_boundary_data',
                    'in_barangay' => null,
                    'purok' => null,
                    'candidates' => [],
                    'message' => 'Configured barangay boundary geometry is invalid.',
                ];
            }
        }

        foreach ($puroks as $purok) {
            $geomCheck = $this->validateGeometry($purok->boundary_geometry);
            if (! $geomCheck['valid']) {
                return [
                    'status' => 'invalid_boundary_data',
                    'in_barangay' => null,
                    'purok' => null,
                    'candidates' => [],
                    'message' => 'One or more configured Purok boundaries are invalid.',
                ];
            }
        }

        $inBarangay = null;
        if ($barangayBoundary !== null) {
            $barEval = $this->isPointInGeometry($lat, $lng, $barangayBoundary);
            if (! $barEval['inside']) {
                return [
                    'status' => 'outside_barangay',
                    'in_barangay' => false,
                    'purok' => null,
                    'candidates' => [],
                    'message' => 'Coordinates appear to be outside the official Barangay Calarian boundary.',
                ];
            }
            $inBarangay = true;
        }

        if ($puroks->isEmpty()) {
            return [
                'status' => 'boundary_data_unavailable',
                'in_barangay' => $inBarangay,
                'purok' => null,
                'candidates' => [],
                'message' => 'Official Purok boundary data is not yet configured.',
            ];
        }

        $matched = [];
        $boundaryMatches = [];

        foreach ($puroks as $purok) {
            $eval = $this->isPointInGeometry($lat, $lng, $purok->boundary_geometry);
            if ($eval['inside']) {
                $entry = [
                    'id' => $purok->id,
                    'code' => $purok->code,
                    'name' => $purok->name,
                ];
                $matched[] = $entry;
                if ($eval['on_boundary']) {
                    $boundaryMatches[] = $entry;
                }
            }
        }

        if (count($matched) > 1 || (count($matched) === 1 && count($boundaryMatches) > 0)) {
            return [
                'status' => 'boundary_ambiguous',
                'in_barangay' => $inBarangay,
                'purok' => null,
                'candidates' => $matched,
                'message' => 'Coordinates lie on or near a Purok boundary or overlap multiple Puroks. Please verify.',
            ];
        }

        if (count($matched) === 1) {
            return [
                'status' => 'exact',
                'in_barangay' => $inBarangay,
                'purok' => $matched[0],
                'candidates' => $matched,
                'message' => 'Purok identified from coordinates.',
            ];
        }

        return [
            'status' => 'purok_unresolved',
            'in_barangay' => $inBarangay,
            'purok' => null,
            'candidates' => [],
            'message' => $inBarangay === true
                ? 'Coordinates are within the barangay, but no Purok boundary matched.'
                : 'No Purok boundary matched these coordinates.',
        ];
    }

    /**
     * @param  list<mixed>  $rings
     * @return array{valid: bool, error: ?string}
     */
    private function validatePolygonRings(array $rings, string $context = 'Polygon'): array
    {
        foreach ($rings as $ringIndex => $ring) {
            if (! is_array($ring) || ! array_is_list($ring) || count($ring) < 4) {
                return ['valid' => false, 'error' => "{$context} ring {$ringIndex} must contain at least 4 coordinate pairs."];
            }

            foreach ($ring as $pointIndex => $coord) {
                if (! is_array($coord) || count($coord) < 2 || ! is_numeric($coord[0] ?? null) || ! is_numeric($coord[1] ?? null)) {
                    return ['valid' => false, 'error' => "{$context} ring {$ringIndex} point {$pointIndex} must be [longitude, latitude]."];
                }

                $lng = (float) $coord[0];
                $lat = (float) $coord[1];

                if ($lng < -180.0 || $lng > 180.0) {
                    return ['valid' => false, 'error' => "{$context} ring {$ringIndex} point {$pointIndex} longitude out of range [-180, 180]."];
                }

                if ($lat < -90.0 || $lat > 90.0) {
                    return ['valid' => false, 'error' => "{$context} ring {$ringIndex} point {$pointIndex} latitude out of range [-90, 90]."];
                }
            }

            $first = $ring[0];
            $last = $ring[count($ring) - 1];
            $closed = abs((float) $first[0] - (float) $last[0]) <= self::RING_CLOSE_EPSILON
                && abs((float) $first[1] - (float) $last[1]) <= self::RING_CLOSE_EPSILON;

            if (! $closed) {
                return ['valid' => false, 'error' => "{$context} ring {$ringIndex} must be closed (first and last coordinate must match)."];
            }
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Evaluate a single GeoJSON Polygon (outer ring + optional interior holes).
     *
     * @param  list<list<array{0: float|int, 1: float|int}>>  $rings
     * @return array{inside: bool, on_boundary: bool}
     */
    private function evaluatePolygon(float $lat, float $lng, array $rings, float $toleranceMeters): array
    {
        if ($rings === []) {
            return ['inside' => false, 'on_boundary' => false];
        }

        $outerRing = $rings[0];
        $onOuterEdge = $this->isPointNearRingBoundary($lat, $lng, $outerRing, $toleranceMeters);

        if ($onOuterEdge) {
            return ['inside' => true, 'on_boundary' => true];
        }

        if (! $this->isPointInRing($lat, $lng, $outerRing)) {
            return ['inside' => false, 'on_boundary' => false];
        }

        // Check interior hole rings (indices 1..n).
        $ringCount = count($rings);
        for ($i = 1; $i < $ringCount; $i++) {
            $holeRing = $rings[$i];
            if ($this->isPointNearRingBoundary($lat, $lng, $holeRing, $toleranceMeters)) {
                return ['inside' => true, 'on_boundary' => true];
            }
            if ($this->isPointInRing($lat, $lng, $holeRing)) {
                return ['inside' => false, 'on_boundary' => false];
            }
        }

        return ['inside' => true, 'on_boundary' => false];
    }

    /**
     * Standard ray-casting algorithm on a closed ring of [lng, lat] pairs.
     *
     * @param  list<array{0: float|int, 1: float|int}>  $ring
     */
    private function isPointInRing(float $lat, float $lng, array $ring): bool
    {
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = (float) $ring[$i][0]; // longitude
            $yi = (float) $ring[$i][1]; // latitude
            $xj = (float) $ring[$j][0];
            $yj = (float) $ring[$j][1];

            $intersects = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-15) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Check whether (lat, lng) lies on or within $toleranceMeters of any segment of a ring.
     *
     * @param  list<array{0: float|int, 1: float|int}>  $ring
     */
    private function isPointNearRingBoundary(float $lat, float $lng, array $ring, float $toleranceMeters): bool
    {
        $count = count($ring);
        if ($count < 2) {
            return false;
        }

        $cosLat = max(0.01, cos(deg2rad($lat)));
        $metersPerDegLng = self::EARTH_METERS_PER_DEGREE_LAT * $cosLat;

        for ($i = 0; $i < $count - 1; $i++) {
            $x1 = ((float) $ring[$i][0] - $lng) * $metersPerDegLng;
            $y1 = ((float) $ring[$i][1] - $lat) * self::EARTH_METERS_PER_DEGREE_LAT;
            $x2 = ((float) $ring[$i + 1][0] - $lng) * $metersPerDegLng;
            $y2 = ((float) $ring[$i + 1][1] - $lat) * self::EARTH_METERS_PER_DEGREE_LAT;

            $dist = $this->pointToSegmentDistanceMeters($x1, $y1, $x2, $y2);
            if ($dist <= $toleranceMeters) {
                return true;
            }
        }

        return false;
    }

    /**
     * Minimum Euclidean distance in local meter-plane from origin (0,0) to segment (x1,y1)-(x2,y2).
     */
    private function pointToSegmentDistanceMeters(float $x1, float $y1, float $x2, float $y2): float
    {
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $lenSq = $dx * $dx + $dy * $dy;

        if ($lenSq <= 0.0) {
            return hypot($x1, $y1);
        }

        $t = max(0.0, min(1.0, (-$x1 * $dx - $y1 * $dy) / $lenSq));
        $projX = $x1 + $t * $dx;
        $projY = $y1 + $t * $dy;

        return hypot($projX, $projY);
    }
}
