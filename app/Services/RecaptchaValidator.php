<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class RecaptchaValidator
{
    /**
     * @throws ValidationException
     */
    public function validateOrFail(?string $token, string $ipAddress): void
    {
        if (! config('services.recaptcha.enabled', true)) {
            return;
        }

        if (! is_string($token) || trim($token) === '') {
            throw ValidationException::withMessages([
                'recaptcha_response' => [__('report.validation.captcha_required')],
            ]);
        }

        if (! app('captcha')->verifyResponse($token, $ipAddress)) {
            throw ValidationException::withMessages([
                'recaptcha_response' => [__('report.validation.captcha_invalid')],
            ]);
        }
    }
}
