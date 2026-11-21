<form action="{{ route('admin.search') }}" method="GET" class="relative flex items-center">
    <div class="relative w-full max-w-xs">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
            <i class="fa-solid fa-magnifying-glass text-xs"></i>
        </span>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search reports, cases..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-100 hover:bg-slate-200/70 focus:bg-white text-slate-800 rounded-lg border border-transparent focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors">
    </div>
</form>
