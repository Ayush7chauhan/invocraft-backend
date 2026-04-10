<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'short_name',
        'type',              // mass | volume | count | length | other
        'base_unit',         // reference unit short_name (e.g. kg, l, pcs, m)
        'conversion_factor', // 1 [this unit] = conversion_factor [base_unit]
    ];

    protected $casts = [
        'conversion_factor' => 'float',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
