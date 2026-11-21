<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'System Backups', 'owner' => 'WS1']);
    }

    public function run(Request $request)
    {
        return redirect()->route('admin.backups.index')->with('success', 'Backup triggered.');
    }

    public function download(string $file)
    {
        return response('stub-backup-content', 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$file}\"",
        ]);
    }
}
