@extends('layouts.app')
@section('title', 'Report a Problem')

@push('head')
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
@endpush

@section('content')
<div class="bg-red-50 border border-red-300 rounded p-4 mb-6">
  <p class="font-semibold text-red-700">Emergency or crime? Call 911 or the local police first.</p>
  <p class="text-sm text-red-700">This system is not monitored in real time.</p>
  <ul class="text-sm mt-2 space-y-1">
    @foreach($contacts as $c)
      <li>{{ $c->name }}: <span class="font-mono">{{ $c->number }}</span></li>
    @endforeach
  </ul>
</div>

<h1 class="text-2xl font-bold mb-4">Report a Problem</h1>

@if($errors->any())
  <div class="bg-red-100 border border-red-300 text-red-800 rounded p-3 mb-4 text-sm">
    <ul class="list-disc list-inside">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
@endif

<form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data"
      class="space-y-5 bg-white p-5 rounded shadow">
  @csrf

  <div>
    <label for="description" class="block font-medium mb-1">Describe the problem</label>
    <textarea id="description" name="description" rows="4" required
      class="w-full border rounded p-2"
      placeholder="Example: Barado ang drainage sa harap ng tindahan, bumabaha na kapag umuulan.">{{ old('description') }}</textarea>
  </div>

  <div>
    <label for="photo" class="block font-medium mb-1">Photo (optional)</label>
    <input type="file" id="photo" name="photo" accept="image/*" class="w-full text-sm">
  </div>

  <div>
    <label class="block font-medium mb-1">Location</label>
    <button type="button" id="btn-locate"
      class="bg-gray-700 text-white text-sm px-3 py-1 rounded mb-2">Use my current location</button>
    <div id="map" class="h-72 rounded border"></div>
    <p class="text-xs text-gray-500 mt-1">Tap the map or drag the pin to adjust the location.</p>
    <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
    <input type="hidden" name="lng" id="lng" value="{{ old('lng') }}">
  </div>

  <div class="grid sm:grid-cols-2 gap-4">
    <div>
      <label for="purok_id" class="block font-medium mb-1">Purok (optional)</label>
      <select id="purok_id" name="purok_id" class="w-full border rounded p-2">
        <option value="">Select purok</option>
        @foreach($puroks as $p)
          <option value="{{ $p->id }}" @selected(old('purok_id') == $p->id)>{{ $p->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label for="landmark" class="block font-medium mb-1">Landmark (optional)</label>
      <input type="text" id="landmark" name="landmark" value="{{ old('landmark') }}"
        class="w-full border rounded p-2" placeholder="Near the chapel, beside the school...">
    </div>
  </div>

  <div>
    <label for="reporter_email" class="block font-medium mb-1">Email for updates (optional)</label>
    <input type="email" id="reporter_email" name="reporter_email" value="{{ old('reporter_email') }}"
      class="w-full border rounded p-2">
  </div>

  <button type="submit" class="bg-green-700 hover:bg-green-800 text-white px-5 py-2 rounded">
    Submit Report
  </button>
</form>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script>
  // TODO: change to Barangay Calarian's coordinates (Google Maps > right-click > copy coordinates)
  const defaultCenter = [6.9214, 122.0790];

  const latInput = document.getElementById('lat');
  const lngInput = document.getElementById('lng');
  const map = L.map('map').setView(defaultCenter, 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  let marker = null;
  function setPin(lat, lng) {
    if (marker) {
      marker.setLatLng([lat, lng]);
    } else {
      marker = L.marker([lat, lng], { draggable: true }).addTo(map);
      marker.on('dragend', e => {
        const p = e.target.getLatLng();
        setPin(p.lat, p.lng);
      });
    }
    latInput.value = Number(lat).toFixed(7);
    lngInput.value = Number(lng).toFixed(7);
  }

  map.on('click', e => setPin(e.latlng.lat, e.latlng.lng));

  document.getElementById('btn-locate').addEventListener('click', () => {
    if (!navigator.geolocation) {
      alert('Location is not supported by this browser.');
      return;
    }
    navigator.geolocation.getCurrentPosition(
      pos => {
        setPin(pos.coords.latitude, pos.coords.longitude);
        map.setView([pos.coords.latitude, pos.coords.longitude], 17);
      },
      () => alert('Could not get your location. Tap the map or choose a purok instead.')
    );
  });

  @if(old('lat') && old('lng'))
    setPin({{ old('lat') }}, {{ old('lng') }});
  @endif
</script>
@endpush