<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function index(): View
    {
        $backupDir = storage_path('app/private/backups');
        $backups = [];

        if (File::isDirectory($backupDir)) {
            $files = File::files($backupDir);
            foreach ($files as $file) {
                if ($file->getExtension() === 'zip') {
                    $mtime = $file->getMTime();
                    $bytes = $file->getSize();
                    $backups[] = [
                        'filename' => $file->getFilename(),
                        'size' => $bytes > 1048576 ? round($bytes / 1048576, 2).' MB' : round($bytes / 1024, 2).' KB',
                        'created_at' => Carbon::createFromTimestamp($mtime),
                        'status' => 'Completed',
                    ];
                }
            }
        }

        usort($backups, fn ($a, $b) => $b['created_at']->timestamp <=> $a['created_at']->timestamp);

        return view('admin.settings.backups.index', compact('backups'));
    }

    public function run(Request $request): RedirectResponse
    {
        Artisan::call('app:backup');

        return redirect()->route('admin.backups.index')->with('success', 'System backup generated successfully.');
    }

    public function download(string $file): BinaryFileResponse
    {
        $safeFilename = basename($file);
        $fullPath = storage_path("app/private/backups/{$safeFilename}");

        if (! File::exists($fullPath)) {
            abort(404, 'Backup archive not found.');
        }

        return response()->download($fullPath, $safeFilename, [
            'Content-Type' => 'application/zip',
        ]);
    }
}
