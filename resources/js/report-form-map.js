window.nidviteReportFormMapData = function reportFormMapData(options) {
    const settings = options || {};

    return {
        map: null,
        marker: null,
        mapReady: false,
        geocoding: false,
        duplicateNudge: null,
        duplicateNudgeText: '',
        duplicateNudgeLinkText: settings.duplicateNudgeLink || 'View existing report',
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

            if (settings.initialLatitude && settings.initialLongitude) {
                this.updateMap(settings.initialLatitude, settings.initialLongitude);
                this.updateDuplicateNudge(settings.initialLatitude, settings.initialLongitude);
            }

            this.updateGpsWarning(settings.initialLatitude, settings.initialLongitude, settings.initialAccuracy);

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
                alert(settings.geolocationNotSupported || 'Geolocation not supported');
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
                    alert(settings.geolocationFailed || 'Unable to get location');
                }
            );
        },

        reverseGeocode(lat, lng) {
            const lang = document.documentElement.lang || 'en';

            fetch(
                'https://nominatim.openstreetmap.org/reverse?format=json&lat='
                    + lat
                    + '&lon='
                    + lng
                    + '&addressdetails=1&zoom=18&accept-language='
                    + lang
            )
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

            fetch(
                'https://nominatim.openstreetmap.org/search?format=json&q='
                    + encodeURIComponent(q)
                    + '&city=Montreal&country=Canada&countrycodes=ca&limit=1&addressdetails=1&bounded=1&viewbox=-74.01,45.72,-73.40,45.41'
            )
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

            fetch(`${settings.duplicateHintEndpoint}?${params.toString()}`)
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
                    this.duplicateNudgeText = (settings.duplicateNudgeMessage || 'A nearby open report already exists (:distance m).')
                        .replace(':distance', payload.report.distance_meters);
                })
                .catch(() => {});
        },

        updateGpsWarning(lat, lng, accuracy) {
            this.gpsWarning = '';

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                this.gpsWarning = settings.gpsWarningMissingMessage || 'No GPS coordinates detected yet.';
                return;
            }

            const threshold = Number(settings.gpsWarningAccuracyThreshold || 50);

            if (Number.isFinite(accuracy) && Number(accuracy) > threshold) {
                this.gpsWarning = settings.gpsWarningWeakMessage || 'GPS precision is weak.';
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
                        this.photoQualitySevere = settings.photoSevereMessage || 'Photo quality is too poor to submit.';
                        return;
                    }

                    const warning = results.find((result) => result && result.warning);
                    if (warning) {
                        this.photoQualityWarning = settings.photoWarningMessage || 'Photo quality may be low.';
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
