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
        @if($submittedTrackingQrSvg !== '')
            <div class="mb-4 rounded-xl border border-gray-200 bg-gray-50 p-4" data-testid="success-tracking-qr">
                <p class="text-sm font-semibold text-gray-900 mb-2">{{ __('tracking.qr_title') }}</p>
                <div class="inline-flex rounded-lg bg-white p-2 border border-gray-200">{!! $submittedTrackingQrSvg !!}</div>
                @if($submittedTrackingUrl)
                    <p class="mt-2 text-xs text-gray-500 break-all">{{ $submittedTrackingUrl }}</p>
                @endif
            </div>
        @endif
        <a href="{{ route('report.tracking', $submittedTrackingId) }}" class="mb-3 w-full inline-flex items-center justify-center px-6 py-3.5 border border-amber-200 text-base font-semibold rounded-xl text-amber-700 bg-amber-50 hover:bg-amber-100 active:scale-[0.98] transition-all duration-200 btn-touch interactive-lift">
            {{ __('tracking.Suivi') }}
        </a>
    @endif
    <div class="flex flex-col gap-3">
        <button wire:click="$set('submitted', false)" class="w-full inline-flex items-center justify-center px-6 py-3.5 border border-transparent text-base font-semibold rounded-xl text-white bg-linear-to-r from-amber-700 to-orange-500 hover:from-amber-800 hover:to-orange-600 active:scale-[0.98] transition-all duration-200 btn-touch interactive-lift">
            {{ __('report.new_report') }}
        </button>
        <a href="/" class="w-full inline-flex items-center justify-center px-6 py-3.5 border-2 border-amber-100 text-base font-medium rounded-xl text-gray-700 bg-white/90 hover:bg-white active:scale-[0.98] transition-all duration-200 btn-touch interactive-lift">
            {{ __('report.back_to_home') }}
        </a>
    </div>
</div>
