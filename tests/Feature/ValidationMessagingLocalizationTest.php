<?php

it('provides localized off-street and low-accuracy guidance in english', function () {
    app()->setLocale('en');

    $offStreet = __('report.validation.off_street');
    $lowAccuracy = __('report.validation.low_accuracy');

    expect($offStreet)->toContain('street')
        ->and($lowAccuracy)->toContain('recapture');
});

it('provides localized off-street and low-accuracy guidance in french', function () {
    app()->setLocale('fr');

    $offStreet = __('report.validation.off_street');
    $lowAccuracy = __('report.validation.low_accuracy');

    expect($offStreet)->toContain('rue')
        ->and($lowAccuracy)->toContain('recapturer');
});
