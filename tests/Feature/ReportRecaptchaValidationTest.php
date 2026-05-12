<?php

use App\Services\RecaptchaValidator;
use Illuminate\Validation\ValidationException;

it('fails validation when recaptcha token is missing', function () {
    config()->set('services.recaptcha.enabled', true);

    $validator = new RecaptchaValidator;

    expect(fn () => $validator->validateOrFail('', '127.0.0.1'))
        ->toThrow(ValidationException::class, __('report.validation.captcha_required'));
});

it('fails validation when recaptcha verification is invalid', function () {
    config()->set('services.recaptcha.enabled', true);

    app()->bind('captcha', fn () => new class
    {
        public function verifyResponse(string $token, string $ipAddress): bool
        {
            return false;
        }
    });

    $validator = new RecaptchaValidator;

    expect(fn () => $validator->validateOrFail('invalid-token', '127.0.0.1'))
        ->toThrow(ValidationException::class, __('report.validation.captcha_invalid'));
});

it('passes validation when recaptcha verification succeeds', function () {
    config()->set('services.recaptcha.enabled', true);

    app()->bind('captcha', fn () => new class
    {
        public function verifyResponse(string $token, string $ipAddress): bool
        {
            return true;
        }
    });

    $validator = new RecaptchaValidator;

    expect(fn () => $validator->validateOrFail('valid-token', '127.0.0.1'))
        ->not->toThrow(ValidationException::class);
});

it('skips recaptcha validation when recaptcha is disabled', function () {
    config()->set('services.recaptcha.enabled', false);

    app()->bind('captcha', fn () => new class
    {
        public function verifyResponse(string $token, string $ipAddress): bool
        {
            throw new RuntimeException('captcha service should not be called when recaptcha is disabled');
        }
    });

    $validator = new RecaptchaValidator;

    expect(fn () => $validator->validateOrFail('', '127.0.0.1'))
        ->not->toThrow(ValidationException::class);
});
