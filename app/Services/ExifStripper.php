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
        $manager = self::createManager();

        $image = $manager->read($file->getRealPath());

        $extension = strtolower($file->getClientOriginalExtension());
        $tempPath = tempnam(sys_get_temp_dir(), 'clean_').'.'.$extension;

        $image->encodeByExtension($extension, quality: 90)->save($tempPath);

        return $tempPath;
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
