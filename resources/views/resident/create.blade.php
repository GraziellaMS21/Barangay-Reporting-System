@extends('layouts.app')
@section('title', 'Report a Problem')

@section('hero')
  <section class="hero">
    <div class="hero-inner">
      <div>
        <h1>Report a problem in Barangay Calarian</h1>
        <p>Send a report with a photo and location. Barangay staff will review it, route it to the proper office, and keep you updated with a tracking code.</p>
        <em class="hero-motto">Pusong May Malasakit, Serbisyong Tapat sa Mamamayan</em>
        <div class="hero-actions">
          <a class="btn btn-gold" href="#report-form">
            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Report a problem
          </a>
          <a class="btn btn-ghost" href="{{ route('track.index') }}">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Track a report
          </a>
        </div>
      </div>
      <div class="hero-seal" aria-hidden="true"><img src="{{ asset('storage/calarian-seal.png') }}" alt=""></div>
    </div>
  </section>
@endsection

@section('content')
<section class="card alert" role="alert">
  <span class="alert-icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
  <div>
    <h2>Emergency or crime? Call 911 or the local police first.</h2>
    <p>This system is not monitored in real time.</p>
    @if($contacts->isNotEmpty())
      <div class="contact-chips">
        @foreach($contacts as $contact)
          <span><b>{{ $contact->name }}:</b> {{ $contact->number }}</span>
        @endforeach
      </div>
    @endif
  </div>
</section>

<section id="report-form" class="card form-card">
  <h2><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i> Submit a report</h2>

  @if($errors->any())
    <div role="alert" class="form-errors">
      <ul>
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="field">
      <label for="description"><i class="fa-solid fa-align-left" aria-hidden="true"></i> What is the problem?</label>
      <textarea id="description" name="description" rows="4" required
        placeholder="Example: Barado ang drainage sa harap ng tindahan, bumabaha na kapag umuulan.">{{ old('description') }}</textarea>
      <p class="field-hint">You may write in English, Filipino, Bisaya, or Chavacano.</p>
    </div>

    <div class="field">
      <span class="field-label"><i class="fa-solid fa-camera" aria-hidden="true"></i> Photo (optional)</span>
      <div class="photo-preview-area">
        <p id="photo-empty-state" class="photo-empty-state">Your selected photo previews will appear here.</p>
        <div id="photo-previews" class="photo-preview-grid" aria-live="polite"></div>
      </div>
      <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png"
        aria-label="Photos (optional)" multiple hidden>
      <button type="button" id="add-photos" class="btn btn-outline btn-small photo-add-button">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> Tap to add photos
      </button>
      <p class="field-hint">JPG or PNG, combined up to 5 MB.</p>
      <p id="photo-upload-error" class="field-hint" role="alert" hidden></p>
      <p id="photo-total-size" class="field-hint" aria-live="polite"></p>
      @error('photos')
        <p class="field-hint" role="alert">{{ $message }}</p>
      @enderror
      @error('photos.*')
        <p class="field-hint" role="alert">{{ $message }}</p>
      @enderror
    </div>

    <div class="field">
      <label for="map"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Location</label>
      <div id="map" class="map-frame" aria-label="Choose the report location on the map"></div>
      <div class="map-controls">
        <button type="button" id="btn-locate" class="btn btn-outline btn-small">
          <i class="fa-solid fa-crosshairs" aria-hidden="true"></i> Use my location
        </button>
      </div>
      <p class="field-hint">Tap the map or drag the pin to adjust, or choose a purok or landmark below.</p>
      <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
      <input type="hidden" name="lng" id="lng" value="{{ old('lng') }}">
    </div>

    <div class="form-grid">
      <div class="field">
        <label for="purok_id"><i class="fa-solid fa-map" aria-hidden="true"></i> Purok (optional)</label>
        <select id="purok_id" name="purok_id">
          <option value="">Select purok</option>
          @foreach($puroks as $purok)
            <option value="{{ $purok->id }}" @selected(old('purok_id') == $purok->id)>{{ $purok->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label for="landmark"><i class="fa-solid fa-landmark" aria-hidden="true"></i> Landmark (optional)</label>
        <input type="text" id="landmark" name="landmark" value="{{ old('landmark') }}"
          placeholder="Near the chapel, beside the school">
      </div>
    </div>

    <div class="form-grid">
      <div class="field">
        <label for="reporter_name"><i class="fa-solid fa-user" aria-hidden="true"></i> Name (optional)</label>
        <input type="text" id="reporter_name" name="reporter_name" value="{{ old('reporter_name') }}"
          autocomplete="name" maxlength="100">
      </div>
      <div class="field">
        <label for="reporter_contact">
          <i class="fa-solid fa-phone" aria-hidden="true"></i> Contact number
          @if(!config('reports.require_contact')) (optional) @endif
        </label>
        <input type="tel" id="reporter_contact" name="reporter_contact" value="{{ old('reporter_contact') }}"
          autocomplete="tel" inputmode="tel" maxlength="20" @if(config('reports.require_contact')) required @endif>
        <p class="field-hint">
          @if(config('reports.require_contact'))
            Lets barangay staff reach you if they need more details.
          @else
            Optional. Lets barangay staff reach you if they need more details.
          @endif
        </p>
      </div>
    </div>

    <div class="field">
      <label for="reporter_email"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Email for updates (optional)</label>
      <input type="email" id="reporter_email" name="reporter_email" value="{{ old('reporter_email') }}"
        autocomplete="email" placeholder="name@example.com">
      <p class="field-hint">Only barangay staff can see your contact details. They are never shown publicly.</p>
    </div>

    <button type="submit" class="btn btn-gold">
      <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit report
    </button>
  </form>
</section>
@endsection
