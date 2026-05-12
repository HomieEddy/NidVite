<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SignedMediaController extends Controller
{
    public function __invoke(Media $media): RedirectResponse|Response
    {
        if ($media->model_type !== Report::class || $media->collection_name !== 'report-photos') {
            abort(404);
        }

        $diskName = $media->disk;
        $driver = (string) config("filesystems.disks.{$diskName}.driver");
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($diskName);
        $path = $media->getPathRelativeToRoot();

        if (! $disk->exists($path)) {
            abort(404);
        }

        if ($driver === 's3') {
            $expiresAt = now()->addMinutes((int) config('media-library.temporary_url_default_lifetime', 5));
            $temporaryUrl = $disk->temporaryUrl($path, $expiresAt);

            return redirect()->away($temporaryUrl);
        }

        try {
            $content = $disk->get($path);
        } catch (FileNotFoundException) {
            abort(404);
        }

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $filename = $media->file_name ?: basename($path);

        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
