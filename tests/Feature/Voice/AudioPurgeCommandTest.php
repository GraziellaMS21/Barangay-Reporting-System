<?php

namespace Tests\Feature\Voice;

use App\Models\Report;
use App\Models\ReportAudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AudioPurgeCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_purge_command_deletes_expired_and_orphaned_audio_files(): void
    {
        // 1. Expired audio (> retention period)
        $expiredPath = 'audio/2026/01/expired.webm';
        Storage::disk('private')->put($expiredPath, 'audio-data-expired');
        $expiredAudio = ReportAudio::create([
            'token' => '00000000-0000-0000-0000-000000000001',
            'origin' => 'resident',
            'disk_path' => $expiredPath,
            'mime' => 'audio/webm',
            'size_bytes' => 100,
            'expires_at' => now()->subDay(),
        ]);

        // 2. Orphaned draft audio (> 24 hours unattached to report)
        $orphanedPath = 'audio/2026/01/orphaned.webm';
        Storage::disk('private')->put($orphanedPath, 'audio-data-orphaned');
        $orphanedAudio = ReportAudio::create([
            'token' => '00000000-0000-0000-0000-000000000002',
            'origin' => 'resident',
            'disk_path' => $orphanedPath,
            'mime' => 'audio/webm',
            'size_bytes' => 100,
            'report_id' => null,
            'expires_at' => now()->addDays(90),
        ]);
        ReportAudio::where('id', $orphanedAudio->id)->update(['created_at' => now()->subHours(25)]);

        // 3. Active valid audio (should NOT be purged)
        $report = Report::factory()->create();
        $activePath = 'audio/2026/01/active.webm';
        Storage::disk('private')->put($activePath, 'audio-data-active');
        $activeAudio = ReportAudio::create([
            'token' => '00000000-0000-0000-0000-000000000003',
            'origin' => 'resident',
            'disk_path' => $activePath,
            'mime' => 'audio/webm',
            'size_bytes' => 100,
            'report_id' => $report->id,
            'expires_at' => now()->addDays(89),
        ]);

        // Run the purge command
        $this->artisan('app:purge-audio')
            ->expectsOutput('Starting audio retention purge...')
            ->expectsOutput('Purged 2 expired/orphaned audio file(s).')
            ->assertSuccessful();

        // Expired and orphaned files removed from disk
        Storage::disk('private')->assertMissing($expiredPath);
        Storage::disk('private')->assertMissing($orphanedPath);

        // Active file preserved
        Storage::disk('private')->assertExists($activePath);

        // Check records updated
        $this->assertEquals('[purged]', $expiredAudio->fresh()->disk_path);
        $this->assertEquals('[purged]', $orphanedAudio->fresh()->disk_path);
        $this->assertEquals($activePath, $activeAudio->fresh()->disk_path);
    }
}
