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

const photosInput = document.getElementById('photos');
const addPhotosButton = document.getElementById('add-photos');
const photoEmptyState = document.getElementById('photo-empty-state');
const photoPreviews = document.getElementById('photo-previews');
const photoUploadError = document.getElementById('photo-upload-error');
const photoTotalSize = document.getElementById('photo-total-size');

if (photosInput && addPhotosButton && photoEmptyState && photoPreviews && photoUploadError && photoTotalSize) {
  const maximumTotalSize = 5 * 1024 * 1024;
  let selectedPhotos = [];
  let previewUrls = [];

  addPhotosButton.addEventListener('click', () => photosInput.click());

  const updateInputFiles = () => {
    const transfer = new DataTransfer();
    selectedPhotos.forEach((photo) => transfer.items.add(photo));
    photosInput.files = transfer.files;
  };

  const renderPhotoPreviews = () => {
    previewUrls.forEach((url) => URL.revokeObjectURL(url));
    previewUrls = [];
    photoPreviews.replaceChildren();
    photoEmptyState.hidden = selectedPhotos.length > 0;

    selectedPhotos.forEach((photo, index) => {
      const previewUrl = URL.createObjectURL(photo);
      previewUrls.push(previewUrl);

      const preview = document.createElement('figure');
      preview.className = 'photo-preview-item';

      const image = document.createElement('img');
      image.src = previewUrl;
      image.alt = `Preview of ${photo.name}`;

      const caption = document.createElement('figcaption');
      caption.textContent = photo.name;

      const removeButton = document.createElement('button');
      removeButton.type = 'button';
      removeButton.className = 'photo-preview-remove';
      removeButton.setAttribute('aria-label', `Remove ${photo.name}`);
      removeButton.title = `Remove ${photo.name}`;
      removeButton.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';
      removeButton.addEventListener('click', () => {
        selectedPhotos = selectedPhotos.filter((_, photoIndex) => photoIndex !== index);
        updateInputFiles();
        renderPhotoPreviews();
      });

      preview.append(image, caption, removeButton);
      photoPreviews.append(preview);
    });

    const totalSize = selectedPhotos.reduce((total, photo) => total + photo.size, 0);
    photoTotalSize.textContent = selectedPhotos.length
      ? `Combined size: ${(totalSize / (1024 * 1024)).toFixed(2)} MB / 5 MB`
      : '';
  };

  photosInput.addEventListener('change', () => {
    const addedPhotos = Array.from(photosInput.files ?? []);
    const combinedSize = selectedPhotos.reduce((total, photo) => total + photo.size, 0)
      + addedPhotos.reduce((total, photo) => total + photo.size, 0);

    if (combinedSize > maximumTotalSize) {
      photoUploadError.textContent = 'The combined size of all photos must not exceed 5 MB.';
      photoUploadError.hidden = false;
      updateInputFiles();
      return;
    }

    photoUploadError.textContent = '';
    photoUploadError.hidden = true;
    selectedPhotos = [...selectedPhotos, ...addedPhotos];
    updateInputFiles();
    renderPhotoPreviews();
  });
}

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
