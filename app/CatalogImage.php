<?php

namespace App;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CatalogImage
{
    /** @param array<string, mixed> $attributes */
    public static function save(Product|Category $entry, array $attributes, ?UploadedFile $image, bool $removeImage): void
    {
        $previousImage = $entry->image;
        $newImage = null;

        try {
            if ($image !== null) {
                $newImage = $image->store('catalog', 'public');

                if ($newImage === false) {
                    throw ValidationException::withMessages(['image' => 'Görsel kaydedilemedi. Lütfen tekrar deneyin.']);
                }
            }

            $entry->fill($attributes);

            if ($newImage !== null) {
                $entry->image = $newImage;
            } elseif ($removeImage) {
                $entry->image = null;
            }

            $entry->save();
        } catch (Throwable $exception) {
            if (is_string($newImage)) {
                Storage::disk('public')->delete($newImage);
            }

            throw $exception;
        }

        if ($entry->image !== $previousImage) {
            self::deleteIfUnused($previousImage);
        }
    }

    public static function deleteIfUnused(?string $path): void
    {
        if (self::isUpload($path)
            && ! Product::where('image', $path)->exists()
            && ! Category::where('image', $path)->exists()) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function url(?string $path): string
    {
        if (self::isUpload($path) && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        $imagesDirectory = realpath(public_path('images'));
        $imagePath = is_string($path) && Str::startsWith($path, 'images/') ? realpath(public_path($path)) : false;

        if ($imagesDirectory !== false && $imagePath !== false
            && Str::startsWith($imagePath, $imagesDirectory.DIRECTORY_SEPARATOR) && is_file($imagePath)) {
            return asset($path);
        }

        return asset('images/product-placeholder.svg');
    }

    private static function isUpload(?string $path): bool
    {
        return is_string($path) && preg_match('/\Acatalog\/[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|webp)\z/', $path) === 1;
    }
}
