<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'sku',
        'barcode',
        'category',         // legacy string – kept for backward compat
        'category_id',      // FK to categories
        'unit_id',          // FK to units
        'purchase_price',
        'selling_price',
        'stock_quantity',
        'low_stock_threshold',
        'tax_rate',
        'status',
    ];

    protected $casts = [
        'purchase_price'     => 'decimal:2',
        'selling_price'      => 'decimal:2',
        'stock_quantity'     => 'integer',
        'low_stock_threshold'=> 'integer',
        'tax_rate'           => 'decimal:2',
        'status'             => ProductStatus::class,
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship to Category model.
     * Note: accessed via $product->categoryModel or $product->category_id eager-load.
     * The raw column 'category' (legacy string) is still accessible as $product->category.
     * Use $product->load('productCategory') to load the related Category record.
     */
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function isActive(): bool
    {
        return $this->status === ProductStatus::Active;
    }
}
