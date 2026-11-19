@extends('layouts.admin')

@section('title', 'Manage offices and agencies')
@section('page-heading', 'Manage offices and agencies')

@section('content')
<div class="page-heading">
  <h2>Manage offices and agencies</h2>
  <p>Maintain the destination directory of internal barangay offices (Mode A) and external agencies (Mode B) used by routing and referrals.</p>
  <div class="contact-actions">
    <a class="btn btn-gold btn-small" href="{{ route('admin.settings.offices.create') }}">
      <i class="fa-solid fa-plus" aria-hidden="true"></i> Add office or agency
    </a>
    <a class="btn btn-outline btn-small" href="{{ route('admin.referrals.index') }}">
      <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Referrals and routing
    </a>
  </div>
</div>

@include('admin.settings.partials.validation-errors')

<section class="card" aria-label="Offices and agencies">
  <div class="table-wrap">
    <table class="data-table data-table-cards">
      <thead>
        <tr>
          <th scope="col">Name &amp; Description</th>
          <th scope="col">Type</th>
          <th scope="col">Contact / Default Assignee</th>
          <th scope="col">Status</th>
          <th scope="col">Usage</th>
          <th scope="col"><span class="sr-only">Actions</span></th>
        </tr>
      </thead>
      <tbody>
        @forelse($offices as $office)
          @php
            $isInUse = ($office->categories_count + $office->routing_rules_count + $office->reports_count + $office->referrals_count) > 0;
            $isExternal = $office->directoryType() === 'external';
          @endphp
          <tr>
            <td data-label="Name">
              <strong>{{ $office->name }}</strong>
              @if($office->description)
                <div class="text-sm text-muted">{{ $office->description }}</div>
              @endif
            </td>
            <td data-label="Type">
              @if($isExternal)
                <span class="review-badge review-badge-amber">External agency</span>
              @else
                <span class="review-badge review-badge-blue">Within barangay</span>
              @endif
            </td>
            <td data-label="Contact / Default Assignee">
              @if(!$isExternal && $office->defaultAssignee)
                <div><strong>Default assignee:</strong> {{ $office->defaultAssignee->name }}</div>
              @endif
              @if($office->contact_person || $office->contact_phone || $office->contact_email || $office->contact)
                <div class="text-sm text-muted">
                  {{ collect([$office->contact_person, $office->contact_phone, $office->contact_email, $office->contact])->filter()->unique()->implode(' · ') }}
                </div>
              @elseif($isExternal || !$office->defaultAssignee)
                <span class="text-muted">No contact details</span>
              @endif
            </td>
            <td data-label="Status">
              <span class="contact-state {{ $office->is_active ? 'is-active' : 'is-inactive' }}">
                {{ $office->is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td data-label="Usage">
              <span class="text-sm text-muted">
                {{ $office->reports_count }} report{{ $office->reports_count === 1 ? '' : 's' }} · {{ $office->referrals_count }} referral{{ $office->referrals_count === 1 ? '' : 's' }}
              </span>
              @if($isInUse)
                <div><small class="text-muted">In use · cannot delete</small></div>
              @endif
            </td>
            <td data-label="Actions">
              <div class="contact-actions">
                <a class="btn btn-outline btn-small" href="{{ route('admin.settings.offices.edit', $office) }}">
                  <i class="fa-solid fa-pen" aria-hidden="true"></i> Edit
                </a>
                <form method="POST" action="{{ route('admin.settings.offices.toggle-active', $office) }}">
                  @csrf
                  @method('PATCH')
                  <input type="hidden" name="is_active" value="{{ $office->is_active ? '0' : '1' }}">
                  <button type="submit" class="btn btn-outline btn-small">
                    {{ $office->is_active ? 'Deactivate' : 'Activate' }}
                  </button>
                </form>
                @if(!$isInUse)
                  <form method="POST" action="{{ route('admin.settings.offices.destroy', $office) }}"
                        data-confirm="Delete {{ $office->name }}?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-small">Delete</button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="table-empty" data-label="">No offices or agencies have been added.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>
@endsection
