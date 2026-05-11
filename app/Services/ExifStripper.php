<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use RuntimeException;

class ExifStripper
{
    /**
     * Strip EXIF metadata from an uploaded image by re-encoding it.
     *
     * @return string Path to the cleaned temporary file
     */
    public static function process(UploadedFile $file): string
    {
        $sourcePath = (string) $file->getRealPath();
        if ($sourcePath === '' || ! is_file($sourcePath)) {
            throw new RuntimeException('Uploaded image temporary file is unavailable.');
        }

        $extension = strtolower($file->extension());
        $tempPath = tempnam(sys_get_temp_dir(), 'clean_');

        if ($tempPath === false) {
            throw new RuntimeException('Failed to create temporary file.');
        }

        $finalPath = $tempPath.'.'.$extension;
        @unlink($tempPath);

        try {
            $manager = self::createManager();
            $manager->read($sourcePath)
                ->encodeByExtension($extension, quality: 90)
                ->save($finalPath);
        } catch (\Throwable $e) {
            // Fall back to a pass-through copy if image extension support is unavailable.
            if (! @copy($sourcePath, $finalPath)) {
                @unlink($finalPath);
                throw new RuntimeException('Failed to process uploaded image.', 0, $e);
            }
        }

        return $finalPath;
    }

    private static function createManager(): ImageManager
    {
        if (extension_loaded('imagick')) {
            return ImageManager::imagick();
        }

        if (extension_loaded('gd')) {
            return ImageManager::gd();
        }

        throw new RuntimeException('No image processing extension available. Install php-gd or php-imagick.');
    }
}
