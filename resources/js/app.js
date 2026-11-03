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

const dashboardTabs = document.querySelector('[data-dashboard-tabs]');

if (dashboardTabs) {
  const tabButtons = Array.from(dashboardTabs.querySelectorAll('[data-dashboard-tab]'));
  const setActiveDashboardTab = (tabName, updateHash = false) => {
    const activeButton = tabButtons.find((button) => button.dataset.dashboardTab === tabName);

    if (!activeButton) {
      return;
    }

    tabButtons.forEach((button) => {
      const isActive = button === activeButton;
      button.setAttribute('aria-selected', String(isActive));
      button.tabIndex = isActive ? 0 : -1;

      const panel = document.getElementById(button.getAttribute('aria-controls'));
      if (panel) {
        panel.hidden = !isActive;
      }
    });

    if (updateHash && window.location.hash !== `#${tabName}`) {
      window.history.pushState(null, '', `#${tabName}`);
    }
  };

  dashboardTabs.addEventListener('click', (event) => {
    const tabButton = event.target.closest('[data-dashboard-tab]');

    if (tabButton) {
      setActiveDashboardTab(tabButton.dataset.dashboardTab, true);
    }
  });

  dashboardTabs.addEventListener('keydown', (event) => {
    const currentIndex = tabButtons.indexOf(document.activeElement);

    if (currentIndex === -1) {
      return;
    }

    let nextIndex = currentIndex;
    if (event.key === 'ArrowRight') {
      nextIndex = (currentIndex + 1) % tabButtons.length;
    } else if (event.key === 'ArrowLeft') {
      nextIndex = (currentIndex - 1 + tabButtons.length) % tabButtons.length;
    } else if (event.key === 'Home') {
      nextIndex = 0;
    } else if (event.key === 'End') {
      nextIndex = tabButtons.length - 1;
    } else {
      return;
    }

    event.preventDefault();
    tabButtons[nextIndex].focus();
    setActiveDashboardTab(tabButtons[nextIndex].dataset.dashboardTab, true);
  });

  window.addEventListener('hashchange', () => {
    setActiveDashboardTab(window.location.hash.slice(1));
  });

  const initialTab = window.location.hash.slice(1);
  const validInitialTab = tabButtons.some((button) => button.dataset.dashboardTab === initialTab);
  setActiveDashboardTab(validInitialTab ? initialTab : 'overview');

  if (!validInitialTab && window.location.hash !== '#overview') {
    window.history.replaceState(null, '', '#overview');
  }
}

const siteMenuToggle = document.querySelector('.site-menu-toggle');
const siteNavigation = document.getElementById('site-navigation');

if (siteMenuToggle && siteNavigation) {
  const closeSiteNavigation = (restoreFocus = false) => {
    siteNavigation.classList.remove('is-open');
    siteMenuToggle.setAttribute('aria-expanded', 'false');
    siteMenuToggle.setAttribute('aria-label', 'Open main navigation');

    if (restoreFocus) {
      siteMenuToggle.focus();
    }
  };

  siteMenuToggle.addEventListener('click', () => {
    const isOpen = siteMenuToggle.getAttribute('aria-expanded') === 'true';
    siteMenuToggle.setAttribute('aria-expanded', String(!isOpen));
    siteMenuToggle.setAttribute('aria-label', isOpen ? 'Open main navigation' : 'Close main navigation');
    siteNavigation.classList.toggle('is-open', !isOpen);

    if (!isOpen) {
      siteNavigation.querySelector('a, button')?.focus();
    }
  });

  siteNavigation.addEventListener('click', (event) => {
    if (event.target.closest('a, button')) {
      closeSiteNavigation();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && siteMenuToggle.getAttribute('aria-expanded') === 'true') {
      closeSiteNavigation(true);
    }
  });
}

const staffMenuToggle = document.querySelector('.staff-menu-toggle');
const staffSidebar = document.getElementById('staff-sidebar');
const staffDrawerOverlay = document.querySelector('.staff-drawer-overlay');
const staffMain = document.querySelector('.staff-main');

if (staffMenuToggle && staffSidebar && staffDrawerOverlay && staffMain) {
  const mobileViewport = window.matchMedia('(max-width: 767px)');
  let isStaffMenuOpen = false;

  const focusableDrawerElements = () => Array.from(
    staffSidebar.querySelectorAll('a[href], button:not([disabled])'),
  );

  const setStaffMenuOpen = (isOpen, restoreFocus = false) => {
    isStaffMenuOpen = isOpen && mobileViewport.matches;
    staffSidebar.classList.toggle('is-open', isStaffMenuOpen);
    staffDrawerOverlay.classList.toggle('is-open', isStaffMenuOpen);
    staffMenuToggle.setAttribute('aria-expanded', String(isStaffMenuOpen));
    staffMenuToggle.setAttribute('aria-label', isStaffMenuOpen ? 'Close staff navigation' : 'Open staff navigation');
    staffSidebar.inert = mobileViewport.matches && !isStaffMenuOpen;
    staffMain.inert = isStaffMenuOpen;
    document.body.classList.toggle('staff-menu-open', isStaffMenuOpen);

    if (isStaffMenuOpen) {
      focusableDrawerElements()[0]?.focus();
    } else if (restoreFocus) {
      staffMenuToggle.focus();
    }
  };

  const syncStaffMenuViewport = () => {
    isStaffMenuOpen = false;
    staffSidebar.classList.remove('is-open');
    staffDrawerOverlay.classList.remove('is-open');
    staffMenuToggle.setAttribute('aria-expanded', 'false');
    staffMenuToggle.setAttribute('aria-label', 'Open staff navigation');
    staffSidebar.inert = mobileViewport.matches;
    staffMain.inert = false;
    document.body.classList.remove('staff-menu-open');
  };

  staffMenuToggle.addEventListener('click', () => setStaffMenuOpen(!isStaffMenuOpen));
  staffDrawerOverlay.addEventListener('click', () => setStaffMenuOpen(false, true));

  staffSidebar.addEventListener('click', (event) => {
    if (mobileViewport.matches && event.target.closest('a')) {
      setStaffMenuOpen(false);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (!isStaffMenuOpen) {
      return;
    }

    if (event.key === 'Escape') {
      event.preventDefault();
      setStaffMenuOpen(false, true);
      return;
    }

    if (event.key === 'Tab') {
      const focusableElements = focusableDrawerElements();
      const firstElement = focusableElements[0];
      const lastElement = focusableElements.at(-1);

      if (event.shiftKey && document.activeElement === firstElement) {
        event.preventDefault();
        lastElement?.focus();
      } else if (!event.shiftKey && document.activeElement === lastElement) {
        event.preventDefault();
        firstElement?.focus();
      }
    }
  });

  mobileViewport.addEventListener('change', syncStaffMenuViewport);
  syncStaffMenuViewport();
}

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
