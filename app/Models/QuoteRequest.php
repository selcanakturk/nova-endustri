<?php

namespace App\Models;

use Database\Factories\QuoteRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

#[Fillable(['name', 'email', 'phone', 'company', 'quantity', 'unit', 'message'])]
class QuoteRequest extends Model
{
    /** @use HasFactory<QuoteRequestFactory> */
    use HasFactory;

    public const array STATUSES = ['new' => 'Yeni', 'reviewing' => 'İnceleniyor', 'quoted' => 'Teklif verildi', 'closed' => 'Kapatıldı', 'cancelled' => 'İptal edildi'];

    protected $attributes = ['status' => 'new'];

    protected function casts(): array
    {
        return ['quoted_at' => 'datetime', 'quantity' => 'decimal:3'];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function newlyReceived(Builder $query): void
    {
        $query->where('status', 'new');
    }

    /** @param Builder<static> $query */
    #[Scope]
    protected function ordered(Builder $query, string $direction = 'desc'): void
    {
        $query->orderBy('created_at', $direction)->orderBy('id', $direction);
    }

    public function transitionTo(string $status, ?string $note): void
    {
        if (! array_key_exists($status, self::STATUSES)) {
            throw new InvalidArgumentException('Geçersiz talep durumu.');
        }
        if ($status === 'quoted') {
            $this->quoted_at ??= now();
        } elseif (in_array($status, ['new', 'reviewing'], true)) {
            $this->quoted_at = null;
        }
        $this->status = $status;
        $this->admin_note = $note;
        $this->save();
    }
}
