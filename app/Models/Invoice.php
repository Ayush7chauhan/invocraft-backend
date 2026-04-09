<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'party_id',
        'invoice_number',
        'invoice_date',
        'subtotal',
        'discount',
        'tax_amount',
        'total_amount',
        'payment_status',
        'paid_amount',
        'notes',
    ];

    protected $casts = [
        'invoice_date'   => 'date',
        'subtotal'       => 'decimal:2',
        'discount'       => 'decimal:2',
        'tax_amount'     => 'decimal:2',
        'total_amount'   => 'decimal:2',
        'paid_amount'    => 'decimal:2',
        'payment_status' => PaymentStatus::class,
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ─── Computed Attributes ──────────────────────────────────────────────────

    public function getOutstandingAmountAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->paid_amount;
    }
}
