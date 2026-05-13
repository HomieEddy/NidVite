<div class="citizen-card overflow-hidden animate-slide-up relative">
    <div class="bg-gradient-to-r from-amber-700 via-amber-600 to-orange-500 px-5 py-5">
        <h1 class="text-xl font-extrabold font-display text-white">{{ __('report.title') }}</h1>
        <p class="text-amber-100 text-sm mt-1">{{ __('report.subtitle') }}</p>
    </div>

    <form wire:submit="submit" x-on:submit="if (!canSubmitForm()) { $event.preventDefault(); }" class="p-5 space-y-5" data-nidvite-recaptcha>
        <x-honeypot :livewireModel="'honeypotData'" />
        <input type="hidden" id="recaptcha-response" wire:model="recaptcha_response">

        @include('components.report-form.sections.contact-and-description')
        @include('components.report-form.sections.neighborhood-and-borough')
        @include('components.report-form.sections.location')
        @include('components.report-form.sections.photos')
        @include('components.report-form.sections.submit')
    </form>
</div>
