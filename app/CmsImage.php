<?php

namespace App;

use App\Http\Requests\Admin\CmsRequest;
use App\Models\Page;
use App\Models\Service;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class CmsImage
{
    /** @param array<string, array<string, mixed>> $fields */
    public static function save(Page|SiteSetting $entry, CmsRequest $request, array $fields): void
    {
        $created = [];
        $previous = [];
        $validated = $request->validated();
        try {
            DB::transaction(function () use ($entry, $request, $fields, $validated, &$created, &$previous): void {
                $content = $entry instanceof Page ? ($entry->content ?? []) : ($entry->data ?? []);
                $seo = $entry instanceof Page ? ($entry->seo ?? []) : [];
                foreach ($fields as $key => $field) {
                    if ($entry instanceof Page && in_array($key, ['meta_title', 'meta_description', 'canonical_url', 'og_title', 'og_description', 'og_image'])) {
                        $target = &$seo;
                    } else {
                        $target = &$content;
                    }
                    if ($field['type'] === 'image') {
                        if ($request->hasFile($key)) {
                            $path = $request->file($key)->store('cms', 'public');
                            if ($path === false) {
                                throw ValidationException::withMessages([$key => 'Görsel kaydedilemedi.']);
                            }
                            $created[] = $path;
                            $previous[] = $target[$key] ?? null;
                            $target[$key] = $path;
                        } elseif ($request->boolean('remove_'.$key)) {
                            $previous[] = $target[$key] ?? null;
                            $target[$key] = null;
                        }
                    } elseif (array_key_exists($key, $validated)) {
                        $target[$key] = $validated[$key];
                    }
                    unset($target);
                }
                if ($entry instanceof Page) {
                    $entry->content = $content;
                    $entry->seo = $seo;
                } else {
                    $entry->data = $content;
                }
                $entry->save();
            });
        } catch (Throwable $exception) {
            foreach ($created as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }
        foreach ($previous as $path) {
            CatalogImage::deleteIfUnused($path);
        }
    }

    public static function referenced(string $path): bool
    {
        if (Service::where('image', $path)->exists()) {
            return true;
        }
        foreach (SiteSetting::cursor() as $settings) {
            if (in_array($path, $settings->data ?? [], true)) {
                return true;
            }
        }
        foreach (Page::cursor() as $page) {
            if (in_array($path, $page->content ?? [], true) || in_array($path, $page->seo ?? [], true)) {
                return true;
            }
        }

        return false;
    }
}
