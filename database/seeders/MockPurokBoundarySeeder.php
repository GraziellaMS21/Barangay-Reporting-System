<?php

namespace Database\Seeders;

use App\Models\Purok;
use Illuminate\Database\Seeder;

/**
 * Development-only seeder creating 7 clearly labelled MOCK PUROK boundaries
 * inside the provisional Barangay Calarian polygon for local testing.
 *
 * NEVER run in production; guarded by app()->environment('local', 'testing').
 * Does NOT modify or overwrite existing official Purok records.
 */
class MockPurokBoundarySeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            $this->command?->warn('MockPurokBoundarySeeder is restricted to local and testing environments.');

            return;
        }

        foreach (self::mockDefinitions() as $item) {
            Purok::updateOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'boundary_geometry' => $item['boundary_geometry'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @return list<array{code: string, name: string, boundary_geometry: array<string, mixed>}>
     */
    public static function mockDefinitions(): array
    {
        return [
            [
                'code' => 'mock-purok-1',
                'name' => 'MOCK PUROK 1',
                'boundary_geometry' => self::rect(122.0210, 6.9270, 122.0255, 6.9310),
            ],
            [
                'code' => 'mock-purok-2',
                'name' => 'MOCK PUROK 2',
                'boundary_geometry' => self::rect(122.0260, 6.9235, 122.0290, 6.9260),
            ],
            [
                'code' => 'mock-purok-3',
                'name' => 'MOCK PUROK 3',
                'boundary_geometry' => self::rect(122.0292, 6.9235, 122.0320, 6.9260),
            ],
            [
                'code' => 'mock-purok-4',
                'name' => 'MOCK PUROK 4',
                'boundary_geometry' => self::rect(122.0322, 6.9235, 122.0355, 6.9265),
            ],
            [
                'code' => 'mock-purok-5',
                'name' => 'MOCK PUROK 5',
                'boundary_geometry' => self::rect(122.0357, 6.9235, 122.0400, 6.9270),
            ],
            [
                'code' => 'mock-purok-6',
                'name' => 'MOCK PUROK 6',
                'boundary_geometry' => self::rect(122.0322, 6.9200, 122.0365, 6.9230),
            ],
            [
                'code' => 'mock-purok-7',
                'name' => 'MOCK PUROK 7',
                'boundary_geometry' => self::rect(122.0367, 6.9185, 122.0410, 6.9230),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function rect(float $minLng, float $minLat, float $maxLng, float $maxLat): array
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
}
