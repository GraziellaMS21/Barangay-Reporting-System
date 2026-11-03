@extends('layouts.admin')

@section('title', 'Settings')
@section('page-heading', 'Settings')

@section('content')
<div class="page-heading">
  <h2>Administration settings</h2>
  <p>Maintain categories, offices, routing rules, emergency contacts, and weights.</p>
</div>

<div class="detail-grid">
  <section class="card">
    <h2 class="card-title"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i> Priority weights</h2>
    <form method="POST" action="{{ route('admin.settings.priority') }}" class="status-form">
      @csrf
      <label for="priority_urgency">Urgency weight
        <input id="priority_urgency" name="priority[urgency]" type="number" min="0" value="{{ App\Models\Setting::value('priority.urgency', 20) }}">
      </label>
      <label for="priority_category">Category weight
        <input id="priority_category" name="priority[category]" type="number" min="0" value="{{ App\Models\Setting::value('priority.category', 10) }}">
      </label>
      <label for="priority_sensitive">Sensitive place weight
        <input id="priority_sensitive" name="priority[sensitive_place]" type="number" min="0" value="{{ App\Models\Setting::value('priority.sensitive_place', 25) }}">
      </label>
      <label for="priority_support">Supporting reports weight
        <input id="priority_support" name="priority[supporting_reports]" type="number" min="0" value="{{ App\Models\Setting::value('priority.supporting_reports', 8) }}">
      </label>
      <label for="priority_age">Age in days weight
        <input id="priority_age" name="priority[age_days]" type="number" min="0" value="{{ App\Models\Setting::value('priority.age_days', 2) }}">
      </label>
      <button type="submit" class="btn btn-gold">Save weights</button>
    </form>
  </section>

  <section class="card">
    <h2 class="card-title"><i class="fa-solid fa-folder-tree" aria-hidden="true"></i> Categories</h2>
    <ul class="stacked-list">
      @foreach($categories as $category)
        <li><span>{{ $category->name }}</span><strong>{{ $category->risk_weight }}</strong></li>
      @endforeach
    </ul>
  </section>
</div>

<section class="card">
  <h2 class="card-title"><i class="fa-solid fa-building" aria-hidden="true"></i> Offices and routing rules</h2>
  <table class="data-table data-table-cards">
    <thead>
      <tr>
        <th>Category</th>
        <th>Office</th>
        <th>Within authority</th>
      </tr>
    </thead>
    <tbody>
      @foreach($routingRules as $rule)
        <tr>
          <td data-label="Category">{{ $rule->category->name }}</td>
          <td data-label="Office">{{ $rule->office->name }}</td>
          <td data-label="Within authority">{{ $rule->within_authority ? 'Yes' : 'No' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</section>
@endsection
