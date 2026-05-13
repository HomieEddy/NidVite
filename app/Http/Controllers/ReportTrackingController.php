<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportFollower;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportTrackingController extends Controller
{
    public function show(string $trackingId): View
    {
        $report = Report::where('public_tracking_id', $trackingId)
            ->with(['category', 'media'])
            ->firstOrFail();

        $location = $report->coordinatePoint();

        $photoUrls = $report->signedPhotoUrls();

        $trackingUrl = route('report.tracking', ['trackingId' => $report->public_tracking_id]);
        $trackingQrSvg = $this->makeQrSvg($trackingUrl);
        $etaHint = $this->buildEtaHint($report);

        return view('tracking', compact('report', 'location', 'photoUrls', 'trackingUrl', 'trackingQrSvg', 'etaHint'));
    }

    public function duplicateHint(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $radiusMeters = (int) config('tracking_experience.duplicate_nudge.radius_meters', 50);
        $windowDays = (int) config('tracking_experience.duplicate_nudge.window_days', 30);
        $openStatuses = config('tracking_experience.duplicate_nudge.open_statuses', ['received', 'verified', 'scheduled', 'in_progress']);

        $report = Report::query()
            ->select(['id', 'public_tracking_id', 'status', 'address', 'created_at'])
            ->selectRaw(
                'ROUND(ST_Distance(location::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography)) AS distance_meters',
                [$longitude, $latitude]
            )
            ->whereNotNull('location')
            ->whereIn('status', $openStatuses)
            ->where('created_at', '>=', now()->subDays($windowDays))
            ->near($latitude, $longitude, $radiusMeters)
            ->orderByRaw(
                'ST_Distance(location::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) ASC',
                [$longitude, $latitude]
            )
            ->first();

        if ($report === null) {
            return response()->json([
                'has_duplicate_nudge' => false,
            ]);
        }

        return response()->json([
            'has_duplicate_nudge' => true,
            'report' => [
                'tracking_id' => $report->public_tracking_id,
                'status' => $report->status,
                'status_label' => __("report.status.{$report->status}"),
                'address' => $report->address,
                'distance_meters' => (int) ($report->distance_meters ?? 0),
                'tracking_url' => route('report.tracking', ['trackingId' => $report->public_tracking_id]),
            ],
        ]);
    }

    public function updatePreference(Request $request, string $trackingId): RedirectResponse
    {
        $report = Report::where('public_tracking_id', $trackingId)->firstOrFail();

        $validated = $request->validate([
            'notification_preference' => ['required', 'in:all,major,resolved'],
        ]);

        $report->update([
            'notification_preference' => $validated['notification_preference'],
        ]);

        return redirect()
            ->route('report.tracking', ['trackingId' => $report->public_tracking_id])
            ->with('tracking_notice', __('tracking.preferences_saved'));
    }

    public function follow(Request $request, string $trackingId): RedirectResponse
    {
        $report = Report::where('public_tracking_id', $trackingId)->firstOrFail();

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = mb_strtolower(trim((string) $validated['email']));

        $alreadyActive = $report->followers()
            ->where('email', $email)
            ->where('is_active', true)
            ->exists();

        if (! $alreadyActive) {
            ReportFollower::upsert([
                [
                    'report_id' => $report->id,
                    'email' => $email,
                    'preferred_locale' => app()->getLocale(),
                    'is_active' => true,
                    'unsubscribed_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ], ['report_id', 'email'], ['preferred_locale', 'is_active', 'unsubscribed_at', 'updated_at']);
        }

        return redirect()
            ->route('report.tracking', ['trackingId' => $report->public_tracking_id])
            ->with('tracking_notice', __($alreadyActive ? 'tracking.follow_already_active' : 'tracking.follow_saved'));
    }

    public function unsubscribe(string $trackingId, ReportFollower $follower): RedirectResponse
    {
        $report = Report::where('public_tracking_id', $trackingId)->firstOrFail();

        if ($follower->report_id !== $report->id) {
            abort(404);
        }

        $follower->update([
            'is_active' => false,
            'unsubscribed_at' => now(),
        ]);

        return redirect()
            ->route('report.tracking', ['trackingId' => $report->public_tracking_id])
            ->with('tracking_notice', __('tracking.unsubscribed'));
    }

    public function lookup(string $trackingId): JsonResponse
    {
        $report = Report::where('public_tracking_id', $trackingId)
            ->with(['category', 'media'])
            ->first();

        if (! $report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        $location = $report->coordinates();

        $steps = ['received', 'verified', 'scheduled', 'in_progress', 'repaired'];
        $currentIndex = array_search($report->status, $steps);
        if ($currentIndex === false) {
            $currentIndex = -1;
        }

        return response()->json([
            'tracking_id' => $report->public_tracking_id,
            'status' => $report->status,
            'status_label' => __("report.status.{$report->status}"),
            'address' => $report->address,
            'description' => $report->description,
            'category' => $report->category ? (app()->getLocale() === 'fr' ? $report->category->label_fr : $report->category->label_en) : null,
            'created_at' => $report->created_at->toIso8601String(),
            'rejection_reason' => $report->rejection_reason,
            'photos' => $report->signedPhotoUrls(),
            'location' => $location,
            'eta_hint' => $this->buildEtaHint($report),
            'progress' => [
                'current_step' => $currentIndex,
                'total_steps' => count($steps),
                'percent' => $report->status === 'rejected' ? 0 : ($currentIndex >= 0 ? (($currentIndex + 1) / count($steps)) * 100 : 0),
            ],
            'steps' => array_map(fn ($step, $idx) => [
                'status' => $step,
                'label' => __("report.status.{$step}"),
                'done' => $idx <= $currentIndex,
                'current' => $idx === $currentIndex,
            ], $steps, array_keys($steps)),
        ]);
    }

    private function makeQrSvg(string $content): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(168, 1), new SvgImageBackEnd));

        return $writer->writeString($content);
    }

    /**
     * @return array<string, int|string>|null
     */
    private function buildEtaHint(Report $report): ?array
    {
        $statusDays = config("tracking_experience.eta.status_days.{$report->status}");

        if (! is_array($statusDays)) {
            return null;
        }

        $zoneBucket = $this->resolveZoneBucket($report);
        $multiplier = (float) config("tracking_experience.eta.zone_multipliers.{$zoneBucket}", config('tracking_experience.eta.zone_multipliers.default', 1.0));

        $daysMin = (int) ceil(((int) $statusDays['min']) * $multiplier);
        $daysMax = (int) ceil(((int) $statusDays['max']) * $multiplier);

        if ($daysMin === 0 && $daysMax === 0) {
            return [
                'zone_bucket' => $zoneBucket,
                'zone_label' => __("tracking.eta_zone_{$zoneBucket}"),
                'label' => __('tracking.eta_hint_done'),
                'disclaimer' => __('tracking.eta_disclaimer'),
                'days_min' => 0,
                'days_max' => 0,
            ];
        }

        return [
            'zone_bucket' => $zoneBucket,
            'zone_label' => __("tracking.eta_zone_{$zoneBucket}"),
            'label' => __('tracking.eta_hint_range', [
                'min' => $daysMin,
                'max' => $daysMax,
                'zone' => __("tracking.eta_zone_{$zoneBucket}"),
            ]),
            'disclaimer' => __('tracking.eta_disclaimer'),
            'days_min' => $daysMin,
            'days_max' => $daysMax,
        ];
    }

    private function resolveZoneBucket(Report $report): string
    {
        $borough = trim((string) $report->borough);

        if ($borough === '') {
            return 'default';
        }

        $normalized = mb_strtolower($borough);

        foreach ((array) config('tracking_experience.eta.zone_boroughs', []) as $bucket => $boroughs) {
            foreach ((array) $boroughs as $candidate) {
                if ($normalized === mb_strtolower((string) $candidate)) {
                    return (string) $bucket;
                }
            }
        }

        return 'default';
    }
}
