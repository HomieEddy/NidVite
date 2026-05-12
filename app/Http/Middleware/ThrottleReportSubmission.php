<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleReportSubmission
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isReportSubmissionRequest($request)) {
            return $next($request);
        }

        $fingerprint = $request->attributes->get('device_fingerprint_hash');
        if (! is_string($fingerprint) || $fingerprint === '') {
            return $this->tooManyRequestsResponse(__('report.validation.rate_limit_device_missing'));
        }

        $ipKey = 'report-submit:ip:'.$request->ip();
        $deviceKey = 'report-submit:device:'.$fingerprint;

        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            return $this->tooManyRequestsResponse(__('report.validation.rate_limit_ip'));
        }

        if (RateLimiter::tooManyAttempts($deviceKey, 10)) {
            return $this->tooManyRequestsResponse(__('report.validation.rate_limit_device'));
        }

        RateLimiter::hit($ipKey, 15 * 60);
        RateLimiter::hit($deviceKey, 60 * 60);

        return $next($request);
    }

    private function isReportSubmissionRequest(Request $request): bool
    {
        if (! $request->isMethod('POST') || ! $request->is('livewire/update')) {
            return false;
        }

        $components = $request->input('components');
        if (! is_array($components)) {
            return false;
        }

        foreach ($components as $component) {
            if (! is_array($component)) {
                continue;
            }

            $calls = $component['calls'] ?? [];
            $hasSubmitCall = collect($calls)->contains(
                fn ($call) => is_array($call) && ($call['method'] ?? null) === 'submit'
            );

            if (! $hasSubmitCall) {
                continue;
            }

            $snapshotName = $this->extractComponentName($component['snapshot'] ?? null);
            if ($snapshotName === 'report-form' || str_ends_with($snapshotName, '.report-form')) {
                return true;
            }
        }

        return false;
    }

    private function extractComponentName(mixed $snapshot): string
    {
        if (is_string($snapshot)) {
            /** @var array<string, mixed>|null $decoded */
            $decoded = json_decode($snapshot, true);

            return (string) data_get($decoded, 'memo.name', '');
        }

        if (is_array($snapshot)) {
            return (string) data_get($snapshot, 'memo.name', '');
        }

        return '';
    }

    private function tooManyRequestsResponse(string $message): Response
    {
        return response()->json([
            'message' => $message,
        ], 429);
    }
}
