import '@fortawesome/fontawesome-free/css/all.min.css';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import Chart from 'chart.js/auto';

delete L.Icon.Default.prototype._getIconUrl;

L.Icon.Default.mergeOptions({
  iconUrl: markerIcon,
  iconRetinaUrl: markerIcon2x,
  shadowUrl: markerShadow,
});

window.L = L;
window.Chart = Chart;

const dashboardChartInstances = [];
const chartPalette = ['#2456d6', '#ffc21a', '#25855a', '#e0352b', '#7756c8', '#e07b2b', '#35a3a3', '#68748a'];

const initializeDashboardCharts = () => {
  document.querySelectorAll('[data-report-chart]').forEach((container) => {
    const canvas = container.querySelector('canvas');
    const chartData = JSON.parse(container.dataset.reportChart);
    const isDoughnut = container.dataset.chartType === 'doughnut';
    const isHorizontal = container.dataset.chartType === 'horizontal-bar';
    const isLine = container.dataset.chartType === 'line';
    const datasetColors = container.dataset.chartType === 'bar'
      ? ['#25855a', '#ffc21a', '#e07b2b', '#e0352b']
      : chartData.counts.map((_, index) => chartPalette[index % chartPalette.length]);

    dashboardChartInstances.push(new Chart(canvas, {
      type: isHorizontal ? 'bar' : container.dataset.chartType,
      data: {
        labels: chartData.labels,
        datasets: [{
          label: 'Reports',
          data: chartData.counts,
          backgroundColor: isLine ? 'rgb(36 86 214 / 16%)' : datasetColors,
          borderColor: isLine ? '#2456d6' : (isDoughnut ? '#fff' : datasetColors),
          borderWidth: isDoughnut ? 2 : 1,
          tension: 0.3,
          fill: false,
          pointRadius: isLine ? 3 : 0,
          pointBackgroundColor: '#2456d6',
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: isHorizontal ? 'y' : 'x',
        cutout: isDoughnut ? '62%' : undefined,
        plugins: {
          legend: {
            display: isDoughnut,
            position: 'bottom',
            labels: {
              padding: 14,
              generateLabels: (chart) => chart.data.labels.map((label, index) => ({
                text: `${label} (${chart.data.datasets[0].data[index]})`,
                fillStyle: chart.data.datasets[0].backgroundColor[index],
                strokeStyle: chart.data.datasets[0].backgroundColor[index],
                lineWidth: 0,
                hidden: false,
                index,
              })),
            },
          },
          tooltip: {
            callbacks: {
              label: (context) => `${context.label ?? context.dataset.label}: ${context.raw}`,
            },
          },
        },
        scales: isDoughnut ? {} : {
          x: {
            beginAtZero: isHorizontal,
            grid: { display: isHorizontal, color: '#e3e8f4' },
            ticks: { precision: 0, maxRotation: 0 },
          },
          y: {
            beginAtZero: !isHorizontal,
            grid: { display: !isHorizontal, color: '#e3e8f4' },
            ticks: { precision: 0, autoSkip: !isHorizontal, maxTicksLimit: 8 },
          },
        },
      },
    }));
  });

};

if (document.querySelector('[data-report-chart]')) {
  initializeDashboardCharts();

  const moreChartsEl = document.getElementById('dashboard-more-charts-details');
  if (moreChartsEl) {
    moreChartsEl.addEventListener('toggle', () => {
      const textEl = document.getElementById('more-charts-toggle-text');
      if (textEl) {
        textEl.textContent = moreChartsEl.open ? 'Collapse' : 'Expand';
      }
      if (moreChartsEl.open) {
        setTimeout(() => {
          dashboardChartInstances.forEach((chart) => {
            if (chart && typeof chart.resize === 'function') {
              chart.resize();
            }
          });
        }, 60);
      }
    });
  }
}

document.addEventListener('submit', (event) => {
  const form = event.target;

  if (form instanceof HTMLFormElement && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
    event.preventDefault();
  }
});

document.addEventListener('click', (event) => {
  const openButton = event.target.closest('[data-help-open], [data-dialog-open]');

  if (openButton) {
    const dialogId = openButton.dataset.helpOpen ?? openButton.dataset.dialogOpen;
    const dialog = document.getElementById(dialogId);

    if (dialog instanceof HTMLDialogElement) {
      dialog.showModal();
    }

    return;
  }

  const closeButton = event.target.closest('[data-help-close], [data-dialog-close]');
  const dialog = closeButton?.closest('dialog');

  if (dialog instanceof HTMLDialogElement) {
    dialog.close();
  }
});

document.querySelectorAll('dialog.settings-help-dialog, dialog[data-contact-dialog]').forEach((dialog) => {
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      dialog.close();
    }
  });
});

document.querySelectorAll('dialog[data-open-on-load]').forEach((dialog) => {
  if (dialog instanceof HTMLDialogElement) {
    dialog.showModal();
  }
});

document.querySelectorAll('[data-review-form]').forEach((form) => {
  const tabs = Array.from(form.querySelectorAll('[data-review-tab]'));
  const panels = Array.from(form.querySelectorAll('[data-review-panel]'));

  const selectDescriptionTab = (name) => {
    tabs.forEach((tab) => {
      const selected = tab.dataset.reviewTab === name;
      tab.setAttribute('aria-selected', String(selected));
      tab.tabIndex = selected ? 0 : -1;
    });

    panels.forEach((panel) => {
      panel.hidden = panel.dataset.reviewPanel !== name;
    });
  };

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => selectDescriptionTab(tab.dataset.reviewTab));
  });

  form.querySelectorAll('[data-copy-description]').forEach((button) => {
    button.addEventListener('click', () => {
      const panel = form.querySelector(`[data-review-panel="${button.dataset.copyDescription}"]`);
      const source = panel?.querySelector('[data-review-source]');
      const description = form.querySelector('[name="staff_description"]');

      if (source && description instanceof HTMLTextAreaElement) {
        description.value = source.textContent.trim();
        description.dispatchEvent(new Event('input', { bubbles: true }));
      }
    });
  });

  const clearPrefilledBadge = (field) => {
    form.querySelector(`[data-prefilled-for="${field}"]`)?.remove();
  };

  ['category_id', 'urgency', 'staff_description'].forEach((field) => {
    form.querySelector(`[name="${field}"]`)?.addEventListener(
      field === 'staff_description' ? 'input' : 'change',
      () => clearPrefilledBadge(field),
    );
  });

  ['photo_mismatch', 'is_emergency'].forEach((field) => {
    form.querySelector(`input[type="checkbox"][name="${field}"]`)?.addEventListener(
      'change',
      () => clearPrefilledBadge(field),
    );
  });
});

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

export function setupCooperativeMapGestures(map, container) {
  if (!container || !map) return;

  if (map.scrollWheelZoom) {
    map.scrollWheelZoom.disable();
  }

  let hint = container.querySelector('.map-cooperative-hint');
  if (!hint) {
    hint = document.createElement('div');
    hint.className = 'map-cooperative-hint';
    hint.setAttribute('aria-hidden', 'true');
    hint.innerHTML = '<span><i class="fa-solid fa-hand-pointer"></i> Use two fingers to move the map</span>';
    container.style.position = 'relative';
    container.appendChild(hint);
  }

  let hintTimer = null;

  const showHint = () => {
    hint.classList.add('is-visible');
    clearTimeout(hintTimer);
    hintTimer = setTimeout(() => {
      hint.classList.remove('is-visible');
    }, 1200);
  };

  container.addEventListener('touchstart', (e) => {
    const isMarker = Boolean(e.target.closest('.leaflet-marker-icon'));
    const isControl = Boolean(e.target.closest('.leaflet-control'));

    if (e.touches.length === 1 && !isMarker && !isControl) {
      map.dragging.disable();
    } else if (e.touches.length >= 2) {
      map.dragging.enable();
    }
  }, { passive: true });

  container.addEventListener('touchmove', (e) => {
    const isMarker = Boolean(e.target.closest('.leaflet-marker-icon'));
    const isControl = Boolean(e.target.closest('.leaflet-control'));

    if (e.touches.length === 1 && !isMarker && !isControl) {
      showHint();
    }
  }, { passive: true });

  container.addEventListener('touchend', () => {
    map.dragging.enable();
  }, { passive: true });

  container.addEventListener('touchcancel', () => {
    map.dragging.enable();
  }, { passive: true });
}

window.setupCooperativeMapGestures = setupCooperativeMapGestures;

const reportMapElement = document.getElementById('map');
const detailMapElement = document.getElementById('report-location-map');

if (reportMapElement) {
  const defaultCenter = [6.9214, 122.0790];
  const latInput = document.getElementById('lat');
  const lngInput = document.getElementById('lng');
  const map = L.map(reportMapElement, { scrollWheelZoom: false }).setView(defaultCenter, 15);
  setupCooperativeMapGestures(map, reportMapElement);

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
    setupCooperativeMapGestures(map, detailMapElement);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    L.marker([latitude, longitude]).addTo(map);
  }
}

const staffMapElement = document.querySelector('[data-staff-report-map]');

if (staffMapElement) {
  const reports = JSON.parse(staffMapElement.dataset.staffReportMap);
  const defaultCenter = [6.918, 122.081];
  const map = L.map(staffMapElement, {
    scrollWheelZoom: false,
  }).setView(defaultCenter, 13);
  setupCooperativeMapGestures(map, staffMapElement);
  const markerLayer = L.layerGroup().addTo(map);
  const filterControls = {
    category: document.querySelector('[data-map-filter="category"]'),
    purok: document.querySelector('[data-map-filter="purok"]'),
    status: document.querySelector('[data-map-filter="status"]'),
    dateFrom: document.querySelector('[data-map-filter="date-from"]'),
    dateTo: document.querySelector('[data-map-filter="date-to"]'),
  };
  const resultsCount = document.querySelector('[data-map-results]');
  const clearHotspotButton = document.querySelector('[data-map-clear-hotspot]');
  const hotspotButtons = Array.from(document.querySelectorAll('[data-hotspot-cell]'));
  let selectedHotspotCell = null;
  let hotspotOverlay = null;

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map);

  const escapePopupText = (value) => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);

  const markerColor = (urgency) => ({
    low: '#25855a',
    medium: '#c58a00',
    high: '#e07b2b',
    critical: '#e0352b',
  })[String(urgency).toLowerCase()] ?? '#2456d6';

  const filteredReports = () => reports.filter((report) => {
    const categoryMatches = !filterControls.category.value
      || filterControls.category.value === report.categoryId;
    const purokMatches = !filterControls.purok.value
      || filterControls.purok.value === report.purokId;
    const statusMatches = !filterControls.status.value
      || filterControls.status.value === report.status;
    const fromDateMatches = !filterControls.dateFrom.value
      || report.createdAt >= filterControls.dateFrom.value;
    const toDateMatches = !filterControls.dateTo.value
      || report.createdAt <= filterControls.dateTo.value;
    const hotspotMatches = !selectedHotspotCell || report.cellKey === selectedHotspotCell;

    return categoryMatches
      && purokMatches
      && statusMatches
      && fromDateMatches
      && toDateMatches
      && hotspotMatches;
  });

  const renderMapReports = () => {
    const visibleReports = filteredReports();
    const markerLocations = [];
    markerLayer.clearLayers();

    visibleReports.forEach((report) => {
      const location = [report.lat, report.lng];
      const color = markerColor(report.urgency);
      const marker = L.circleMarker(location, {
        radius: 8,
        color,
        weight: 2,
        fillColor: color,
        fillOpacity: 0.85,
      });
      const popup = [
        `<strong>${escapePopupText(report.trackingCode)}</strong>`,
        escapePopupText(report.category),
        `Status: ${escapePopupText(report.statusLabel)}`,
        `Urgency: ${escapePopupText(report.urgency)}`,
        `Purok: ${escapePopupText(report.purok)}`,
        `<a href="${escapePopupText(report.url)}">Open report</a>`,
      ].join('<br>');

      marker.bindPopup(popup);
      markerLayer.addLayer(marker);
      markerLocations.push(location);
    });

    resultsCount.textContent = `${visibleReports.length} location${visibleReports.length === 1 ? '' : 's'} shown`;

    if (selectedHotspotCell) {
      const [latitude, longitude] = selectedHotspotCell.split(',').map(Number);
      map.fitBounds([
        [latitude - 0.0005, longitude - 0.0005],
        [latitude + 0.0005, longitude + 0.0005],
      ], { padding: [24, 24], maxZoom: 16 });
    } else if (markerLocations.length > 0) {
      map.fitBounds(L.latLngBounds(markerLocations), { padding: [24, 24], maxZoom: 15 });
    } else {
      map.setView(defaultCenter, 13);
    }
  };

  const clearHotspotSelection = () => {
    selectedHotspotCell = null;
    hotspotOverlay?.remove();
    hotspotOverlay = null;
    hotspotButtons.forEach((button) => button.setAttribute('aria-pressed', 'false'));
    clearHotspotButton.disabled = true;
  };

  const selectHotspot = (cellKey, selectedButton) => {
    selectedHotspotCell = cellKey;
    hotspotOverlay?.remove();
    const [latitude, longitude] = cellKey.split(',').map(Number);
    hotspotOverlay = L.rectangle([
      [latitude - 0.0005, longitude - 0.0005],
      [latitude + 0.0005, longitude + 0.0005],
    ], {
      color: '#2456d6',
      weight: 3,
      fillColor: '#ffc21a',
      fillOpacity: 0.3,
      interactive: false,
    }).addTo(map);
    hotspotOverlay.bringToBack();
    hotspotButtons.forEach((button) => {
      button.setAttribute('aria-pressed', String(button === selectedButton));
    });
    clearHotspotButton.disabled = false;
    renderMapReports();
  };

  Object.values(filterControls).forEach((control) => {
    control.addEventListener('change', renderMapReports);
  });

  hotspotButtons.forEach((button) => {
    button.addEventListener('click', () => selectHotspot(button.dataset.hotspotCell, button));
  });

  clearHotspotButton.addEventListener('click', () => {
    clearHotspotSelection();
    renderMapReports();
  });

  document.querySelector('[data-map-show-all]').addEventListener('click', () => {
    Object.values(filterControls).forEach((control) => {
      control.value = '';
    });
    clearHotspotSelection();
    renderMapReports();
  });

  renderMapReports();
}
