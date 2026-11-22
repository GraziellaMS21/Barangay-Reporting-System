<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_admin_can_view_backups_list(): void
    {
        $response = $this->get(route('admin.backups.index'));
        $response->assertStatus(200);
        $response->assertSee('System Backups Management');
    }

    public function test_artisan_backup_command_runs_successfully(): void
    {
        $exitCode = Artisan::call('app:backup');
        $this->assertSame(0, $exitCode);

        $backupDir = storage_path('app/private/backups');
        $this->assertTrue(File::isDirectory($backupDir));

        $files = File::files($backupDir);
        $this->assertNotEmpty($files);
    }

    public function test_admin_can_trigger_backup_via_web(): void
    {
        $response = $this->post(route('admin.backups.run'));
        $response->assertRedirect(route('admin.backups.index'));
        $response->assertSessionHas('success');
    }

    public function test_admin_can_download_backup_archive(): void
    {
        Artisan::call('app:backup');
        $backupDir = storage_path('app/private/backups');
        $files = File::files($backupDir);
        $this->assertNotEmpty($files);

        $fileName = $files[0]->getFilename();

        $response = $this->get(route('admin.backups.download', ['file' => $fileName]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
    }
}
