<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('dashboard.reports_map') }}
        </x-slot>

        <iframe
            src="{{ route('map.public', ['embed' => 1]) }}"
            title="{{ __('dashboard.reports_map') }}"
            loading="lazy"
            style="display: block; width: 100%; height: 420px; border: 1px solid #e5e7eb; border-radius: 0.5rem;"
        ></iframe>
    </x-filament::section>
</x-filament-widgets::widget>
