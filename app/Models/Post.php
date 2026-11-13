<?php

namespace App\Models;

use App\CatalogImage;
use App\PostMarkdown;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['post_category_id', 'title', 'slug', 'excerpt', 'content', 'cover_image', 'type', 'status', 'is_featured', 'published_at', 'meta_title', 'meta_description', 'sort_order'])]
#[Hidden(['seed_key'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'published_at' => 'datetime', 'sort_order' => 'integer'];
    }

    /** @return BelongsTo<PostCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now())
            ->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->active());
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderByDesc('published_at')->orderBy('sort_order')->orderByDesc('id');
    }

    /** @return Attribute<string, never> */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): string => CatalogImage::url($this->cover_image, 'images/editorial.svg'));
    }

    /** @return Attribute<string, never> */
    protected function publicUrl(): Attribute
    {
        return Attribute::get(fn (): string => route($this->type === 'news' ? 'news.show' : 'blog.show', $this->slug));
    }

    /** @return Attribute<string, never> */
    protected function contentHtml(): Attribute
    {
        return Attribute::get(fn (): string => PostMarkdown::render($this->content));
    }
}
