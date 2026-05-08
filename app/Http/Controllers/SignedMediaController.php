<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SignedMediaController extends Controller
{
    public function __invoke(Media $media): RedirectResponse
    {
        if ($media->model_type !== Report::class || $media->collection_name !== 'report-photos') {
            abort(404);
        }

        $diskName = $media->disk;
        $driver = (string) config("filesystems.disks.{$diskName}.driver");

        if ($driver !== 's3') {
            abort(404);
        }

        $expiresAt = now()->addMinutes((int) config('media-library.temporary_url_default_lifetime', 5));
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($diskName);
        $temporaryUrl = $disk->temporaryUrl($media->getPathRelativeToRoot(), $expiresAt);

        return redirect()->away($temporaryUrl);
    }
}
