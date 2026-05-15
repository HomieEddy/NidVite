{{-- Neighborhood & Borough --}}
<div class="grid grid-cols-2 gap-3">
    <div>
        <label for="neighborhood" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('report.neighborhood') }}</label>
        <span id="neighborhood-optional" class="text-xs text-gray-400">({{ __('report.optional') }})</span>
        <input id="neighborhood" type="text" wire:model="neighborhood" list="neighborhoods-list" autocomplete="off" aria-describedby="neighborhood-optional"
            class="mt-1 block w-full rounded-xl border-amber-100 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-base transition px-4 py-3 bg-white/90">
        <datalist id="neighborhoods-list">
            @foreach ($this->neighborhoods as $name)
                <option value="{{ $name }}">
            @endforeach
        </datalist>
    </div>
    <div>
        <label for="borough" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('report.borough') }}</label>
        <span id="borough-optional" class="text-xs text-gray-400">({{ __('report.optional') }})</span>
        <input id="borough" type="text" wire:model="borough" list="boroughs-list" autocomplete="off" aria-describedby="borough-optional"
            class="mt-1 block w-full rounded-xl border-amber-100 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-base transition px-4 py-3 bg-white/90">
        <datalist id="boroughs-list">
            @foreach ($this->boroughs as $name)
                <option value="{{ $name }}">
            @endforeach
        </datalist>
    </div>
</div>
