<x-filament-widgets::widget>
    <style>
        #admin-map-filters-controls .admin-map-filter-control {
            height: 2.5rem;
            border-radius: 0.5rem;
            opacity: 1;
            pointer-events: auto;
            filter: none;
        }

        #admin-map-filters-controls select.admin-map-filter-control {
            min-width: 180px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            padding: 0.375rem 0.625rem;
        }

        #admin-map-filters-controls button.admin-map-filter-control {
            padding: 0 0.875rem;
            font-weight: 600;
            cursor: pointer;
        }

        #admin-map-apply-filter {
            border: 1px solid #d97706;
            background: #d97706;
            color: #ffffff;
        }

        #admin-map-reset-filter {
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #374151;
        }
    </style>

    <x-filament::section>
        <x-slot name="heading">
            {{ __('dashboard.reports_map') }}
        </x-slot>

        <div id="admin-map-filters-controls" style="display: flex; gap: 0.75rem; align-items: end; flex-wrap: wrap; margin-bottom: 0.75rem;">
            <div style="display: flex; flex-direction: column; min-width: 180px;">
                <label for="admin-map-status-filter" style="font-size: 0.75rem; color: #4b5563; margin-bottom: 0.25rem;">
                    {{ __('dashboard.map_filter_status') }}
                </label>
                <select id="admin-map-status-filter" wire:model="selectedStatus" class="admin-map-filter-control">
                    <option value="">{{ __('dashboard.map_filter_all') }}</option>
                    @foreach ($this->getAvailableStatuses() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; flex-direction: column; min-width: 220px;">
                <label for="admin-map-borough-filter" style="font-size: 0.75rem; color: #4b5563; margin-bottom: 0.25rem;">
                    {{ __('dashboard.map_filter_borough') }}
                </label>
                <select id="admin-map-borough-filter" wire:model="selectedBorough" class="admin-map-filter-control">
                    <option value="">{{ __('dashboard.map_filter_all') }}</option>
                    @foreach ($this->getAvailableBoroughs() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button id="admin-map-apply-filter" type="button" wire:click="applyFilters" class="admin-map-filter-control">
                {{ __('dashboard.map_apply_filters') }}
            </button>

            <button id="admin-map-reset-filter" type="button" wire:click="resetFilters" class="admin-map-filter-control">
                {{ __('dashboard.map_reset_filters') }}
            </button>
        </div>

        <iframe
            id="admin-reports-map-frame"
            src="{{ $this->mapSrc }}"
            title="{{ __('dashboard.reports_map') }}"
            loading="lazy"
            style="display: block; width: 100%; height: 420px; border: 1px solid #e5e7eb; border-radius: 0.5rem;"
        ></iframe>
    </x-filament::section>
</x-filament-widgets::widget>
