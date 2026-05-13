@php
    $currentLocale = app()->getLocale() === 'en' ? 'en' : 'fr';
    $nextLocale = $currentLocale === 'fr' ? 'en' : 'fr';

    $toggleLabel = $nextLocale === 'fr'
        ? __('filament.admin.language_switcher.toggle_to_french')
        : __('filament.admin.language_switcher.toggle_to_english');
@endphp

<div class="fi-topbar-item" style="margin-inline-start: 0.5rem;">
    <a
        href="{{ route('locale.switch', ['locale' => $nextLocale]) }}"
        class="fi-topbar-item-btn"
        title="{{ $toggleLabel }}"
        aria-label="{{ $toggleLabel }}"
    >
        <span
            class="fi-topbar-item-label {{ $currentLocale === 'fr' ? 'text-primary-600 dark:text-primary-400' : 'opacity-60' }}"
            @if ($currentLocale === 'fr') aria-current="true" @endif
            title="{{ __('filament.admin.language_switcher.french_active') }}"
        >FR</span>
        <span class="fi-topbar-item-label opacity-60">/</span>
        <span
            class="fi-topbar-item-label {{ $currentLocale === 'en' ? 'text-primary-600 dark:text-primary-400' : 'opacity-60' }}"
            @if ($currentLocale === 'en') aria-current="true" @endif
            title="{{ __('filament.admin.language_switcher.english_active') }}"
        >EN</span>
    </a>
</div>
