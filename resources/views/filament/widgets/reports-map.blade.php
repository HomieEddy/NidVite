<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('dashboard.reports_map') }}
        </x-slot>

        <div style="display: flex; gap: 0.75rem; align-items: end; flex-wrap: wrap; margin-bottom: 0.75rem;">
            <div style="display: flex; flex-direction: column; min-width: 180px;">
                <label for="admin-map-status-filter" style="font-size: 0.75rem; color: #4b5563; margin-bottom: 0.25rem;">
                    {{ __('dashboard.map_filter_status') }}
                </label>
                <select id="admin-map-status-filter" style="height: 2.5rem; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.375rem 0.625rem;">
                    <option value="">{{ __('dashboard.map_filter_all') }}</option>
                    <option value="received">{{ __('filament.admin.resources.reports.statuses.received') }}</option>
                    <option value="verified">{{ __('filament.admin.resources.reports.statuses.verified') }}</option>
                    <option value="scheduled">{{ __('filament.admin.resources.reports.statuses.scheduled') }}</option>
                    <option value="in_progress">{{ __('filament.admin.resources.reports.statuses.in_progress') }}</option>
                    <option value="repaired">{{ __('filament.admin.resources.reports.statuses.repaired') }}</option>
                    <option value="rejected">{{ __('filament.admin.resources.reports.statuses.rejected') }}</option>
                </select>
            </div>

            <div style="display: flex; flex-direction: column; min-width: 240px;">
                <label for="admin-map-neighborhood-filter" style="font-size: 0.75rem; color: #4b5563; margin-bottom: 0.25rem;">
                    {{ __('dashboard.map_filter_neighborhood') }}
                </label>
                <input id="admin-map-neighborhood-filter" type="text" placeholder="{{ __('dashboard.map_filter_neighborhood_placeholder') }}" style="height: 2.5rem; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.375rem 0.625rem;" />
            </div>

            <button id="admin-map-apply-filter" type="button" style="height: 2.5rem; padding: 0 0.875rem; border-radius: 0.5rem; border: 1px solid #d97706; background: #d97706; color: #fff; font-weight: 600; cursor: pointer;">
                {{ __('dashboard.map_apply_filters') }}
            </button>

            <button id="admin-map-reset-filter" type="button" style="height: 2.5rem; padding: 0 0.875rem; border-radius: 0.5rem; border: 1px solid #d1d5db; background: #fff; color: #374151; font-weight: 600; cursor: pointer;">
                {{ __('dashboard.map_reset_filters') }}
            </button>
        </div>

        <iframe
            id="admin-reports-map-frame"
            src="{{ route('map.public', ['embed' => 1]) }}"
            data-base-src="{{ route('map.public', ['embed' => 1]) }}"
            title="{{ __('dashboard.reports_map') }}"
            loading="lazy"
            style="display: block; width: 100%; height: 420px; border: 1px solid #e5e7eb; border-radius: 0.5rem;"
        ></iframe>

        <script>
            (function () {
                const frame = document.getElementById('admin-reports-map-frame');
                const statusInput = document.getElementById('admin-map-status-filter');
                const neighborhoodInput = document.getElementById('admin-map-neighborhood-filter');
                const applyButton = document.getElementById('admin-map-apply-filter');
                const resetButton = document.getElementById('admin-map-reset-filter');

                if (!frame || !statusInput || !neighborhoodInput || !applyButton || !resetButton) {
                    return;
                }

                const updateFrameSource = function () {
                    const url = new URL(frame.dataset.baseSrc || frame.src, window.location.origin);
                    url.searchParams.set('embed', '1');

                    const status = statusInput.value.trim();
                    const neighborhood = neighborhoodInput.value.trim();

                    if (status !== '') {
                        url.searchParams.set('status', status);
                    } else {
                        url.searchParams.delete('status');
                    }

                    if (neighborhood !== '') {
                        url.searchParams.set('neighborhood', neighborhood);
                    } else {
                        url.searchParams.delete('neighborhood');
                    }

                    frame.src = url.toString();
                };

                applyButton.addEventListener('click', updateFrameSource);
                resetButton.addEventListener('click', function () {
                    statusInput.value = '';
                    neighborhoodInput.value = '';
                    updateFrameSource();
                });
            })();
        </script>
    </x-filament::section>
</x-filament-widgets::widget>
