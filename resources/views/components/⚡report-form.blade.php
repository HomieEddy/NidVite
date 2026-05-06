<?php

use App\Events\ReportCreated;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Services\ExifStripper;
use Illuminate\Support\Facades\DB;
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

    #[Validate('nullable|array|max:5')]
    #[Validate('each:file|mimes:jpeg,png,gif,webp|max:10240')]
    public array $photos = [];

    public string $recaptcha_response = '';

    public bool $submitted = false;

    public function mount(): void
    {
        $this->honeypotData = new HoneypotData();
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
        ]);

        // Require location
        if ($this->latitude === null || $this->longitude === null) {
            $this->addError('location', __('report.validation.location_required'));
            return;
        }

        // Validate geofence (Montreal only)
        try {
            Report::validateGeofence($this->latitude, $this->longitude);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('location', $e->getMessage());
            return;
        }

        $report = Report::create([
            'reporter_email' => $validated['reporter_email'],
            'preferred_locale' => app()->getLocale(),
            'category_id' => $validated['category_id'],
            'description' => $validated['description'],
            'address' => $validated['address'],
            'neighborhood' => $validated['neighborhood'] ?: null,
            'borough' => $validated['borough'] ?: null,
            'ip_address_hash' => hash('sha256', request()->ip() ?? 'unknown'),
            'ip_address_raw' => request()->ip(),
            'user_agent_hash' => hash('sha256', request()->userAgent() ?? ''),
        ]);

        $report->setLocation($this->latitude, $this->longitude);

        event(new ReportCreated($report));

        if (! empty($this->photos)) {
            foreach ($this->photos as $photo) {
                $cleanPath = ExifStripper::process($photo);

                $report->addMedia($cleanPath)
                    ->usingName($photo->getClientOriginalName())
                    ->toMediaCollection('report-photos');
            }
        }

        $this->submitted = true;
        $this->reset(['reporter_email', 'category_id', 'description', 'address', 'neighborhood', 'borough', 'photos', 'latitude', 'longitude']);
    }

    public function getCategoriesProperty()
    {
        return ReportCategory::where('is_active', true)->orderBy('sort_order')->get();
    }
} ?>

<div class="min-h-screen bg-gray-50 py-8 px-4">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ __('report.title') }}</h1>
        <p class="text-gray-600 mb-6">{{ __('report.subtitle') }}</p>

        @if ($submitted)
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ __('report.success_message') }}
            </div>
        @endif

        <form wire:submit="submit" class="space-y-4">
            <x-honeypot :livewireModel="'honeypotData'" />

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('report.email') }} *</label>
                <input type="email" wire:model="reporter_email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                @error('reporter_email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('report.category') }} *</label>
                <select wire:model="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    <option value="">{{ __('report.choose') }}</option>
                    @foreach ($this->categories as $category)
                        <option value="{{ $category->id }}">{{ app()->getLocale() === 'fr' ? $category->label_fr : $category->label_en }}</option>
                    @endforeach
                </select>
                @error('category_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('report.description') }} *</label>
                <textarea wire:model="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
                @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('report.address') }} *</label>
                <input type="text" wire:model="address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                @error('address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('report.neighborhood') }}</label>
                    <input type="text" wire:model="neighborhood" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('report.borough') }}</label>
                    <input type="text" wire:model="borough" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('report.location') }} *</label>
                <button type="button" x-data="" x-on:click="
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition((position) => {
                            $wire.latitude = position.coords.latitude;
                            $wire.longitude = position.coords.longitude;
                            alert('Localisation capturee: ' + position.coords.latitude + ', ' + position.coords.longitude);
                        }, () => {
                            alert('Impossible d\'obtenir la localisation.');
                        });
                    } else {
                        alert('Geolocalisation non supportee.');
                    }
                " class="mt-1 inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 focus:bg-amber-700 active:bg-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('report.capture_location') }}
                </button>
                @if ($latitude && $longitude)
                    <span class="ml-2 text-sm text-green-600">{{ $latitude }}, {{ $longitude }}</span>
                @endif
                @error('location') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('report.photos') }} ({{ __('report.max_photos') }})</label>
                <input type="file" wire:model="photos" multiple accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                @error('photos') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                @error('photos.*') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                <div wire:loading wire:target="photos" class="text-sm text-gray-500 mt-1">{{ __('report.loading') }}</div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 focus:bg-amber-700 active:bg-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('report.submit') }}
                </button>
            </div>
        </form>
    </div>
</div>
