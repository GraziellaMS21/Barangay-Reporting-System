@extends('layouts.admin')

@section('title', 'Offices')
@section('page-heading', 'Offices')

@section('content')
<div class="page-heading">
  <h2>Responsible offices</h2>
  <p>Maintain internal and external offices used by category defaults, routing rules, and referrals.</p>
  <a class="btn btn-gold btn-small" href="{{ route('admin.settings.offices.create') }}">
    <i class="fa-solid fa-plus" aria-hidden="true"></i> Add office
  </a>
</div>

@include('admin.settings.partials.validation-errors')

<section class="emergency-contact-list" aria-label="Offices">
  @forelse($offices as $office)
    @php($isInUse = $office->categories_count + $office->routing_rules_count + $office->reports_count + $office->referrals_count > 0)
    <article class="card emergency-contact-card">
      <div>
        <h2>{{ $office->name }}</h2>
        <p>{{ $office->type }} · {{ $office->is_internal ? 'Internal' : 'External' }}</p>
        <p>{{ $office->contact ?: 'No contact details' }}</p>
        @if($isInUse)
          <span class="contact-state is-active">In use · cannot delete</span>
        @endif
      </div>
      <div class="contact-actions">
        <a class="btn btn-outline btn-small" href="{{ route('admin.settings.offices.edit', $office) }}">Edit</a>
        @if(!$isInUse)
          <form method="POST" action="{{ route('admin.settings.offices.destroy', $office) }}"
            data-confirm="Delete {{ $office->name }}?">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-small">Delete</button>
          </form>
        @endif
      </div>
    </article>
  @empty
    <div class="card empty-state">No offices have been added.</div>
  @endforelse
</section>
@endsection
