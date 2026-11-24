@extends('layouts.admin')

@section('title', 'System Backups')
@section('page-heading', 'System Backups')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="System Backups Management"
        subtitle="Generate database and storage snapshots, review automated archival history, and download recovery archives.">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.backups.run') }}">
                @csrf
                <x-ui.button type="submit" variant="primary">
                    <i class="fa-solid fa-cloud-arrow-up text-xs mr-1.5"></i>
                    <span>Run Backup Now</span>
                </x-ui.button>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 leading-relaxed flex items-start gap-2.5">
        <i class="fa-solid fa-shield-halved text-slate-400 mt-0.5 text-sm"></i>
        <div>
            <strong>Automated 14-Day Retention Policy:</strong> Backups archive the SQLite database, uploaded private transmittal attachments, and system configuration. Backups older than 14 days are automatically pruned during scheduled backup runs.
        </div>
    </div>

    <x-ui.card>
        <div class="overflow-hidden">
            <table class="w-full text-left border-collapse text-sm table-fixed responsive-card-table">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider bg-slate-50/50">
                        <th class="py-3 px-4 w-[35%]">Backup Archive</th>
                        <th class="py-3 px-4 w-[25%]">Creation Date</th>
                        <th class="py-3 px-4 w-[15%]">File Size</th>
                        <th class="py-3 px-4 w-[15%]">Status</th>
                        <th class="py-3 px-4 w-[10%] text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($backups as $b)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td data-label="Backup Archive" class="py-3.5 px-4 font-mono font-semibold text-slate-900 text-xs flex items-center gap-2">
                                <i class="fa-solid fa-file-zipper text-blue-600 text-base"></i>
                                <span>{{ $b['filename'] }}</span>
                            </td>
                            <td data-label="Creation Date" class="py-3.5 px-4 text-xs text-slate-600">
                                {{ $b['created_at']->format('Y-m-d H:i:s') }}
                                <span class="text-slate-400 text-[11px] block">({{ $b['created_at']->diffForHumans() }})</span>
                            </td>
                            <td data-label="File Size" class="py-3.5 px-4 text-xs font-semibold text-slate-700">
                                {{ $b['size'] }}
                            </td>
                            <td data-label="Status" class="py-3.5 px-4">
                                <x-ui.badge variant="success" dot>Completed</x-ui.badge>
                            </td>
                            <td data-label="Action" class="py-3.5 px-4 text-right">
                                <a href="{{ route('admin.backups.download', ['file' => $b['filename']]) }}" data-turbo="false"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition-colors">
                                    <i class="fa-solid fa-download text-xs"></i>
                                    <span>Download</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 text-sm">
                                No backup archives found. Click "Run Backup Now" to create your first archive.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
@endsection
