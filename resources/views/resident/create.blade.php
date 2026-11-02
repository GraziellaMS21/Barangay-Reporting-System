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
      <label class="file-drop">
        <span>
          <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
          <b>Tap to add a photo</b><br>
          <small>JPG or PNG, up to 5 MB</small>
        </span>
        <input type="file" id="photo" name="photo" accept="image/*" aria-label="Photo (optional)">
      </label>
      @error('photo')
        <p class="field-hint">{{ $message }}</p>
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

    <div class="field">
      <label for="reporter_email"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Email for updates (optional)</label>
      <input type="email" id="reporter_email" name="reporter_email" value="{{ old('reporter_email') }}"
        autocomplete="email" placeholder="name@example.com">
      <p class="field-hint"><i class="fa-solid fa-lock" aria-hidden="true"></i> Your contact details are never shown on the public page.</p>
    </div>

    <button type="submit" class="btn btn-gold">
      <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit report
    </button>
  </form>
</section>
@endsection
