@extends('layouts.admin')
@section('title', 'Log Phone Report')
@section('page-heading', 'Log phone report')

@section('content')
<div style="max-width:800px;margin:0 auto;padding-bottom:3rem;">
  <div class="page-heading" style="margin-bottom:1.5rem;">
    <div style="display:flex;align-items:center;gap:0.75rem;">
      <a href="{{ route('admin.reports.index') }}" class="btn btn-outline btn-small" style="text-decoration:none;">
        <i class="fa-solid fa-arrow-left"></i> Back to queue
      </a>
      <h2 style="margin:0;font-size:1.5rem;font-weight:700;color:var(--ink);">Log Hotline Phone Report</h2>
    </div>
    <p style="margin:0.25rem 0 0;color:#64748b;font-size:0.9rem;">
      Record an incoming phone complaint taken by barangay staff. Mandatory caller verbal consent must be obtained before filing.
    </p>
  </div>

  <form method="POST" action="{{ route('admin.reports.phone.store') }}" style="background:#fff;border:1px solid var(--line);border-radius:0.75rem;padding:1.75rem;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
    @csrf

    {{-- 1. Verbal Consent Box (Mandatory) --}}
    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.5rem;padding:1.15rem;margin-bottom:1.5rem;">
      <label style="display:flex;align-items:flex-start;gap:0.75rem;cursor:pointer;">
        <input type="checkbox" name="verbal_consent" value="1" required {{ old('verbal_consent') ? 'checked' : '' }}
               style="margin-top:0.2rem;accent-color:var(--blue);width:1.25rem;height:1.25rem;cursor:pointer;">
        <div>
          <strong style="color:#1e40af;font-size:0.95rem;display:block;">
            Caller Verbal Consent Obtained <span style="color:var(--red);">*</span>
          </strong>
          <p style="margin:0.25rem 0 0;font-size:0.85rem;color:#1e3a8a;line-height:1.4;">
            I confirm that I informed the caller that this report will be officially recorded and processed by Barangay Calarian, and they gave verbal consent to proceed under Republic Act 10173 (Data Privacy Act).
          </p>
        </div>
      </label>
      @error('verbal_consent')
        <p style="color:var(--red);font-size:0.85rem;margin:0.5rem 0 0;font-weight:600;">{{ $message }}</p>
      @enderror
    </div>

    {{-- 2. Caller Information --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;">
      <div>
        <label for="caller_name" style="display:block;font-size:0.875rem;font-weight:600;color:var(--ink);margin-bottom:0.35rem;">
          Caller Name <span style="color:#64748b;font-weight:400;">(leave blank if anonymous)</span>
        </label>
        <input type="text" id="caller_name" name="caller_name" value="{{ old('caller_name') }}"
               placeholder="Juan Dela Cruz"
               style="width:100%;padding:0.6rem 0.75rem;border:1px solid var(--line);border-radius:0.375rem;font-size:0.9rem;">
        @error('caller_name')
          <p style="color:var(--red);font-size:0.8rem;margin-top:0.25rem;">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label for="caller_phone" style="display:block;font-size:0.875rem;font-weight:600;color:var(--ink);margin-bottom:0.35rem;">
          Caller Phone / Mobile
        </label>
        <input type="text" id="caller_phone" name="caller_phone" value="{{ old('caller_phone') }}"
               placeholder="09171234567"
               style="width:100%;padding:0.6rem 0.75rem;border:1px solid var(--line);border-radius:0.375rem;font-size:0.9rem;">
        @error('caller_phone')
          <p style="color:var(--red);font-size:0.8rem;margin-top:0.25rem;">{{ $message }}</p>
        @enderror
      </div>
    </div>

    {{-- 3. Location --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;">
      <div>
        <label for="purok_id" style="display:block;font-size:0.875rem;font-weight:600;color:var(--ink);margin-bottom:0.35rem;">
          Purok <span style="color:var(--red);">*</span>
        </label>
        <select id="purok_id" name="purok_id" required
                style="width:100%;padding:0.6rem 0.75rem;border:1px solid var(--line);border-radius:0.375rem;font-size:0.9rem;background:#fff;">
          <option value="">Select purok</option>
          @foreach($puroks as $purok)
            <option value="{{ $purok->id }}" {{ old('purok_id') == $purok->id ? 'selected' : '' }}>
              {{ $purok->name }}
            </option>
          @endforeach
        </select>
        @error('purok_id')
          <p style="color:var(--red);font-size:0.8rem;margin-top:0.25rem;">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label for="landmark" style="display:block;font-size:0.875rem;font-weight:600;color:var(--ink);margin-bottom:0.35rem;">
          Landmark or Specific Street
        </label>
        <input type="text" id="landmark" name="landmark" value="{{ old('landmark') }}"
               placeholder="Near Calarian Elementary School"
               style="width:100%;padding:0.6rem 0.75rem;border:1px solid var(--line);border-radius:0.375rem;font-size:0.9rem;">
        @error('landmark')
          <p style="color:var(--red);font-size:0.8rem;margin-top:0.25rem;">{{ $message }}</p>
        @enderror
      </div>
    </div>

    {{-- 4. Category & Urgency --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;">
      <div>
        <label for="category_id" style="display:block;font-size:0.875rem;font-weight:600;color:var(--ink);margin-bottom:0.35rem;">
          Category
        </label>
        <select id="category_id" name="category_id"
                style="width:100%;padding:0.6rem 0.75rem;border:1px solid var(--line);border-radius:0.375rem;font-size:0.9rem;background:#fff;">
          <option value="">Select category (optional)</option>
          @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
              {{ $category->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label for="urgency" style="display:block;font-size:0.875rem;font-weight:600;color:var(--ink);margin-bottom:0.35rem;">
          Urgency
        </label>
        <select id="urgency" name="urgency"
                style="width:100%;padding:0.6rem 0.75rem;border:1px solid var(--line);border-radius:0.375rem;font-size:0.9rem;background:#fff;">
          <option value="Low" {{ old('urgency') === 'Low' ? 'selected' : '' }}>Low</option>
          <option value="Medium" {{ old('urgency', 'Medium') === 'Medium' ? 'selected' : '' }}>Medium</option>
          <option value="High" {{ old('urgency') === 'High' ? 'selected' : '' }}>High</option>
          <option value="Critical" {{ old('urgency') === 'Critical' ? 'selected' : '' }}>Critical</option>
        </select>
      </div>
    </div>

    {{-- 5. Audio Recorder --}}
    <div style="margin-bottom:1.25rem;padding:1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.5rem;">
      <label style="display:block;font-size:0.875rem;font-weight:700;color:var(--ink);margin-bottom:0.5rem;">
        <i class="fa-solid fa-headset" style="color:var(--blue);"></i>
        Voice Recording / Audio Memo <span style="font-size:0.8rem;color:#64748b;font-weight:400;">(optional)</span>
      </label>
      <x-voice.recorder target-input="phone-description" origin="staff" />
    </div>

    {{-- 6. Problem Description --}}
    <div style="margin-bottom:1.5rem;">
      <label for="phone-description" style="display:block;font-size:0.875rem;font-weight:700;color:var(--ink);margin-bottom:0.35rem;">
        Incident Description <span style="color:var(--red);">*</span>
      </label>
      <textarea id="phone-description" name="description" rows="4" required minlength="5"
                placeholder="Details of the problem as reported by caller..."
                style="width:100%;padding:0.75rem;border:1px solid var(--line);border-radius:0.375rem;font-size:0.9rem;">{{ old('description') }}</textarea>
      @error('description')
        <p style="color:var(--red);font-size:0.8rem;margin-top:0.25rem;">{{ $message }}</p>
      @enderror
    </div>

    {{-- 7. Emergency Checkbox --}}
    <div style="margin-bottom:1.75rem;padding:0.85rem 1rem;background:#fff1f2;border:1px solid #fecdd3;border-radius:0.5rem;">
      <label style="display:flex;align-items:center;gap:0.75rem;cursor:pointer;">
        <input type="checkbox" name="is_emergency" value="1" {{ old('is_emergency') ? 'checked' : '' }}
               style="accent-color:var(--red);width:1.15rem;height:1.15rem;cursor:pointer;">
        <div>
          <strong style="color:#9f1239;font-size:0.9rem;">Mark as Emergency</strong>
          <span style="display:block;color:#be123c;font-size:0.8rem;">Flag for immediate attention by Captain or Tanod.</span>
        </div>
      </label>
    </div>

    {{-- Submit / Cancel Actions --}}
    <div style="display:flex;justify-content:flex-end;align-items:center;gap:1rem;border-top:1px solid var(--line);padding-top:1.25rem;">
      <a href="{{ route('admin.reports.index') }}" class="btn btn-outline" style="text-decoration:none;">
        Cancel
      </a>
      <button type="submit" class="btn btn-primary" style="padding:0.65rem 1.5rem;font-weight:700;">
        <i class="fa-solid fa-phone"></i> Log Phone Report
      </button>
    </div>
  </form>
</div>
@endsection
