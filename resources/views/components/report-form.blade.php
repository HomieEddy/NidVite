<?php

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Actions\Reports\SubmitReportAction;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Services\RecaptchaValidator;
use App\Services\StreetProximityValidationService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;

new class extends Component
{
    use UsesSpamProtection;
    use WithFileUploads;

    public HoneypotData $honeypotData;

    #[Validate('required|email|max:255')]
    public string $reporter_email = '';

    #[Validate('required|exists:report_categories,id')]
    public ?int $category_id = null;

    #[Validate('required|string|max:2000')]
    public string $description = '';

    #[Validate('required|string|max:500')]
    public string $address = '';

    #[Validate('nullable|string|max:100')]
    public string $neighborhood = '';

    #[Validate('nullable|string|max:100')]
    public string $borough = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?float $location_accuracy = null;

    public ?string $location_source = null;

    #[Validate('nullable|array|max:5')]
    public array $photos = [];

    public array $photoPreviews = [];

    public string $recaptcha_response = '';

    public bool $submitted = false;

    public ?string $submittedTrackingId = null;

    public ?string $submittedTrackingUrl = null;

    public string $submittedTrackingQrSvg = '';

    public function mount(): void
    {
        $this->honeypotData = new HoneypotData;
        $pothole = ReportCategory::where('slug', 'pothole')->first();
        if ($pothole) {
            $this->category_id = $pothole->id;
        }
    }

    public function getPotholeCategoryProperty()
    {
        return ReportCategory::where('slug', 'pothole')->first();
    }

    public function getNeighborhoodsProperty(): array
    {
        return config('report_form_location.neighborhoods', []);
    }

    public function getBoroughsProperty(): array
    {
        return config('report_form_location.boroughs', []);
    }

    public function updatedPhotos(): void
    {
        $this->photoPreviews = [];
        foreach ($this->photos as $photo) {
            $this->photoPreviews[] = $photo->temporaryUrl();
        }
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        unset($this->photoPreviews[$index]);
        $this->photos = array_values($this->photos);
        $this->photoPreviews = array_values($this->photoPreviews);
    }

    public function submit(): void
    {
        $this->protectAgainstSpam();

        $recaptchaEnabled = (bool) config('services.recaptcha.enabled', true);

        $validated = $this->validate([
            'reporter_email' => 'required|email|max:255',
            'category_id' => 'required|exists:report_categories,id',
            'description' => 'required|string|max:2000',
            'address' => 'required|string|max:500',
            'neighborhood' => 'nullable|string|max:100',
            'borough' => 'nullable|string|max:100',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'file|mimes:jpeg,png,gif,webp|max:10240',
            'recaptcha_response' => $recaptchaEnabled ? 'required|string' : 'nullable|string',
        ], [
            'recaptcha_response.required' => __('report.validation.captcha_required'),
        ]);

        try {
            (new RecaptchaValidator)->validateOrFail(
                $validated['recaptcha_response'] ?? null,
                request()->ip() ?? ''
            );
        } catch (ValidationException $e) {
            $this->addError('recaptcha_response', $e->errors()['recaptcha_response'][0] ?? $e->getMessage());

            return;
        }

        if ($this->latitude === null || $this->longitude === null) {
            $this->addError('location', __('report.validation.location_required'));

            return;
        }

        try {
            Report::validateGeofence($this->latitude, $this->longitude);
        } catch (ValidationException $e) {
            $this->addError('location', $e->getMessage());

            return;
        }

        $validation = (new StreetProximityValidationService)->validate(
            $this->latitude,
            $this->longitude,
            $this->location_accuracy
        );

        $isOffStreetDecision = in_array($validation['decision'], ['fail_off_street', 'fail_both'], true);

        if ($validation['should_block'] || $isOffStreetDecision) {
            $errorKey = match ($validation['reason']) {
                'off_street' => 'report.validation.off_street',
                'low_accuracy' => 'report.validation.low_accuracy',
                default => 'report.validation.off_street_and_low_accuracy',
            };
            $this->addError('location', __($errorKey));

            return;
        }

        $report = app(SubmitReportAction::class)(
            $validated,
            $this->latitude,
            $this->longitude,
            $this->location_accuracy,
            $this->location_source,
            $this->photos,
            $validation
        );

        $this->submittedTrackingId = $report->public_tracking_id;
        $this->submittedTrackingUrl = route('report.tracking', ['trackingId' => $report->public_tracking_id]);
        $this->submittedTrackingQrSvg = $this->makeQrSvg($this->submittedTrackingUrl);
        $this->submitted = true;
        $this->reset(['reporter_email', 'category_id', 'description', 'address', 'neighborhood', 'borough', 'photos', 'photoPreviews', 'latitude', 'longitude', 'recaptcha_response']);
    }

    public function getCategoriesProperty()
    {
        return ReportCategory::where('is_active', true)->orderBy('sort_order')->get();
    }

    private function makeQrSvg(string $content): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(168, 1), new SvgImageBackEnd));

        return $writer->writeString($content);
    }
} ?>

<div class="relative max-w-3xl mx-auto px-4 py-4 overflow-hidden"
    x-data="window.nidviteReportFormMapData({
        initialLatitude: @js($latitude),
        initialLongitude: @js($longitude),
        initialAccuracy: @js($location_accuracy),
        duplicateHintEndpoint: @js(route('api.reports.duplicate-hint')),
        duplicateNudgeMessage: @js(__('report.duplicate_nudge_message')),
        duplicateNudgeLink: @js(__('report.duplicate_nudge_link')),
        gpsWarningAccuracyThreshold: @js((int) config('tracking_experience.evidence.gps_warning_accuracy_meters', 50)),
        gpsWarningMissingMessage: @js(__('report.gps_warning_missing')),
        gpsWarningWeakMessage: @js(__('report.gps_warning_weak', ['meters' => (int) config('tracking_experience.evidence.gps_warning_accuracy_meters', 50)])),
        photoDarkWarningThreshold: @js((int) config('tracking_experience.evidence.photo.dark_warning_threshold', 45)),
        photoDarkSevereThreshold: @js((int) config('tracking_experience.evidence.photo.dark_severe_threshold', 25)),
        photoBlurWarningThreshold: @js((int) config('tracking_experience.evidence.photo.blur_warning_threshold', 12)),
        photoBlurSevereThreshold: @js((int) config('tracking_experience.evidence.photo.blur_severe_threshold', 6)),
        photoWarningMessage: @js(__('report.photo_quality_warning')),
        photoSevereMessage: @js(__('report.photo_quality_severe')),
        geolocationNotSupported: @js(__('report.geolocation_not_supported')),
        geolocationFailed: @js(__('report.geolocation_failed')),
    })"
    x-init="$nextTick(() => { setTimeout(() => initMap(), 100); })">
    <div class="pointer-events-none absolute -top-24 -left-16 h-52 w-52 rounded-full bg-amber-300/25 blur-3xl"></div>
    <div class="pointer-events-none absolute top-20 -right-16 h-60 w-60 rounded-full bg-teal-300/20 blur-3xl"></div>
    @if ($submitted)
        @include('components.report-form.success-state')
    @else
        @include('components.report-form.form-card')
    @endif
</div>
