{{-- Email --}}
<div>
    <label for="reporter_email" class="block text-sm font-semibold text-gray-700 mb-1.5">
        {{ __('report.email') }}
        <span class="text-red-500">*</span>
    </label>
    <p class="text-xs text-gray-500 mb-2">{{ __('report.email_help') }}</p>
    <input id="reporter_email" type="email" wire:model="reporter_email"
        class="block w-full rounded-xl border-amber-100 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-base transition px-4 py-3 bg-white/90"
        placeholder="{{ __('report.email_placeholder') }}">
    @error('reporter_email') <span class="mt-1.5 text-sm text-red-600">{{ $message }}</span> @enderror
</div>

{{-- Category -- Pothole only --}}
<div>
    <label for="category_display" class="block text-sm font-semibold text-gray-700 mb-1.5">
        {{ __('report.category') }}
        <span class="text-red-500">*</span>
    </label>
    <div id="category_display" class="flex items-center px-4 py-3 rounded-xl border border-amber-100 bg-amber-50/70 text-gray-700 text-base">
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
    <label for="report_description" class="block text-sm font-semibold text-gray-700 mb-1.5">
        {{ __('report.description') }}
        <span class="text-red-500">*</span>
    </label>
    <p class="text-xs text-gray-500 mb-2">{{ __('report.description_help') }}</p>
    <textarea id="report_description" wire:model="description" rows="4"
        class="block w-full rounded-xl border-amber-100 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-base transition px-4 py-3 resize-none bg-white/90"
        placeholder="{{ __('report.description_placeholder') }}"></textarea>
    @error('description') <span class="mt-1.5 text-sm text-red-600">{{ $message }}</span> @enderror
</div>

{{-- Address --}}
@if ($this->shouldShowManualLocationFields())
    <div>
        <label for="report_address" class="block text-sm font-semibold text-gray-700 mb-1.5">
            {{ __('report.address') }}
            <span class="text-red-500">*</span>
        </label>
        <p class="text-xs text-gray-500 mb-2">{{ __('report.address_help') }}</p>
        <input id="report_address" type="text" wire:model="address" x-ref="addressInput" data-action="geocode-address"
            class="block w-full rounded-xl border-amber-100 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-base transition px-4 py-3 bg-white/90"
            placeholder="{{ __('report.address_placeholder') }}">
        @error('address') <span class="mt-1.5 text-sm text-red-600">{{ $message }}</span> @enderror
    </div>
@endif
