window.nidviteReportFormMapData = function reportFormMapData(options) {
    const settings = options || {};

    return {
        map: null,
        marker: null,
        mapReady: false,
        geocoding: false,

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
            }

            this.mapReady = true;
        },

        updateMap(lat, lng) {
            if (!this.map) this.initMap();
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
                })
                .catch(() => {
                    this.geocoding = false;
                });
        },
    };
};
