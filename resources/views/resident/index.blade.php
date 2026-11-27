@extends('layouts.app')

@section('title', 'Barangay Calarian — Public Citizen Portal')

@section('hero')
  <section class="hero">
    <div class="hero-inner">
      <div>
        <h1 data-i18n="hero_title">Barangay Calarian Public Services</h1>
        <p data-i18n="hero_desc">
          Official digital gateway for residents and stakeholders of Barangay Calarian, City of Zamboanga.
          Access emergency assistance, report community infrastructure problems, file dispute complaints for Katarungang Pambarangay conciliation, or track ongoing cases.
        </p>
        <em class="hero-motto">Pusong May Malasakit, Serbisyong Tapat sa Mamamayan</em>
      </div>
      <div class="hero-seal" aria-hidden="true">
        <img src="{{ asset('storage/calarian-seal.png') }}" alt="Barangay Calarian Seal">
      </div>
    </div>
  </section>
@endsection

@section('content')
<div class="max-w-5xl mx-auto py-4 sm:py-8 px-2 sm:px-4">
  @if($errors->any())
    <div role="alert" class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm shadow-sm">
      <div class="flex items-center gap-2 font-bold mb-1">
        <i class="fa-solid fa-circle-exclamation text-red-600"></i> Please check the following:
      </div>
      <ul class="list-disc pl-5 space-y-1">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if(session('success'))
    <div role="alert" class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm shadow-sm flex items-center gap-2">
      <i class="fa-solid fa-circle-check text-emerald-600"></i>
      <span>{{ session('success') }}</span>
    </div>
  @endif

  <!-- 2x2 Grid on Desktop, 1 Column on Mobile -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">

    <!-- 1. Emergency Card -->
    <button
      type="button"
      onclick="openEmergencyModal()"
      class="group relative flex flex-col justify-between text-left p-6 sm:p-7 rounded-2xl bg-white border-2 border-red-200 hover:border-red-800 hover:bg-red-50 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 cursor-pointer overflow-hidden focus:outline-none focus:ring-2 focus:ring-red-800 focus:ring-offset-2"
    >
      <div class="absolute top-0 left-0 h-1.5 w-full bg-red-600"></div>

      <div>
        <div class="flex items-start justify-between gap-4 mb-4">
          <div class="w-14 h-14 rounded-xl bg-red-100 text-red-700 flex items-center justify-center text-2xl group-hover:bg-red-900 group-hover:text-white transition-colors duration-200 shadow-sm">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </div>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-red-100 text-red-800 border border-red-200">
            <i class="fa-solid fa-phone-volume text-[10px]"></i> Hotlines
          </span>
        </div>

        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 group-hover:text-red-900 transition-colors mb-2">
          Emergency / 911
        </h2>

        <p class="text-sm text-slate-600 leading-relaxed">
          Immediate life-threatening danger, crime in progress, medical distress, fire, or acute disasters.
          Access national 911 and 24/7 local emergency patrol hotlines.
        </p>
      </div>

      <div class="mt-6 pt-4 border-t border-red-100 flex items-center justify-between text-red-700 font-bold text-sm">
        <span>View Emergency Hotlines</span>
        <div class="w-9 h-9 rounded-full bg-red-100 group-hover:bg-red-900 group-hover:text-white flex items-center justify-center transition-all duration-200 group-hover:translate-x-1">
          <i class="fa-solid fa-arrow-right text-xs"></i>
        </div>
      </div>
    </button>

    <!-- 2. Report a Concern Card -->
    <a
      href="{{ route('reports.create') }}"
      class="group relative flex flex-col justify-between text-left p-6 sm:p-7 rounded-2xl bg-white border-2 border-amber-200 hover:border-amber-800 hover:bg-amber-50 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 overflow-hidden focus:outline-none focus:ring-2 focus:ring-amber-800 focus:ring-offset-2"
    >
      <div class="absolute top-0 left-0 h-1.5 w-full bg-amber-500"></div>

      <div>
        <div class="flex items-start justify-between gap-4 mb-4">
          <div class="w-14 h-14 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl group-hover:bg-amber-900 group-hover:text-white transition-colors duration-200 shadow-sm">
            <i class="fa-solid fa-bullhorn"></i>
          </div>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200">
            <i class="fa-solid fa-city text-[10px]"></i> Community
          </span>
        </div>

        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 group-hover:text-amber-900 transition-colors mb-2">
          Report a Concern
        </h2>

        <p class="text-sm text-slate-600 leading-relaxed">
          Broken streetlights, potholes, uncollected garbage, clogged drainage, stray animals, or public facility damage.
          Submit photo evidence, map location, and description.
        </p>
      </div>

      <div class="mt-6 pt-4 border-t border-amber-100 flex items-center justify-between text-amber-800 font-bold text-sm">
        <span>Report a Concern</span>
        <div class="w-9 h-9 rounded-full bg-amber-100 group-hover:bg-amber-900 group-hover:text-white flex items-center justify-center transition-all duration-200 group-hover:translate-x-1">
          <i class="fa-solid fa-arrow-right text-xs"></i>
        </div>
      </div>
    </a>

    <!-- 3. File a Dispute Complaint Card -->
    <a
      href="{{ route('complaint.create') }}"
      class="group relative flex flex-col justify-between text-left p-6 sm:p-7 rounded-2xl bg-white border-2 border-slate-300 hover:border-slate-800 hover:bg-slate-50 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 overflow-hidden focus:outline-none focus:ring-2 focus:ring-slate-800 focus:ring-offset-2"
    >
      <div class="absolute top-0 left-0 h-1.5 w-full bg-slate-800"></div>

      <div>
        <div class="flex items-start justify-between gap-4 mb-4">
          <div class="w-14 h-14 rounded-xl bg-slate-100 text-slate-800 flex items-center justify-center text-2xl group-hover:bg-slate-900 group-hover:text-white transition-colors duration-200 shadow-sm">
            <i class="fa-solid fa-scale-balanced"></i>
          </div>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-slate-200 text-slate-800 border border-slate-300">
            <i class="fa-solid fa-gavel text-[10px]"></i> Legal Dispute
          </span>
        </div>

        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 group-hover:text-slate-950 transition-colors mb-2">
          File a Complaint
        </h2>

        <p class="text-sm text-slate-600 leading-relaxed">
          Disputes with specific individuals, property boundary issues, unpaid debt claims, rental conflicts, or noise disturbances under the Katarungang Pambarangay conciliation process.
        </p>
      </div>

      <div class="mt-6 pt-4 border-t border-slate-200 flex items-center justify-between text-slate-900 font-bold text-sm">
        <span>File with Lupon</span>
        <div class="w-9 h-9 rounded-full bg-slate-200 group-hover:bg-slate-900 group-hover:text-white flex items-center justify-center transition-all duration-200 group-hover:translate-x-1">
          <i class="fa-solid fa-arrow-right text-xs"></i>
        </div>
      </div>
    </a>

    <!-- 4. Track a Report Card -->
    <a
      href="{{ route('track.index') }}"
      class="group relative flex flex-col justify-between text-left p-6 sm:p-7 rounded-2xl bg-white border-2 border-blue-200 hover:border-blue-800 hover:bg-blue-50 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 overflow-hidden focus:outline-none focus:ring-2 focus:ring-blue-800 focus:ring-offset-2"
    >
      <div class="absolute top-0 left-0 h-1.5 w-full bg-blue-600"></div>

      <div>
        <div class="flex items-start justify-between gap-4 mb-4">
          <div class="w-14 h-14 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-2xl group-hover:bg-blue-900 group-hover:text-white transition-colors duration-200 shadow-sm">
            <i class="fa-solid fa-magnifying-glass"></i>
          </div>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-200">
            <i class="fa-solid fa-timeline text-[10px]"></i> Live Status
          </span>
        </div>

        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 group-hover:text-blue-900 transition-colors mb-2">
          Track a Report
        </h2>

        <p class="text-sm text-slate-600 leading-relaxed">
          Check live progress on your submitted community report using your tracking code.
          View assigned municipal offices, provide additional info, or confirm resolution.
        </p>
      </div>

      <div class="mt-6 pt-4 border-t border-blue-100 flex items-center justify-between text-blue-700 font-bold text-sm">
        <span>Search Tracking Code</span>
        <div class="w-9 h-9 rounded-full bg-blue-100 group-hover:bg-blue-900 group-hover:text-white flex items-center justify-center transition-all duration-200 group-hover:translate-x-1">
          <i class="fa-solid fa-arrow-right text-xs"></i>
        </div>
      </div>
    </a>

  </div>
</div>

<!-- Emergency Hotlines Modal -->
<div id="emergency-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 sm:p-7 space-y-5 animate-in fade-in duration-200">
    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
      <div class="flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center text-lg">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </span>
        <div>
          <h3 class="text-lg font-bold text-slate-900">Emergency Hotlines</h3>
          <p class="text-xs text-slate-500">24/7 Immediate Response Numbers</p>
        </div>
      </div>
      <button type="button" onclick="closeEmergencyModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 flex items-center justify-center transition-colors">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-xs text-red-900 leading-relaxed flex items-start gap-2.5">
      <i class="fa-solid fa-bell-slash text-red-600 mt-0.5 text-sm flex-shrink-0"></i>
      <div>
        <strong>Note:</strong> Online portal requests are reviewed during official working hours. For immediate life-threatening incidents, please call directly.
      </div>
    </div>

    <div class="space-y-2.5">
      <!-- 911 National -->
      <a href="tel:911" class="flex items-center justify-between p-3.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold transition-all shadow-sm">
        <div class="flex items-center gap-3">
          <i class="fa-solid fa-phone-volume text-xl"></i>
          <div>
            <div class="text-sm">911 National Emergency</div>
            <div class="text-[11px] font-normal text-red-100">Police, Medical &amp; Fire Dispatch</div>
          </div>
        </div>
        <span class="px-2.5 py-1 bg-white/20 rounded-lg text-xs font-bold uppercase tracking-wider">Call 911</span>
      </a>

      @foreach($contacts as $contact)
        @php $num = trim((string) $contact->number); @endphp
        @if($num !== '' && stripos($num, 'N/A') === false && $num !== '911')
          <a href="tel:{{ preg_replace('/[^0-9+]/', '', $num) }}" class="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 hover:border-blue-400 hover:bg-blue-50/50 text-slate-800 transition-all">
            <div class="flex items-center gap-3">
              <span class="w-8 h-8 rounded-lg bg-slate-100 text-blue-600 flex items-center justify-center text-sm">
                <i class="fa-solid fa-phone"></i>
              </span>
              <span class="text-xs font-semibold">{{ $contact->name }}</span>
            </div>
            <span class="text-xs font-mono font-bold text-blue-600">{{ $num }}</span>
          </a>
        @endif
      @endforeach
    </div>

    <div class="pt-2 text-center">
      <button type="button" onclick="closeEmergencyModal()" class="w-full py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
        Close Window
      </button>
    </div>
  </div>
</div>

<script>
  function openEmergencyModal() {
    document.getElementById('emergency-modal').classList.remove('hidden');
  }
  function closeEmergencyModal() {
    document.getElementById('emergency-modal').classList.add('hidden');
  }

  // Close on outside click
  document.getElementById('emergency-modal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeEmergencyModal();
    }
  });

  // Close on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeEmergencyModal();
    }
  });
</script>
@endsection
