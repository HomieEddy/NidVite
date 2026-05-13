window.nidviteReportFormMapData = function reportFormMapData(options) {
    const settings = options || {};
    const locale = document.documentElement.lang || 'en';
    const isFrench = locale.toLowerCase().startsWith('fr');

    const parseOptionalNumber = (value) => {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        const parsed = Number(value);

        return Number.isFinite(parsed) ? parsed : null;
    };

    const localizedDefault = (enMessage, frMessage) => (isFrench ? frMessage : enMessage);

    const buildNominatimUrl = (path, params) => {
        const url = new URL(`https://nominatim.openstreetmap.org/${path}`);
        const searchParams = new URLSearchParams(params);

        searchParams.set('accept-language', locale);

        if (settings.nominatimContactEmail) {
            searchParams.set('email', settings.nominatimContactEmail);
        }

        url.search = searchParams.toString();

        return url.toString();
    };

    const fetchJsonWithTimeout = (url, timeoutMs = 6000) => {
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), timeoutMs);

        return fetch(url, {
            signal: controller.signal,
            headers: {
                'Accept-Language': locale,
            },
        }).finally(() => {
            window.clearTimeout(timeout);
        });
    };

    return {
        map: null,
        marker: null,
        mapReady: false,
        geocoding: false,
        duplicateNudge: null,
        duplicateNudgeText: '',
        duplicateNudgeLinkText: settings.duplicateNudgeLink || localizedDefault('View existing report', 'Voir le signalement existant'),
        gpsWarning: '',
        photoQualityWarning: '',
        photoQualitySevere: '',
        hasSeverePhotoIssue: false,

        initMap() {
            if (this.map) return;

            const el = document.getElementById('form-map');
            if (!el) return;

            this.map = L.map(el).setView([45.5017, -73.5673], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(this.map);

            const initialLatitude = parseOptionalNumber(settings.initialLatitude);
            const initialLongitude = parseOptionalNumber(settings.initialLongitude);

            if (initialLatitude !== null && initialLongitude !== null) {
                this.updateMap(initialLatitude, initialLongitude);
                this.updateDuplicateNudge(initialLatitude, initialLongitude);
            }

            this.updateGpsWarning(initialLatitude, initialLongitude, parseOptionalNumber(settings.initialAccuracy));

            this.mapReady = true;
        },

        updateMap(lat, lng) {
            if (!this.map) this.initMap();
            if (!this.map) return;

            if (this.marker) this.map.removeLayer(this.marker);
            this.marker = L.marker([lat, lng]).addTo(this.map);
            this.map.setView([lat, lng], 15);
        },

        captureLocation() {
            if (!navigator.geolocation) {
                alert(settings.geolocationNotSupported || localizedDefault('Geolocation is not supported by this browser.', 'La geolocalisation n\'est pas prise en charge par ce navigateur.'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = position.coords.accuracy;

                    $wire.latitude = lat;
                    $wire.longitude = lng;
                    $wire.location_accuracy = accuracy;
                    $wire.location_source = 'gps';

                    setTimeout(() => {
                        this.updateMap(lat, lng);
                    }, 300);

                    this.updateGpsWarning(lat, lng, accuracy);
                    this.updateDuplicateNudge(lat, lng);

                    this.reverseGeocode(lat, lng);
                },
                () => {
                    alert(settings.geolocationFailed || localizedDefault('Unable to get your location.', 'Impossible d\'obtenir votre position.'));
                }
            );
        },

        reverseGeocode(lat, lng) {
            const reverseUrl = buildNominatimUrl('reverse', {
                format: 'json',
                lat: String(lat),
                lon: String(lng),
                addressdetails: '1',
                zoom: '18',
            });

            fetchJsonWithTimeout(reverseUrl)
                .then((r) => r.json())
                .then((data) => {
                    if (!data || !data.display_name || !data.address) return;

                    const addr = data.address;
                    const houseNumber = addr.house_number || '';
                    const road = addr.road || addr.pedestrian || addr.footway || addr.path || '';
                    const streetAddress = (houseNumber ? `${houseNumber}, ` : '') + road;

                    if (streetAddress.trim() !== '') {
                        $wire.address = streetAddress.trim();
                    } else {
                        const parts = data.display_name.split(',');
                        if (parts[0]) {
                            $wire.address = parts[0].trim();
                        }
                    }

                    if (addr.suburb) {
                        $wire.neighborhood = addr.suburb;
                    } else if (addr.neighbourhood) {
                        $wire.neighborhood = addr.neighbourhood;
                    } else if (addr.quarter) {
                        $wire.neighborhood = addr.quarter;
                    }

                    if (addr.city_district) {
                        $wire.borough = addr.city_district;
                    } else if (addr.borough) {
                        $wire.borough = addr.borough;
                    } else if (addr.city) {
                        $wire.borough = addr.city;
                    } else if (addr.county) {
                        $wire.borough = addr.county;
                    }
                })
                .catch(() => {});
        },

        geocodeAddress() {
            if (this.geocoding) return;

            const el = this.$refs.addressInput;
            if (!el) return;

            const q = el.value.trim();
            if (q.length < 5) return;

            this.geocoding = true;

            const searchUrl = buildNominatimUrl('search', {
                format: 'json',
                q,
                city: 'Montreal',
                country: 'Canada',
                countrycodes: 'ca',
                limit: '1',
                addressdetails: '1',
                bounded: '1',
                viewbox: '-74.01,45.72,-73.40,45.41',
            });

            fetchJsonWithTimeout(searchUrl)
                .then((r) => r.json())
                .then((data) => {
                    this.geocoding = false;
                    if (!data || !data[0]) return;

                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    const bbox = data[0].boundingbox;
                    let accuracy = null;

                    if (bbox && bbox[0] && bbox[1] && bbox[2] && bbox[3]) {
                        const latErr = (parseFloat(bbox[1]) - parseFloat(bbox[0])) / 2;
                        const lngErr = (parseFloat(bbox[3]) - parseFloat(bbox[2])) / 2;
                        accuracy = Math.max(Math.abs(latErr), Math.abs(lngErr)) * 111320;
                    }

                    $wire.latitude = lat;
                    $wire.longitude = lng;
                    $wire.location_accuracy = accuracy;
                    $wire.location_source = 'geocode';

                    setTimeout(() => {
                        this.updateMap(lat, lng);
                    }, 300);

                    this.updateGpsWarning(lat, lng, accuracy);
                    this.updateDuplicateNudge(lat, lng);
                })
                .catch(() => {
                    this.geocoding = false;
                });
        },

        updateDuplicateNudge(lat, lng) {
            this.duplicateNudge = null;
            this.duplicateNudgeText = '';

            if (!settings.duplicateHintEndpoint || !Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const params = new URLSearchParams({
                latitude: String(lat),
                longitude: String(lng),
            });

            fetchJsonWithTimeout(`${settings.duplicateHintEndpoint}?${params.toString()}`, 3000)
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('duplicate_hint_failed');
                    }

                    return response.json();
                })
                .then((payload) => {
                    if (!payload.has_duplicate_nudge || !payload.report) {
                        return;
                    }

                    this.duplicateNudge = payload.report;
                    this.duplicateNudgeText = (settings.duplicateNudgeMessage || localizedDefault('A nearby open report already exists (:distance m).', 'Un signalement ouvert existe deja a proximite (:distance m).'))
                        .replace(':distance', payload.report.distance_meters);
                })
                .catch(() => {});
        },

        updateGpsWarning(lat, lng, accuracy) {
            this.gpsWarning = '';

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                this.gpsWarning = settings.gpsWarningMissingMessage || localizedDefault('No GPS coordinates detected yet.', 'Aucune coordonnee GPS detectee pour le moment.');
                return;
            }

            const threshold = Number(settings.gpsWarningAccuracyThreshold || 50);

            if (Number.isFinite(accuracy) && Number(accuracy) > threshold) {
                this.gpsWarning = settings.gpsWarningWeakMessage || localizedDefault('GPS precision is weak.', 'La precision GPS est faible.');
            }
        },

        onPhotosSelected(event) {
            const files = Array.from((event && event.target && event.target.files) || []);
            this.evaluatePhotoQuality(files);
        },

        canSubmitForm() {
            return !this.hasSeverePhotoIssue;
        },

        evaluatePhotoQuality(files) {
            this.hasSeverePhotoIssue = false;
            this.photoQualityWarning = '';
            this.photoQualitySevere = '';

            if (!files.length) {
                return;
            }

            Promise.all(files.map((file) => this.analyzeImage(file)))
                .then((results) => {
                    const severe = results.find((result) => result && result.severe);
                    if (severe) {
                        this.hasSeverePhotoIssue = true;
                        this.photoQualitySevere = settings.photoSevereMessage || localizedDefault('Photo quality is too poor to submit.', 'La qualite de la photo est trop faible pour soumettre.');
                        return;
                    }

                    const warning = results.find((result) => result && result.warning);
                    if (warning) {
                        this.photoQualityWarning = settings.photoWarningMessage || localizedDefault('Photo quality may be low.', 'La qualite de la photo peut etre faible.');
                    }
                })
                .catch(() => {});
        },

        analyzeImage(file) {
            return new Promise((resolve) => {
                if (!file || !file.type || file.type.indexOf('image/') !== 0) {
                    resolve({ severe: false, warning: false });
                    return;
                }

                const reader = new FileReader();
                reader.onload = () => {
                    const image = new Image();
                    image.onload = () => {
                        const maxSize = 220;
                        const ratio = Math.min(maxSize / image.width, maxSize / image.height, 1);
                        const width = Math.max(32, Math.floor(image.width * ratio));
                        const height = Math.max(32, Math.floor(image.height * ratio));

                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;

                        const context = canvas.getContext('2d', { willReadFrequently: true });
                        if (!context) {
                            resolve({ severe: false, warning: false });
                            return;
                        }

                        context.drawImage(image, 0, 0, width, height);
                        const imageData = context.getImageData(0, 0, width, height).data;
                        const luminance = [];

                        for (let i = 0; i < imageData.length; i += 4) {
                            const y = (0.2126 * imageData[i]) + (0.7152 * imageData[i + 1]) + (0.0722 * imageData[i + 2]);
                            luminance.push(y);
                        }

                        const averageLuminance = luminance.reduce((sum, value) => sum + value, 0) / luminance.length;

                        let laplacianSum = 0;
                        let samples = 0;

                        for (let y = 1; y < height - 1; y += 1) {
                            for (let x = 1; x < width - 1; x += 1) {
                                const index = (y * width) + x;
                                const center = luminance[index];
                                const top = luminance[index - width];
                                const bottom = luminance[index + width];
                                const left = luminance[index - 1];
                                const right = luminance[index + 1];
                                const laplacian = Math.abs((4 * center) - top - bottom - left - right);
                                laplacianSum += laplacian;
                                samples += 1;
                            }
                        }

                        const blurScore = samples > 0 ? laplacianSum / samples : 100;
                        const darkWarning = Number(settings.photoDarkWarningThreshold || 45);
                        const darkSevere = Number(settings.photoDarkSevereThreshold || 25);
                        const blurWarning = Number(settings.photoBlurWarningThreshold || 12);
                        const blurSevere = Number(settings.photoBlurSevereThreshold || 6);

                        resolve({
                            severe: averageLuminance < darkSevere || blurScore < blurSevere,
                            warning: averageLuminance < darkWarning || blurScore < blurWarning,
                        });
                    };

                    image.onerror = () => resolve({ severe: false, warning: false });
                    image.src = String(reader.result || '');
                };

                reader.onerror = () => resolve({ severe: false, warning: false });
                reader.readAsDataURL(file);
            });
        },
    };
};
