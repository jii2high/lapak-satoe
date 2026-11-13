<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;

class ImageStorage
{
    public static function store(UploadedFile $file, string $directory, ?int $maxDimension = null): string
    {
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $name = Str::uuid()->toString().'.'.$extension;
        $directory = trim($directory, '/');

        if ($maxDimension === null) {
            $path = $file->storeAs($directory, $name, 'public');

            if ($path === false) {
                throw new RuntimeException('Gagal menyimpan berkas gambar.');
            }

            return $path;
        }

        $image = ImageManager::usingDriver(Driver::class)
            ->decodePath($file->getPathname())
            ->scaleDown($maxDimension, $maxDimension);

        $encoded = $image->encodeUsingFileExtension($extension, quality: 85);

        Storage::disk('public')->put($directory.'/'.$name, (string) $encoded);

        return $directory.'/'.$name;
    }

    public static function delete(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk('public')->delete($path);
        }
    }
}
