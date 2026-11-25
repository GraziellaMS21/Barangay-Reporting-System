@extends('layouts.admin')

@section('title', 'Administration')
@section('page-heading', 'Administration')

@section('content')
<div class="page-heading">
  <h2>Administration settings</h2>
  <p>Manage staff access, report categories, routing, priority, and resident-facing emergency information.</p>
</div>

<div class="detail-grid">
  <a class="card settings-link" href="{{ route('admin.settings.users.index') }}">
    <h2 class="card-title"><i class="fa-solid fa-users" aria-hidden="true"></i> Users</h2>
    <p>Create, update, and deactivate staff and administrator accounts.</p>
  </a>
  <a class="card settings-link" href="{{ route('admin.settings.categories.index') }}">
    <h2 class="card-title"><i class="fa-solid fa-tags" aria-hidden="true"></i> Categories</h2>
    <p>Set category risk weights and their default offices.</p>
  </a>
  <a class="card settings-link" href="{{ route('admin.settings.offices.index') }}">
    <h2 class="card-title"><i class="fa-solid fa-building" aria-hidden="true"></i> Manage offices and agencies</h2>
    <p>Maintain internal barangay offices and external agencies for routing and referrals.</p>
  </a>
  <a class="card settings-link" href="{{ route('admin.settings.routing-rules.index') }}">
    <h2 class="card-title"><i class="fa-solid fa-route" aria-hidden="true"></i> Routing rules</h2>
    <p>Assign a responsible office and jurisdiction note to each category.</p>
  </a>
  <a class="card settings-link" href="{{ route('admin.settings.priority-weights.index') }}">
    <h2 class="card-title"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i> Priority weights</h2>
    <p>Adjust scoring weights and restore the defaults.</p>
  </a>
  <a class="card settings-link" href="{{ route('admin.settings.emergency-contacts.index') }}">
    <h2 class="card-title"><i class="fa-solid fa-phone" aria-hidden="true"></i> Emergency contacts</h2>
    <p>Manage the active public hotline directory.</p>
  </a>
  <a class="card settings-link" href="{{ route('admin.settings.emergency-alert-emails.index') }}">
    <h2 class="card-title"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Emergency alert emails</h2>
    <p>Maintain the administrator and tanod recipient list for emergency alerts.</p>
  </a>
  <a class="card settings-link" href="{{ route('admin.settings.sensitive-places.index') }}">
    <h2 class="card-title"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Sensitive places</h2>
    <p>Maintain locations used by the priority score.</p>
  </a>
  <a class="card settings-link" href="{{ route('admin.settings.ai.edit') }}">
    <h2 class="card-title"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> AI Integration (Gemini)</h2>
    <p>Configure the Gemini model version and encrypted API key override.</p>
  </a>
  <a class="card settings-link" href="{{ route('admin.audit-log.index') }}">
    <h2 class="card-title"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Audit log</h2>
    <p>Review setting changes, staff overrides, and report status history.</p>
  </a>
</div>
@endsection
