import './bootstrap';
import './report-form-map';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

window.L = L;

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
	iconRetinaUrl: markerIcon2x,
	iconUrl: markerIcon,
	shadowUrl: markerShadow,
});

window.nidviteTracker = function nidviteTracker(errorMsg) {
	return {
		showInput: false,
		trackingId: '',
		error: '',
		errorMsg: errorMsg || 'Report not found',
		modalOpen: false,
		loading: false,
		report: null,
		lookup() {
			this.error = '';
			var id = this.trackingId.trim();
			if (!id) return;
			this.showInput = false;
			this.modalOpen = true;
			this.loading = true;
			this.report = null;
			fetch('/api/reports/' + encodeURIComponent(id) + '/lookup')
				.then(function (r) {
					if (!r.ok) throw new Error('not_found');
					return r.json();
				})
				.then(function (data) {
					this.report = data;
					this.loading = false;
				}.bind(this))
				.catch(function () {
					this.loading = false;
					this.modalOpen = false;
					this.error = this.errorMsg;
					this.showInput = true;
				}.bind(this));
		},
	};
};

if ('serviceWorker' in navigator) {
	window.addEventListener('load', function () {
		navigator.serviceWorker.register('/serviceworker.js', { scope: '.' }).catch(function () {
			// Keep failure silent in production to avoid noisy console logs.
		});
	});
}

function initTrackingMap() {
	var mapEl = document.getElementById('tracking-map');
	if (!mapEl) return;

	var lat = parseFloat(mapEl.dataset.lat || '');
	var lng = parseFloat(mapEl.dataset.lng || '');
	if (Number.isNaN(lat) || Number.isNaN(lng)) return;

	var map = L.map(mapEl).setView([lat, lng], 15);
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; OpenStreetMap contributors',
		maxZoom: 19,
	}).addTo(map);
	L.marker([lat, lng]).addTo(map);
}

function initPublicMapPage() {
	var mapEl = document.getElementById('map');
	if (!mapEl) return;

	var geojsonUrl = mapEl.dataset.geojsonUrl;
	var noAddressLabel = mapEl.dataset.noAddress || '';
	var viewDetailsLabel = mapEl.dataset.viewDetails || 'View details';

	var map = L.map(mapEl, { zoomControl: false }).setView([45.5017, -73.5673], 12);
	L.control.zoom({ position: 'topright' }).addTo(map);

	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
		maxZoom: 19,
	}).addTo(map);

	var statusColors = {
		received: '#d97706',
		verified: '#3b82f6',
		scheduled: '#6366f1',
		in_progress: '#db2777',
		repaired: '#10b981',
		rejected: '#ef4444',
	};

	var normalizeLocationPiece = function (value) {
		if (value === null || value === undefined) {
			return '';
		}

		var text = String(value).trim();
		if (!text) {
			return '';
		}

		var lowered = text.toLowerCase();
		if (lowered === 'montreal' || lowered === 'n/a') {
			return '';
		}

		return text;
	};

	fetch(geojsonUrl)
		.then(function (response) {
			return response.json();
		})
		.then(function (data) {
			var bounds = L.latLngBounds();
			var coordinateUsage = {};

			data.features.forEach(function (feature) {
				var coords = feature.geometry.coordinates;
				var props = feature.properties;
				var color = statusColors[props.status] || '#6b7280';
				var lng = coords[0];
				var lat = coords[1];
				var key = lat.toFixed(6) + ',' + lng.toFixed(6);
				var index = coordinateUsage[key] || 0;
				coordinateUsage[key] = index + 1;

				if (index > 0) {
					// Spread stacked reports in a tiny ring so each marker is clickable.
					var angle = index * 0.9;
					var distance = 0.00008 * Math.ceil(index / 6);
					lat += Math.cos(angle) * distance;
					lng += Math.sin(angle) * distance;
				}

				var marker = L.circleMarker([lat, lng], {
					radius: 8,
					fillColor: color,
					color: '#fff',
					weight: 2,
					opacity: 1,
					fillOpacity: 0.85,
				}).addTo(map);

				var popupEl = document.createElement('div');
				popupEl.className = 'report-popup';

				var statusSpan = document.createElement('span');
				statusSpan.className = 'status status-' + props.status;
				statusSpan.textContent = props.status_label;
				popupEl.appendChild(statusSpan);

				var titleEl = document.createElement('h3');
				titleEl.textContent = props.address || noAddressLabel;
				popupEl.appendChild(titleEl);

				var hoodEl = document.createElement('p');
				var neighborhood = normalizeLocationPiece(props.neighborhood);
				var borough = normalizeLocationPiece(props.borough);
				var locationParts = [];

				if (neighborhood) {
					locationParts.push(neighborhood);
				}

				if (borough && borough.toLowerCase() !== neighborhood.toLowerCase()) {
					locationParts.push(borough);
				}

				if (locationParts.length > 0) {
					hoodEl.textContent = locationParts.join(', ');
					popupEl.appendChild(hoodEl);
				}

				var descEl = document.createElement('p');
				descEl.textContent = props.description
					? props.description.substring(0, 100) + (props.description.length > 100 ? '...' : '')
					: '';
				popupEl.appendChild(descEl);

				var linkEl = document.createElement('a');
				linkEl.href = props.url;
				linkEl.target = '_blank';
				linkEl.textContent = viewDetailsLabel;
				popupEl.appendChild(linkEl);

				marker.bindPopup(popupEl);
				bounds.extend([lat, lng]);
			});

			if (data.features.length > 0) {
				map.fitBounds(bounds, { padding: [50, 50] });
			}
		})
		.catch(function () {
			// Keep failure silent in production to avoid noisy console logs.
		});
}

function bootNidvitePublicPages() {
	initTrackingMap();
	initPublicMapPage();
}

function getAlpineDataContext(element) {
	var root = element.closest('[x-data]');
	if (!root) return null;

	if (root.__x && root.__x.$data) {
		return root.__x.$data;
	}

	if (window.Alpine && typeof window.Alpine.$data === 'function') {
		return window.Alpine.$data(root);
	}

	return null;
}

function initReportFormBindings() {
	if (window.__nidviteReportFormBindingsInitialized) {
		return;
	}

	window.__nidviteReportFormBindingsInitialized = true;

	document.addEventListener('submit', function (event) {
		var form = event.target && event.target.closest ? event.target.closest('form[data-nidvite-recaptcha]') : null;
		if (!form) return;

		if (window.nidviteSyncRecaptchaToken) {
			window.nidviteSyncRecaptchaToken();
		}
	}, true);

	document.addEventListener('blur', function (event) {
		var input = event.target && event.target.closest ? event.target.closest('[data-action="geocode-address"]') : null;
		if (!input) return;

		var alpineData = getAlpineDataContext(input);
		if (alpineData && typeof alpineData.geocodeAddress === 'function') {
			alpineData.geocodeAddress();
		}
	}, true);

	document.addEventListener('click', function (event) {
		var button = event.target && event.target.closest ? event.target.closest('[data-action="capture-location"]') : null;
		if (!button) return;

		var alpineData = getAlpineDataContext(button);
		if (alpineData && typeof alpineData.captureLocation === 'function') {
			alpineData.captureLocation();
		}
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', function () {
		bootNidvitePublicPages();
		initReportFormBindings();
	});
} else {
	bootNidvitePublicPages();
	initReportFormBindings();
}
