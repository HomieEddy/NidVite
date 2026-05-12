{{-- Photos --}}
<div>
    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
        {{ __('report.photos') }}
        <span class="text-xs text-gray-400 font-normal">({{ __('report.max_photos') }})</span>
    </label>
    <p class="text-xs text-gray-500 mb-3">{{ __('report.photos_help') }}</p>

    <div x-show="photoQualityWarning" x-cloak data-action="photo-quality-warning" class="mb-3 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900">
        <span x-text="photoQualityWarning"></span>
    </div>

    <div x-show="photoQualitySevere" x-cloak data-action="photo-quality-severe" class="mb-3 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900">
        <span x-text="photoQualitySevere"></span>
    </div>

    {{-- Photo Previews --}}
    @if (count($photoPreviews) > 0)
        <div class="grid grid-cols-3 sm:grid-cols-5 gap-3 mb-3">
            @foreach ($photoPreviews as $index => $preview)
                <div class="relative aspect-square rounded-xl overflow-hidden border border-amber-100 bg-white">
                    <img src="{{ $preview }}" class="w-full h-full object-cover" alt="{{ __('report.photo_preview_alt', ['number' => $index + 1]) }}">
                    <button type="button" wire:click="removePhoto({{ $index }})" aria-label="Remove photo"
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
            <input type="file" wire:model="photos" x-on:change="onPhotosSelected($event)" multiple accept="image/*" class="hidden">
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
