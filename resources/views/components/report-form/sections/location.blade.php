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

            @if ($latitude !== null && $longitude !== null)
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

            <button type="button" data-action="capture-location"
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
