<?php

namespace Tests\Feature;

use App\Listeners\Notifications\SendInfoRequested;
use App\Mail\InfoRequestedMail;
use App\Models\Permission;
use App\Models\Report;
use App\Models\User;
use App\Support\ReportStatus;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationDatabaseQueueTest extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        RefreshDatabaseState::$migrated = false;

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        RefreshDatabaseState::$migrated = false;
    }

    public function test_info_requested_executes_end_to_end_via_database_queue_worker(): void
    {
        $originalQueue = config('queue.default');
        config(['queue.default' => 'database']);

        try {
            Mail::fake();

            $staff = User::factory()->create([
                'role' => 'staff',
                'is_active' => true,
                'email' => 'staff@calarian.test',
            ]);
            $staff->permissions()->sync([
                Permission::firstOrCreate(['slug' => 'reports.view'], ['label' => 'View Resident Reports Queue', 'group' => 'reports'])->id,
            ]);

            $report = Report::factory()->create([
                'status' => ReportStatus::UNDER_REVIEW,
                'is_anonymous' => false,
                'reporter_email' => 'resident@example.com',
            ]);

            $this->actingAs($staff)
                ->post(route('admin.reports.info-requests.store', $report), [
                    'question' => 'Please confirm the nearest house number.',
                ])
                ->assertRedirect();

            // 1. Job is inserted into the real `jobs` table and not yet sent before worker runs
            $this->assertSame(1, DB::table('jobs')->count());
            $jobRow = DB::table('jobs')->first();
            $this->assertNotNull($jobRow);
            $this->assertStringContainsString(SendInfoRequested::class, str_replace('\\\\', '\\', (string) $jobRow->payload));

            Mail::assertNothingSent();
            $this->assertDatabaseCount('notification_logs', 0);

            // 2. Run the real Laravel queue worker for exactly one job
            $exitCode = Artisan::call('queue:work', [
                'connection' => 'database',
                '--once' => true,
                '--tries' => 1,
            ]);

            $this->assertSame(0, $exitCode);

            // 3. Job is removed from `jobs` after processing
            $this->assertSame(0, DB::table('jobs')->count());
            $this->assertSame(0, DB::table('failed_jobs')->count());

            // 4. Expected Mailable was sent once and logged in `notification_logs`
            Mail::assertSent(InfoRequestedMail::class, 1);
            Mail::assertSent(InfoRequestedMail::class, function (InfoRequestedMail $mail): bool {
                return $mail->hasTo('resident@example.com')
                    && $mail->infoRequest->question === 'Please confirm the nearest house number.';
            });

            $this->assertDatabaseCount('notification_logs', 1);
            $this->assertDatabaseHas('notification_logs', [
                'report_id' => $report->id,
                'type' => 'info_requested',
                'recipient' => 'resident@example.com',
                'status' => 'sent',
                'error' => null,
            ]);
        } finally {
            config(['queue.default' => $originalQueue]);
        }
    }
}
