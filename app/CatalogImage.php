<?php

namespace App;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CatalogImage
{
    /** @param array<string, mixed> $attributes */
    public static function save(Product|Category|Post|Service $entry, array $attributes, ?UploadedFile $image, bool $removeImage): void
    {
        $imageField = $entry instanceof Post ? 'cover_image' : 'image';
        $previousImage = $entry->{$imageField};
        $newImage = null;

        try {
            if ($image !== null) {
                $newImage = $image->store($entry instanceof Post ? 'posts' : 'catalog', 'public');

                if ($newImage === false) {
                    throw ValidationException::withMessages([$imageField => 'Görsel kaydedilemedi. Lütfen tekrar deneyin.']);
                }
            }

            $entry->fill($attributes);

            if ($newImage !== null) {
                $entry->{$imageField} = $newImage;
            } elseif ($removeImage) {
                $entry->{$imageField} = null;
            }

            $entry->save();
        } catch (Throwable $exception) {
            if (is_string($newImage)) {
                Storage::disk('public')->delete($newImage);
            }

            throw $exception;
        }

        if ($entry->{$imageField} !== $previousImage) {
            self::deleteIfUnused($previousImage);
        }
    }

    public static function deleteIfUnused(?string $path): void
    {
        if (self::isUpload($path)
            && ! Product::where('image', $path)->exists()
            && ! Category::where('image', $path)->exists()
            && ! Post::where('cover_image', $path)->exists()
            && ! CmsImage::referenced($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function url(?string $path, string $placeholder = 'images/product-placeholder.svg'): string
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

        return asset($placeholder);
    }

    private static function isUpload(?string $path): bool
    {
        return is_string($path) && preg_match('/\A(?:catalog|posts|cms)\/[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|webp)\z/', $path) === 1;
    }
}
