@extends('layouts.admin')

@section('title', 'Categories')
@section('page-heading', 'Categories')

@section('content')
<div class="page-heading">
  <div>
    <h2>Report categories</h2>
    <p>Manage category risk values, default routing offices, and SLA response targets. Deactivate categories to remove them from new resident reports without affecting historical records.</p>
  </div>
  <div>
    <a class="btn btn-gold btn-small" href="{{ route('admin.settings.categories.create') }}">
      <i class="fa-solid fa-plus" aria-hidden="true"></i> Add category
    </a>
  </div>
</div>

@include('admin.settings.partials.validation-errors')

@if($categories->isEmpty())
  <section class="card">
    <p class="table-empty" style="text-align:center;padding:2.5rem;color:var(--muted,#64748b);">No categories have been created.</p>
  </section>
@else
  <div class="categories-grid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(320px, 1fr));gap:1rem;">
    @foreach($categories as $category)
      <article class="card category-card" style="display:flex;flex-direction:column;justify-content:space-between;border-top:3px solid {{ $category->is_active ? 'var(--blue, #2563eb)' : 'var(--line, #cbd5e1)' }};">
        <div>
          <!-- Header -->
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;margin-bottom:0.75rem;">
            <div>
              <h3 style="font-size:1.05rem;font-weight:700;margin:0 0 0.25rem 0;color:var(--ink,#0f172a);">
                {{ $category->name }}
              </h3>
              <code style="font-size:0.75rem;padding:0.15rem 0.4rem;background:var(--surface-muted,#f1f5f9);border-radius:0.25rem;color:var(--muted,#64748b);">
                {{ $category->slug }}
              </code>
            </div>
            <span class="contact-state {{ $category->is_active ? 'is-active' : 'is-inactive' }}" style="white-space:nowrap;">
              {{ $category->is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>

          <!-- Configuration Attributes -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.6rem;padding:0.75rem;background:var(--surface-muted,#f8fafc);border-radius:0.5rem;font-size:0.83rem;margin-bottom:0.75rem;">
            <div>
              <span class="text-muted" style="display:block;font-size:0.73rem;text-transform:uppercase;letter-spacing:0.04em;font-weight:600;">Risk weight</span>
              <strong style="color:var(--ink,#0f172a);font-size:0.95rem;">{{ $category->risk_weight }}</strong>
              <small class="text-muted" style="display:block;font-size:0.7rem;">priority multiplier</small>
            </div>
            <div>
              <span class="text-muted" style="display:block;font-size:0.73rem;text-transform:uppercase;letter-spacing:0.04em;font-weight:600;">SLA target</span>
              <strong style="color:var(--ink,#0f172a);font-size:0.95rem;">{{ $category->sla_hours ?? 72 }}h</strong>
              <small class="text-muted" style="display:block;font-size:0.7rem;">resolution target</small>
            </div>
            <div style="grid-column:1 / -1;border-top:1px solid var(--line,#e2e8f0);padding-top:0.4rem;">
              <span class="text-muted" style="display:block;font-size:0.73rem;text-transform:uppercase;letter-spacing:0.04em;font-weight:600;">Default office</span>
              @if($category->defaultOffice)
                <div style="display:flex;align-items:center;gap:0.4rem;margin-top:0.15rem;">
                  <i class="fa-solid fa-building" style="font-size:0.75rem;color:var(--blue,#2563eb);" aria-hidden="true"></i>
                  <span style="font-weight:600;color:var(--ink,#0f172a);">{{ $category->defaultOffice->name }}</span>
                </div>
              @else
                <span class="text-muted" style="font-style:italic;">Not set (routes via rules)</span>
              @endif
            </div>
          </div>

          <!-- Question Schema Preview Drawer -->
          @php
            $questions = is_array($category->question_schema) ? $category->question_schema : [];
            $questionCount = count($questions);
          @endphp
          <details class="category-questions-drawer" style="margin-top:0.5rem;background:#ffffff;border:1px solid var(--line,#e2e8f0);border-radius:0.375rem;font-size:0.8rem;overflow:hidden;">
            <summary style="padding:0.5rem 0.65rem;cursor:pointer;font-weight:600;display:flex;align-items:center;justify-content:space-between;background:var(--surface-muted,#f8fafc);user-select:none;">
              <span style="display:flex;align-items:center;gap:0.4rem;color:var(--ink,#0f172a);">
                <i class="fa-solid fa-list-check" style="color:var(--blue,#2563eb);font-size:0.78rem;" aria-hidden="true"></i>
                Category questions
                <span style="display:inline-block;padding:0.05rem 0.35rem;border-radius:999px;font-size:0.7rem;background:{{ $questionCount > 0 ? 'var(--blue,#2563eb)' : 'var(--line,#cbd5e1)' }};color:#ffffff;">
                  {{ $questionCount }}
                </span>
              </span>
              <span class="text-muted" style="font-size:0.72rem;font-weight:400;">
                {{ $questionCount > 0 ? 'View details' : 'Standard fields' }}
              </span>
            </summary>
            <div style="padding:0.6rem 0.75rem;border-top:1px solid var(--line,#e2e8f0);">
              @if($questionCount > 0)
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.4rem;">
                  @foreach($questions as $q)
                    <li style="padding-bottom:0.4rem;border-bottom:1px dashed var(--line,#e2e8f0);">
                      <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">
                        <strong style="color:var(--ink,#0f172a);">{{ $q['label'] ?? $q['name'] ?? 'Question' }}</strong>
                        @if(!empty($q['required']))
                          <span style="font-size:0.68rem;padding:0.1rem 0.3rem;background:#fee2e2;color:#991b1b;border-radius:0.2rem;font-weight:600;">Required</span>
                        @else
                          <span style="font-size:0.68rem;padding:0.1rem 0.3rem;background:var(--surface-muted,#f1f5f9);color:var(--muted,#64748b);border-radius:0.2rem;">Optional</span>
                        @endif
                      </div>
                      @if(!empty($q['options']))
                        <div class="text-muted" style="font-size:0.72rem;margin-top:0.15rem;">
                          Options: {{ implode(', ', (array) $q['options']) }}
                        </div>
                      @endif
                    </li>
                  @endforeach
                </ul>
              @else
                <p class="text-muted" style="margin:0;font-size:0.76rem;line-height:1.4;">
                  Standard fields apply (description, photo, location, consent). No category-specific questions configured.
                </p>
              @endif
            </div>
          </details>
        </div>

        <!-- Action Footer -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:1rem;padding-top:0.75rem;border-top:1px solid var(--line,#e2e8f0);">
          <span class="text-muted" style="font-size:0.72rem;">ID #{{ $category->id }}</span>
          <a class="btn btn-outline btn-small" href="{{ route('admin.settings.categories.edit', $category) }}">
            <i class="fa-solid fa-pen" aria-hidden="true" style="margin-right:0.3rem;"></i> Edit
          </a>
        </div>
      </article>
    @endforeach
  </div>
@endif
@endsection
