{{-- Submit --}}
@if (config('services.recaptcha.enabled', true) && config('captcha.sitekey'))
    <div>
        <div class="g-recaptcha" data-sitekey="{{ config('captcha.sitekey') }}" data-callback="onReportRecaptchaSuccess" data-expired-callback="onReportRecaptchaExpired"></div>
        @error('recaptcha_response') <span class="mt-1.5 text-sm text-red-600 block">{{ $message }}</span> @enderror
    </div>
@elseif (config('services.recaptcha.enabled', true))
    <p class="text-sm text-red-600">{{ __('report.validation.captcha_unavailable') }}</p>
@endif

<div class="pt-2">
    <button type="submit"
        class="w-full flex justify-center items-center px-6 py-4 border border-transparent text-lg font-semibold rounded-xl shadow-lg text-white bg-gradient-to-r from-amber-700 to-orange-500 hover:from-amber-800 hover:to-orange-600 active:scale-[0.98] transition-all duration-200 disabled:opacity-50 btn-touch interactive-lift"
        x-bind:disabled="hasSeverePhotoIssue"
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
