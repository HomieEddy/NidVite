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
    public array $photos = [];

    public array $photoPreviews = [];

    public string $recaptcha_response = '';

    public bool $submitted = false;

    public function mount(): void
    {
        $this->honeypotData = new HoneypotData();
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
        ]);

        if ($this->latitude === null || $this->longitude === null) {
            $this->addError('location', __('report.validation.location_required'));
            return;
        }

        try {
            Report::validateGeofence($this->latitude, $this->longitude);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('location', $e->getMessage());
            return;
        }

        $report = DB::transaction(function () use ($validated): Report {
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

        $this->submitted = true;
        $this->reset(['reporter_email', 'category_id', 'description', 'address', 'neighborhood', 'borough', 'photos', 'photoPreviews', 'latitude', 'longitude']);
    }

    public function getCategoriesProperty()
    {
        return ReportCategory::where('is_active', true)->orderBy('sort_order')->get();
    }
} ?>

<div class="min-h-screen bg-gray-50" x-data="{
    captureLocation() {
        if (! navigator.geolocation) {
            alert(@js(__('report.geolocation_not_supported')));
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (position) => {
                $wire.latitude = position.coords.latitude;
                $wire.longitude = position.coords.longitude;
            },
            () => {
                alert(@js(__('report.geolocation_failed')));
            }
        );
    }
}">
    {{-- Header --}}
    <header class="bg-amber-600 shadow-lg">
        <div class="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-xl font-bold text-white">{{ config('app.name') }}</span>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('locale.switch', 'fr') }}" class="px-2 py-1 rounded text-sm font-medium {{ app()->getLocale() === 'fr' ? 'bg-white text-amber-600' : 'text-amber-100 hover:text-white' }}">FR</a>
                <a href="{{ route('locale.switch', 'en') }}" class="px-2 py-1 rounded text-sm font-medium {{ app()->getLocale() === 'en' ? 'bg-white text-amber-600' : 'text-amber-100 hover:text-white' }}">EN</a>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="max-w-3xl mx-auto px-4 py-8">
        @if ($submitted)
            {{-- Success State --}}
            <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ __('report.success_title') }}</h2>
                <p class="text-gray-600 mb-6">{{ __('report.success_message') }}</p>
                <p class="text-sm text-gray-500 mb-8">{{ __('report.success_tracking') }}</p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="/suivi" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-amber-700 bg-amber-100 hover:bg-amber-200 transition">
                        {{ __('report.track_report') }}
                    </a>
                    <button wire:click="$set('submitted', false)" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-amber-600 hover:bg-amber-700 transition">
                        {{ __('report.new_report') }}
                    </button>
                </div>
            </div>
        @else
            {{-- Form Card --}}
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-amber-600 to-amber-500 px-6 py-6">
                    <h1 class="text-2xl font-bold text-white">{{ __('report.title') }}</h1>
                    <p class="text-amber-100 mt-1">{{ __('report.subtitle') }}</p>
                </div>

                <form wire:submit="submit" class="p-6 space-y-6">
                    <x-honeypot :livewireModel="'honeypotData'" />

                    {{-- Email --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            {{ __('report.email') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-gray-500 mb-2">{{ __('report.email_help') }}</p>
                        <input type="email" wire:model="reporter_email"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm transition"
                            placeholder="exemple@email.com">
                        @error('reporter_email') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            {{ __('report.category') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="category_id"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm transition">
                            <option value="">{{ __('report.choose') }}</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ app()->getLocale() === 'fr' ? $category->label_fr : $category->label_en }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            {{ __('report.description') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-gray-500 mb-2">{{ __('report.description_help') }}</p>
                        <textarea wire:model="description" rows="4"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm transition"
                            placeholder="Decrivez le probleme en detail..."></textarea>
                        @error('description') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    {{-- Address --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            {{ __('report.address') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-gray-500 mb-2">{{ __('report.address_help') }}</p>
                        <input type="text" wire:model="address"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm transition"
                            placeholder="123 rue Example">
                        @error('address') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    {{-- Neighborhood & Borough --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('report.neighborhood') }}</label>
                            <span class="text-xs text-gray-400">({{ __('report.optional') }})</span>
                            <input type="text" wire:model="neighborhood"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm transition">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('report.borough') }}</label>
                            <span class="text-xs text-gray-400">({{ __('report.optional') }})</span>
                            <input type="text" wire:model="borough"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm transition">
                        </div>
                    </div>

                    {{-- Location --}}
                    <div class="bg-amber-50 rounded-lg p-4 border border-amber-200">
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-gray-700">{{ __('report.location') }}</label>
                                <p class="text-xs text-gray-500 mb-3">{{ __('report.location_help') }}</p>

                                @if ($latitude && $longitude)
                                    <div class="flex items-center space-x-2 mb-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ __('report.location_captured') }}
                                        </span>
                                        <span class="text-xs text-gray-500">{{ number_format($latitude, 5) }}, {{ number_format($longitude, 5) }}</span>
                                    </div>
                                @endif

                                <button type="button" x-on:click="captureLocation()"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition">
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
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            {{ __('report.photos') }}
                            <span class="text-xs text-gray-400 font-normal">({{ __('report.max_photos') }})</span>
                        </label>
                        <p class="text-xs text-gray-500 mb-3">{{ __('report.photos_help') }}</p>

                        {{-- Photo Previews --}}
                        @if (count($photoPreviews) > 0)
                            <div class="grid grid-cols-3 sm:grid-cols-5 gap-3 mb-3">
                                @foreach ($photoPreviews as $index => $preview)
                                    <div class="relative aspect-square rounded-lg overflow-hidden border border-gray-200">
                                        <img src="{{ $preview }}" class="w-full h-full object-cover" alt="Preview {{ $index + 1 }}">
                                        <button type="button" wire:click="removePhoto({{ $index }})"
                                            class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if (count($photos) < 5)
                            <label class="flex justify-center w-full h-24 px-4 transition bg-white border-2 border-gray-300 border-dashed rounded-lg appearance-none cursor-pointer hover:border-amber-500 focus:outline-none">
                                <span class="flex items-center space-x-2">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="font-medium text-gray-600">{{ __('report.photos') }}</span>
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

                        @error('photos') <span class="mt-1 text-sm text-red-600 block">{{ $message }}</span> @enderror
                        @error('photos.*') <span class="mt-1 text-sm text-red-600 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Submit --}}
                    <div class="pt-4">
                        <button type="submit"
                            class="w-full flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition disabled:opacity-50"
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
    </main>

    {{-- Footer --}}
    <footer class="bg-white border-t mt-12">
        <div class="max-w-3xl mx-auto px-4 py-6 text-center">
            <p class="text-sm text-gray-500">
                {{ config('app.name') }} - {{ app()->getLocale() === 'fr' ? 'Ameliorons Montreal ensemble' : 'Improving Montreal together' }}
            </p>
        </div>
    </footer>
</div>
