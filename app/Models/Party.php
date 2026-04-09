<?php

namespace App\Models;

use App\Enums\PartyType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Party extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'mobile',
        'email',
        'address',
        'gst_number',
        'type',
        'opening_balance',
        'status',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'type'            => PartyType::class,
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ─── Computed Attributes ──────────────────────────────────────────────────

    /**
     * Current ledger balance for this party.
     * Positive = party owes us (receivable).
     * Negative = we owe party (payable).
     */
    public function getBalanceAttribute(): float
    {
        $debit  = (float) $this->transactions()->where('type', 'debit')->sum('amount');
        $credit = (float) $this->transactions()->where('type', 'credit')->sum('amount');

        return ((float) $this->opening_balance + $credit) - $debit;
    }
}
