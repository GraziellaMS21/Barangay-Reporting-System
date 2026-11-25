@extends('layouts.app')
@section('title', 'Complaint Request Received - Barangay Calarian')

@section('hero')
  <section class="hero">
    <div class="hero-inner">
      <div>
        <h1>Complaint Request Received</h1>
        <p>Your dispute complaint intake has been logged. Please review the official instructions below to complete official docketing under Katarungang Pambarangay rules.</p>
        <em class="hero-motto">Katarungang Pambarangay · Pusong May Malasakit, Serbisyong Tapat sa Mamamayan</em>
      </div>
      <div class="hero-seal" aria-hidden="true"><img src="{{ asset('storage/calarian-seal.png') }}" alt=""></div>
    </div>
  </section>
@endsection

@section('content')
  <div style="max-width: 680px; margin: 0 auto;">
    <div style="border: 1px solid #e2e8f0; border-radius: 20px; padding: 2.25rem; background: #ffffff; box-shadow: 0 4px 6px -1px rgba(11, 31, 75, 0.04), 0 20px 30px -4px rgba(11, 31, 75, 0.08); text-align: center;">

      <!-- Success Icon -->
      <div style="width: 64px; height: 64px; border-radius: 50%; background: #eff6ff; color: #1d4ed8; display: grid; place-items: center; margin: 0 auto 1.25rem; font-size: 1.75rem;">
        <i class="fa-solid fa-file-circle-check"></i>
      </div>

      <h2 style="font-size: 1.45rem; font-weight: 800; color: var(--ink); margin: 0 0 0.5rem;">
        Intake Request Logged
      </h2>

      <p style="font-size: 0.95rem; color: #64748b; line-height: 1.6; margin: 0 0 1.5rem;">
        Your dispute complaint request has been forwarded to the <strong>Office of the Lupong Tagapamayapa</strong> at Barangay Calarian.
      </p>

      <!-- Legal Instructions Card -->
      <div style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 14px; padding: 1.25rem 1.5rem; text-align: left; margin-bottom: 1.75rem;">
        <h3 style="font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 0.65rem; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-scale-balanced text-blue-600"></i> Next Steps: Review &amp; In-Person Sworn Signing
        </h3>
        <p style="font-size: 0.88rem; color: #334155; line-height: 1.6; margin: 0 0 0.75rem;">
          In accordance with the <strong>Katarungang Pambarangay Law (Republic Act No. 7160)</strong>, your request will follow these steps before a mediation hearing is set:
        </p>
        <ol style="font-size: 0.86rem; color: #334155; line-height: 1.6; margin: 0 0 0.85rem; padding-left: 1.25rem;">
          <li style="margin-bottom: 0.35rem;"><strong>Intake Review &amp; Docketing:</strong> Barangay staff will review your complaint details and docket it onto the official KP Form No. 1.</li>
          <li style="margin-bottom: 0.35rem;"><strong>In-Person Sworn Signing:</strong> Visit Barangay Calarian Hall with a valid ID to sign the printed formal complaint in person before the Punong Barangay or Barangay Secretary.</li>
          <li><strong>Hearing &amp; Summons Issuance:</strong> Once your signed complaint is received, the mediation hearing will be scheduled and official summons (KP Form No. 9) will be issued to the respondent.</li>
        </ol>
        <div style="margin-top: 0.65rem; padding: 0.65rem 0.85rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; color: #475569;">
          <div><i class="fa-solid fa-clock text-slate-500 mr-1.5"></i> Monday – Friday: 8:00 AM – 5:00 PM</div>
          <div style="margin-top: 0.25rem;"><i class="fa-solid fa-location-dot text-slate-500 mr-1.5"></i> Barangay Calarian Hall, Calarian, Zamboanga City</div>
        </div>
      </div>

      <!-- Privacy & Protection Safeguard Notice -->
      <div style="background: #f1f5f9; border-radius: 10px; padding: 0.85rem 1rem; text-align: left; margin-bottom: 2rem; font-size: 0.82rem; color: #475569; display: flex; align-items: flex-start; gap: 0.6rem;">
        <i class="fa-solid fa-lock text-slate-600 mt-0.5" aria-hidden="true"></i>
        <span>
          <strong>Data Privacy Protection:</strong> To safeguard the dignity and legal rights of all parties, dispute complaints are strictly confidential and do not appear on public tracking pages or community transparency maps.
        </span>
      </div>

      <!-- Navigation Actions -->
      <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
        <a href="{{ route('reports.create') }}" class="btn btn-primary" style="background: #0f172a; color: #ffffff; font-weight: 700; padding: 0.65rem 1.5rem; border-radius: 999px;">
          <i class="fa-solid fa-house mr-1.5"></i> Return to Home
        </a>
        <a href="{{ route('track.index') }}" class="btn btn-outline" style="border-radius: 999px; padding: 0.65rem 1.5rem;">
          <i class="fa-solid fa-magnifying-glass mr-1.5"></i> Track a Community Report
        </a>
      </div>

    </div>
  </div>
@endsection
