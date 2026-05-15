@if (app()->environment(['local', 'testing', 'staging']))
    <div
        x-data="{ visible: true }"
        x-init="setTimeout(() => visible = false, 5000)"
        x-show="visible"
        x-transition.opacity.duration.300ms
        class="px-4 pt-4"
    >
        <div class="max-w-3xl mx-auto rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-950 shadow-sm" role="status">
            <p class="font-semibold">{{ __('home.demo_notice.title') }}</p>
            <p class="mt-1 leading-5">{{ __('home.demo_notice.body') }}</p>
        </div>
    </div>
@endif
