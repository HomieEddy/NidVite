<?php

use App\Events\ReportCreated;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Services\ExifStripper;
use App\Services\RecaptchaValidator;
use App\Services\StreetProximityValidationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;

new class extends Component
{
    use WithFileUploads;
    use UsesSpamProtection;

    public HoneypotData $honeypotData;

    #[Validate('required|email|max:255')]
    public string $reporter_email = '';

    #[Validate('required|exists:report_categories,id')]
    public ?int $category_id = null;

    #[Validate('required|string|max:2000')]
    public string $description = '';

    #[Validate('required|string|max:500')]
    public string $address = '';

    #[Validate('nullable|string|max:100')]
    public string $neighborhood = '';

    #[Validate('nullable|string|max:100')]
    public string $borough = '';

    public ?float $latitude = null;
    public ?float $longitude = null;
    public ?float $location_accuracy = null;
    public ?string $location_source = null;

    #[Validate('nullable|array|max:5')]
    public array $photos = [];

    public array $photoPreviews = [];

    public string $recaptcha_response = '';

    public bool $submitted = false;

    public ?string $submittedTrackingId = null;

    public function mount(): void
    {
        $this->honeypotData = new HoneypotData();
        $pothole = ReportCategory::where('slug', 'pothole')->first();
        if ($pothole) {
            $this->category_id = $pothole->id;
        }
    }

    public function getPotholeCategoryProperty()
    {
        return ReportCategory::where('slug', 'pothole')->first();
    }

    public function getNeighborhoodsProperty(): array
    {
        return [
            'Ahuntsic', 'Bordeaux', 'Cartierville', 'Chinatown',
            'Côte-Saint-Luc', 'Côte-des-Neiges', 'Downtown',
            'Griffintown', 'Hampstead', 'Hochelaga',
            'Île-Bizard', 'La Petite-Patrie', 'Lachine',
            'LaSalle', 'Maisonneuve', 'Mercier',
            'Mile End', 'Montréal-Est', 'Montréal-Nord',
            'Mont-Royal', 'Notre-Dame-de-Grâce', 'Nouveau-Rosemont',
            'Outremont', 'Parc-Extension', 'Petite-Bourgogne',
            'Pierrefonds', 'Plateau-Mont-Royal', 'Pointe-aux-Trembles',
            'Pointe-Saint-Charles', 'Quartier des Spectacles',
            'Rivière-des-Prairies', 'Rosemont', 'Roxboro',
            'Sainte-Geneviève', 'Saint-Henri', 'Saint-Laurent',
            'Saint-Léonard', 'Saint-Michel', 'Snowdon',
            'Verdun', 'Vieux-Montréal', 'Village',
            'Ville-Marie', 'Villeray', 'Westmount',
        ];
    }

    public function getBoroughsProperty(): array
    {
        return [
            'Ahuntsic-Cartierville', 'Anjou',
            'Côte-des-Neiges–Notre-Dame-de-Grâce',
            'Lachine', 'LaSalle', 'Le Plateau-Mont-Royal',
            'Le Sud-Ouest', "L'Île-Bizard–Sainte-Geneviève",
            'Mercier–Hochelaga-Maisonneuve', 'Montréal-Nord',
            'Outremont', 'Pierrefonds-Roxboro',
            'Rivière-des-Prairies–Pointe-aux-Trembles',
            'Rosemont–La Petite-Patrie', 'Saint-Laurent',
            'Saint-Léonard', 'Verdun', 'Ville-Marie',
            'Villeray–Saint-Michel–Parc-Extension',
        ];
    }

    public function updatedPhotos(): void
    {
        $this->photoPreviews = [];
        foreach ($this->photos as $photo) {
            $this->photoPreviews[] = $photo->temporaryUrl();
        }
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        unset($this->photoPreviews[$index]);
        $this->photos = array_values($this->photos);
        $this->photoPreviews = array_values($this->photoPreviews);
    }

    public function submit(): void
    {
        $this->protectAgainstSpam();

        $validated = $this->validate([
            'reporter_email' => 'required|email|max:255',
            'category_id' => 'required|exists:report_categories,id',
            'description' => 'required|string|max:2000',
            'address' => 'required|string|max:500',
            'neighborhood' => 'nullable|string|max:100',
            'borough' => 'nullable|string|max:100',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'file|mimes:jpeg,png,gif,webp|max:10240',
            'recaptcha_response' => 'required|string',
        ], [
            'recaptcha_response.required' => __('report.validation.captcha_required'),
        ]);

        try {
            (new RecaptchaValidator())->validateOrFail(
                $validated['recaptcha_response'] ?? null,
                request()->ip() ?? ''
            );
        } catch (ValidationException $e) {
            $this->addError('recaptcha_response', $e->errors()['recaptcha_response'][0] ?? $e->getMessage());

            return;
        }

        if ($this->latitude === null || $this->longitude === null) {
            $this->addError('location', __('report.validation.location_required'));
            return;
        }

        try {
            Report::validateGeofence($this->latitude, $this->longitude);
        } catch (ValidationException $e) {
            $this->addError('location', $e->getMessage());
            return;
        }

        $validation = (new StreetProximityValidationService())->validate(
            $this->latitude,
            $this->longitude,
            $this->location_accuracy
        );

        if ($validation['should_block']) {
            $errorKey = match ($validation['reason']) {
                'off_street' => 'report.validation.off_street',
                'low_accuracy' => 'report.validation.low_accuracy',
                default => 'report.validation.off_street_and_low_accuracy',
            };
            $this->addError('location', __($errorKey));

            return;
        }

        $report = DB::transaction(function () use ($validated, $validation): Report {
            $report = Report::create([
                'reporter_email' => $validated['reporter_email'],
                'preferred_locale' => app()->getLocale(),
                'category_id' => $validated['category_id'],
                'description' => $validated['description'],
                'address' => $validated['address'],
                'neighborhood' => $validated['neighborhood'] ?: null,
                'borough' => $validated['borough'] ?: null,
                'road_distance_meters' => $validation['distance_meters'],
                'road_validation_decision' => $validation['decision'],
                'road_validation_reason' => $validation['reason'],
                'road_validation_mode' => $validation['mode'],
                'location_accuracy_passed' => $validation['accuracy_passed'],
            ]);

            $report->setLocation($this->latitude, $this->longitude, $this->location_accuracy, $this->location_source);

            if (! empty($this->photos)) {
                foreach ($this->photos as $photo) {
                    $cleanPath = ExifStripper::process($photo);

                    $report->addMedia($cleanPath)
                        ->usingName($photo->getClientOriginalName())
                        ->toMediaCollection('report-photos');
                }
            }

            return $report;
        });

        event(new ReportCreated($report));

        $this->submittedTrackingId = $report->public_tracking_id;
        $this->submitted = true;
        $this->reset(['reporter_email', 'category_id', 'description', 'address', 'neighborhood', 'borough', 'photos', 'photoPreviews', 'latitude', 'longitude', 'recaptcha_response']);
    }

    public function getCategoriesProperty()
    {
        return ReportCategory::where('is_active', true)->orderBy('sort_order')->get();
    }
} ?>

<div class="relative max-w-3xl mx-auto px-4 py-4 overflow-hidden" x-data="{
    map: null,
    marker: null,
    mapReady: false,
    geocoding: false,
    initMap() {
        if (this.map) return;
        var el = document.getElementById('form-map');
        if (!el) return;
        this.map = L.map(el).setView([45.5017, -73.5673], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(this.map);
        @if($latitude && $longitude)
        this.updateMap({{ $latitude }}, {{ $longitude }});
        @endif
        this.mapReady = true;
    },
    updateMap(lat, lng) {
        if (!this.map) this.initMap();
        if (this.marker) this.map.removeLayer(this.marker);
        this.marker = L.marker([lat, lng]).addTo(this.map);
        this.map.setView([lat, lng], 15);
    },
    captureLocation() {
        if (! navigator.geolocation) {
            alert(@js(__('report.geolocation_not_supported')));
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (position) => {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                var accuracy = position.coords.accuracy;
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
                alert(@js(__('report.geolocation_failed')));
            }
        );
    },
    reverseGeocode(lat, lng) {
        var lang = document.documentElement.lang || 'en';
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&addressdetails=1&accept-language=' + lang)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || !data.display_name) return;
                var parts = data.display_name.split(',');
                if (parts[0]) {
                    $wire.address = parts[0].trim();
                }
                if (data.address) {
                    if (data.address.suburb) {
                        $wire.neighborhood = data.address.suburb;
                    }
                    if (data.address.city_district) {
                        $wire.borough = data.address.city_district;
                    } else if (data.address.county) {
                        $wire.borough = data.address.county;
                    }
                }
            })
            .catch(function() {});
    },
    geocodeAddress() {
        if (this.geocoding) return;
        var el = this.$refs.addressInput;
        if (!el) return;
        var q = el.value.trim();
        if (q.length < 5) return;
        this.geocoding = true;
        fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q) + '&city=Montreal&country=Canada&limit=1')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                this.geocoding = false;
                if (!data || !data[0]) return;
                var lat = parseFloat(data[0].lat);
                var lng = parseFloat(data[0].lon);
                var bbox = data[0].boundingbox;
                var accuracy = null;
                if (bbox && bbox[0] && bbox[1] && bbox[2] && bbox[3]) {
                    var latErr = (parseFloat(bbox[1]) - parseFloat(bbox[0])) / 2;
                    var lngErr = (parseFloat(bbox[3]) - parseFloat(bbox[2])) / 2;
                    accuracy = Math.max(Math.abs(latErr), Math.abs(lngErr)) * 111320;
                }
                $wire.latitude = lat;
                $wire.longitude = lng;
                $wire.location_accuracy = accuracy;
                $wire.location_source = 'geocode';
                setTimeout(function() {
                    this.updateMap(lat, lng);
                }.bind(this), 300);
            }.bind(this))
            .catch(function() { this.geocoding = false; }.bind(this));
    }
}" x-init="$nextTick(() => { setTimeout(() => initMap(), 100); })">
    <div class="pointer-events-none absolute -top-24 -left-16 h-52 w-52 rounded-full bg-amber-300/25 blur-3xl"></div>
    <div class="pointer-events-none absolute top-20 -right-16 h-60 w-60 rounded-full bg-teal-300/20 blur-3xl"></div>
    @if ($submitted)
        {{-- Success State --}}
        <div class="citizen-card p-8 text-center animate-fade-in relative">
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
                <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-2xl font-extrabold font-display text-gray-900 mb-2">{{ __('report.success_title') }}</h2>
            <p class="text-gray-700 mb-2">{{ __('report.success_message') }}</p>
            <p class="text-sm text-gray-500 mb-8">{{ __('report.success_tracking') }}</p>
            @if($submittedTrackingId)
                <p class="text-sm text-gray-700 mb-4">
                    <span class="font-semibold">{{ __('tracking.Numéro') }}:</span>
                    <span class="font-mono bg-gray-100 px-2 py-0.5 rounded">{{ $submittedTrackingId }}</span>
                </p>
                <a href="{{ route('report.tracking', $submittedTrackingId) }}" class="mb-3 w-full inline-flex items-center justify-center px-6 py-3.5 border border-amber-200 text-base font-semibold rounded-xl text-amber-700 bg-amber-50 hover:bg-amber-100 active:scale-[0.98] transition-all duration-200 btn-touch interactive-lift">
                    {{ __('tracking.Suivi') }}
                </a>
            @endif
            <div class="flex flex-col gap-3">
                <button wire:click="$set('submitted', false)" class="w-full inline-flex items-center justify-center px-6 py-3.5 border border-transparent text-base font-semibold rounded-xl text-white bg-linear-to-r from-amber-700 to-orange-500 hover:from-amber-800 hover:to-orange-600 active:scale-[0.98] transition-all duration-200 btn-touch interactive-lift">
                    {{ __('report.new_report') }}
                </button>
                <a href="/" class="w-full inline-flex items-center justify-center px-6 py-3.5 border-2 border-amber-100 text-base font-medium rounded-xl text-gray-700 bg-white/90 hover:bg-white active:scale-[0.98] transition-all duration-200 btn-touch interactive-lift">
                    {{ app()->getLocale() === 'fr' ? 'Retour à l\'accueil' : 'Back to home' }}
                </a>
            </div>
        </div>
    @else
        {{-- Form Card --}}
        <div class="citizen-card overflow-hidden animate-slide-up relative">
            <div class="bg-linear-to-r from-amber-700 via-amber-600 to-orange-500 px-5 py-5">
                <h1 class="text-xl font-extrabold font-display text-white">{{ __('report.title') }}</h1>
                <p class="text-amber-100 text-sm mt-1">{{ __('report.subtitle') }}</p>
            </div>

            <form wire:submit="submit" class="p-5 space-y-5" x-on:submit="window.nidviteSyncRecaptchaToken && window.nidviteSyncRecaptchaToken()">
                <x-honeypot :livewireModel="'honeypotData'" />
                <input type="hidden" id="recaptcha-response" wire:model="recaptcha_response">

                {{-- Email --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        {{ __('report.email') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs text-gray-500 mb-2">{{ __('report.email_help') }}</p>
                    <input type="email" wire:model="reporter_email"
                        class="block w-full rounded-xl border-amber-100 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-base transition px-4 py-3 bg-white/90"
                        placeholder="exemple@email.com">
                    @error('reporter_email') <span class="mt-1.5 text-sm text-red-600">{{ $message }}</span> @enderror
                </div>

                {{-- Category -- Pothole only --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        {{ __('report.category') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center px-4 py-3 rounded-xl border border-amber-100 bg-amber-50/70 text-gray-700 text-base">
                        <svg class="w-5 h-5 mr-2 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke-width="2"/>
                            <circle cx="12" cy="12" r="3" fill="currentColor"/>
                        </svg>
                        @if ($this->potholeCategory)
                            {{ app()->getLocale() === 'fr' ? $this->potholeCategory->label_fr : $this->potholeCategory->label_en }}
                        @endif
                    </div>
                    <input type="hidden" name="category_id" wire:model="category_id">
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        {{ __('report.description') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs text-gray-500 mb-2">{{ __('report.description_help') }}</p>
                    <textarea wire:model="description" rows="4"
                        class="block w-full rounded-xl border-amber-100 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-base transition px-4 py-3 resize-none bg-white/90"
                        placeholder="Décrivez le problème en détail..."></textarea>
                    @error('description') <span class="mt-1.5 text-sm text-red-600">{{ $message }}</span> @enderror
                </div>

                {{-- Address --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        {{ __('report.address') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs text-gray-500 mb-2">{{ __('report.address_help') }}</p>
                    <input type="text" wire:model="address" x-ref="addressInput" x-on:blur="geocodeAddress()"
                        class="block w-full rounded-xl border-amber-100 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-base transition px-4 py-3 bg-white/90"
                        placeholder="123 rue Example">
                    @error('address') <span class="mt-1.5 text-sm text-red-600">{{ $message }}</span> @enderror
                </div>

                {{-- Neighborhood & Borough --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('report.neighborhood') }}</label>
                        <span class="text-xs text-gray-400">({{ __('report.optional') }})</span>
                        <input type="text" wire:model="neighborhood" list="neighborhoods-list" autocomplete="off"
                            class="mt-1 block w-full rounded-xl border-amber-100 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-base transition px-4 py-3 bg-white/90">
                        <datalist id="neighborhoods-list">
                            @foreach ($this->neighborhoods as $name)
                                <option value="{{ $name }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('report.borough') }}</label>
                        <span class="text-xs text-gray-400">({{ __('report.optional') }})</span>
                        <input type="text" wire:model="borough" list="boroughs-list" autocomplete="off"
                            class="mt-1 block w-full rounded-xl border-amber-100 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-base transition px-4 py-3 bg-white/90">
                        <datalist id="boroughs-list">
                            @foreach ($this->boroughs as $name)
                                <option value="{{ $name }}">
                            @endforeach
                        </datalist>
                    </div>
                </div>

                {{-- Location --}}
                <div class="bg-amber-50/80 rounded-xl p-4 border border-amber-200">
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-amber-700 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <label class="block text-sm font-semibold text-gray-700">{{ __('report.location') }}</label>
                            <p class="text-xs text-gray-500 mb-3">{{ __('report.location_help') }}</p>

                            @if ($latitude && $longitude)
                                <div class="flex items-center space-x-2 mb-3 flex-wrap gap-y-1">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ __('report.location_captured') }}
                                    </span>
                                    <span class="text-xs text-gray-500">{{ number_format($latitude, 5) }}, {{ number_format($longitude, 5) }}</span>
                                </div>
                            @endif
                            <div id="form-map" class="w-full h-52 rounded-xl border border-amber-100 mb-3" wire:ignore></div>

                            <button type="button" x-on:click="captureLocation()"
                                class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 border border-transparent text-sm font-semibold rounded-xl shadow-sm text-white bg-linear-to-r from-amber-700 to-orange-500 hover:from-amber-800 hover:to-orange-600 active:scale-[0.98] transition-all duration-200 btn-touch interactive-lift">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ __('report.capture_location') }}
                            </button>
                            @error('location') <span class="mt-2 text-sm text-red-600 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Photos --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        {{ __('report.photos') }}
                        <span class="text-xs text-gray-400 font-normal">({{ __('report.max_photos') }})</span>
                    </label>
                    <p class="text-xs text-gray-500 mb-3">{{ __('report.photos_help') }}</p>

                    {{-- Photo Previews --}}
                    @if (count($photoPreviews) > 0)
                        <div class="grid grid-cols-3 sm:grid-cols-5 gap-3 mb-3">
                            @foreach ($photoPreviews as $index => $preview)
                                <div class="relative aspect-square rounded-xl overflow-hidden border border-amber-100 bg-white">
                                    <img src="{{ $preview }}" class="w-full h-full object-cover" alt="Preview {{ $index + 1 }}">
                                    <button type="button" wire:click="removePhoto({{ $index }})"
                                        class="absolute top-1.5 right-1.5 bg-red-500 text-white rounded-full p-1.5 hover:bg-red-600 transition btn-touch">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (count($photos) < 5)
                        <label class="flex justify-center w-full h-28 px-4 transition bg-white/90 border-2 border-amber-200 border-dashed rounded-xl appearance-none cursor-pointer hover:border-amber-500 focus:outline-none interactive-lift">
                            <span class="flex items-center space-x-2">
                                <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="font-semibold text-gray-700">{{ __('report.photos') }}</span>
                            </span>
                            <input type="file" wire:model="photos" multiple accept="image/*" class="hidden">
                        </label>
                    @endif

                    <div wire:loading wire:target="photos" class="mt-2 text-sm text-amber-600 flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('report.loading') }}
                    </div>

                    @error('photos') <span class="mt-1.5 text-sm text-red-600 block">{{ $message }}</span> @enderror
                    @error('photos.*') <span class="mt-1.5 text-sm text-red-600 block">{{ $message }}</span> @enderror
                </div>

                {{-- Submit --}}
                @if (config('captcha.sitekey'))
                    <div>
                        <div class="g-recaptcha" data-sitekey="{{ config('captcha.sitekey') }}" data-callback="onReportRecaptchaSuccess" data-expired-callback="onReportRecaptchaExpired"></div>
                        @error('recaptcha_response') <span class="mt-1.5 text-sm text-red-600 block">{{ $message }}</span> @enderror
                    </div>
                @else
                    <p class="text-sm text-red-600">{{ __('report.validation.captcha_unavailable') }}</p>
                @endif

                <div class="pt-2">
                    <button type="submit"
                        class="w-full flex justify-center items-center px-6 py-4 border border-transparent text-lg font-semibold rounded-xl shadow-lg text-white bg-linear-to-r from-amber-700 to-orange-500 hover:from-amber-800 hover:to-orange-600 active:scale-[0.98] transition-all duration-200 disabled:opacity-50 btn-touch interactive-lift"
                        wire:loading.attr="disabled"
                        wire:target="submit">
                        <span wire:loading.remove wire:target="submit">{{ __('report.submit') }}</span>
                        <span wire:loading wire:target="submit" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('report.submitting') }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
