<?php

namespace App\Actions\Reports;

use App\Events\ReportCreated;
use App\Models\Report;
use App\Services\ExifStripper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SubmitReportAction
{
    /**
     * @param  array<string, mixed>  $validated
     * @param  array<int, UploadedFile>  $photos
     * @param  array<string, mixed>  $validation
     */
    public function __invoke(
        array $validated,
        float $latitude,
        float $longitude,
        ?float $locationAccuracy,
        ?string $locationSource,
        array $photos,
        array $validation
    ): Report {
        /** @var array<int, array{path: string, originalName: string}> $preparedPhotos */
        $preparedPhotos = [];

        foreach ($photos as $photo) {
            $cleanPath = null;

            try {
                $cleanPath = ExifStripper::process($photo);

                $preparedPhotos[] = [
                    'path' => $cleanPath,
                    'originalName' => $photo->getClientOriginalName(),
                ];
            } catch (\Throwable $e) {
                if (is_string($cleanPath) && is_file($cleanPath)) {
                    @unlink($cleanPath);
                }

                foreach ($preparedPhotos as $preparedPhoto) {
                    if (is_file($preparedPhoto['path'])) {
                        @unlink($preparedPhoto['path']);
                    }
                }

                report($e);

                throw ValidationException::withMessages([
                    'photos' => [__('report.validation.photo_upload_failed')],
                ]);
            }
        }

        $report = DB::transaction(function () use ($validated, $latitude, $longitude, $locationAccuracy, $locationSource, $validation): Report {
            $report = Report::create([
                'reporter_email' => (string) $validated['reporter_email'],
                'preferred_locale' => app()->getLocale(),
                'category_id' => (int) $validated['category_id'],
                'description' => (string) $validated['description'],
                'address' => (string) $validated['address'],
                'neighborhood' => filled($validated['neighborhood'] ?? null) ? (string) $validated['neighborhood'] : null,
                'borough' => filled($validated['borough'] ?? null) ? (string) $validated['borough'] : null,
            ]);

            $report->road_distance_meters = $validation['distance_meters'];
            $report->road_validation_decision = $validation['decision'];
            $report->road_validation_reason = $validation['reason'];
            $report->road_validation_mode = $validation['mode'];
            $report->location_accuracy_passed = (bool) $validation['accuracy_passed'];
            $report->save();

            $report->setLocation($latitude, $longitude, $locationAccuracy, $locationSource);

            return $report;
        });

        try {
            foreach ($preparedPhotos as $preparedPhoto) {
                $report->addMedia($preparedPhoto['path'])
                    ->usingName($preparedPhoto['originalName'])
                    ->toMediaCollection('report-photos');

                if (is_file($preparedPhoto['path'])) {
                    @unlink($preparedPhoto['path']);
                }
            }
        } catch (\Throwable $e) {
            foreach ($preparedPhotos as $preparedPhoto) {
                if (is_file($preparedPhoto['path'])) {
                    @unlink($preparedPhoto['path']);
                }
            }

            report($e);

            throw new RuntimeException('Unable to attach report photos after report creation.', 0, $e);
        }

        event(new ReportCreated($report));

        return $report;
    }
}
