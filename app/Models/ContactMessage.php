<?php

namespace App\Models;

use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

#[Fillable(['name', 'email', 'phone', 'company', 'subject', 'message'])]
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    public const array STATUSES = ['new' => 'Yeni', 'read' => 'Okundu', 'replied' => 'Yanıtlandı', 'archived' => 'Arşivlendi'];

    protected $attributes = ['status' => 'new'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'replied_at' => 'datetime'];
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
        if ($status === 'new') {
            $this->read_at = null;
            $this->replied_at = null;
        } elseif ($status === 'read') {
            $this->read_at ??= now();
            $this->replied_at = null;
        } elseif ($status === 'replied') {
            $this->read_at ??= now();
            $this->replied_at ??= now();
        }
        $this->status = $status;
        $this->admin_note = $note;
        $this->save();
    }
}
