<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GenerateDeviceFingerprint
{
    public function handle(Request $request, Closure $next): Response
    {
        $signatureParts = [
            'ua' => $this->normalize($request->header('User-Agent')),
            'lang' => $this->normalize($request->header('Accept-Language')),
            'ch_ua' => $this->normalize($request->header('Sec-CH-UA')),
            'ch_mobile' => $this->normalize($request->header('Sec-CH-UA-Mobile')),
            'ch_platform' => $this->normalize($request->header('Sec-CH-UA-Platform')),
        ];

        $request->attributes->set(
            'device_fingerprint_hash',
            hash('sha256', implode('|', $signatureParts))
        );

        return $next($request);
    }

    private function normalize(?string $value): string
    {
        $normalized = trim(strtolower((string) $value));

        if ($normalized === '') {
            return 'na';
        }

        return preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
    }
}
