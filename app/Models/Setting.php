<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invoice_prefix',
        'invoice_start_number',
        'currency',
        'currency_symbol',
        'timezone',
        'date_format',
        'tax_name',
        'default_tax_rate',
        'show_tax_on_invoice',
        'invoice_footer_note',
    ];

    protected $casts = [
        'invoice_start_number' => 'integer',
        'default_tax_rate'     => 'decimal:2',
        'show_tax_on_invoice'  => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
