import '@fortawesome/fontawesome-free/css/all.min.css';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;

L.Icon.Default.mergeOptions({
  iconUrl: markerIcon,
  iconRetinaUrl: markerIcon2x,
  shadowUrl: markerShadow,
});

window.L = L;

const reportMapElement = document.getElementById('map');
const detailMapElement = document.getElementById('report-location-map');

if (reportMapElement) {
  const defaultCenter = [6.9214, 122.0790];
  const latInput = document.getElementById('lat');
  const lngInput = document.getElementById('lng');
  const map = L.map(reportMapElement).setView(defaultCenter, 15);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map);

  let marker = null;
  const setPin = (lat, lng) => {
    if (marker) {
      marker.setLatLng([lat, lng]);
    } else {
      marker = L.marker([lat, lng], { draggable: true }).addTo(map);
      marker.on('dragend', (event) => {
        const position = event.target.getLatLng();
        setPin(position.lat, position.lng);
      });
    }

    latInput.value = Number(lat).toFixed(7);
    lngInput.value = Number(lng).toFixed(7);
  };

  map.on('click', (event) => setPin(event.latlng.lat, event.latlng.lng));

  const locateButton = document.getElementById('btn-locate');
  locateButton?.addEventListener('click', () => {
    if (!navigator.geolocation) {
      window.alert('Location is not supported by this browser.');
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const { latitude, longitude } = position.coords;
        setPin(latitude, longitude);
        map.setView([latitude, longitude], 17);
      },
      () => window.alert('Could not get your location. Tap the map or choose a purok instead.'),
    );
  });

  if (latInput.value && lngInput.value) {
    const latitude = Number(latInput.value);
    const longitude = Number(lngInput.value);

    if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
      setPin(latitude, longitude);
      map.setView([latitude, longitude], 17);
    }
  }
}

if (detailMapElement) {
  const latitude = Number(detailMapElement.dataset.lat);
  const longitude = Number(detailMapElement.dataset.lng);

  if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
    const map = L.map(detailMapElement, {
      scrollWheelZoom: false,
    }).setView([latitude, longitude], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    L.marker([latitude, longitude]).addTo(map);
  }
}
