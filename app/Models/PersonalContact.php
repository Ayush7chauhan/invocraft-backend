<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonalContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'mobile',
        'email',
        'address',
        'relationship',
        'opening_balance',
        'status',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PersonalTransaction::class);
    }

    public function getBalanceAttribute(): float
    {
        $given = $this->transactions()->where('type', 'given')->sum('amount');
        $received = $this->transactions()->where('type', 'received')->sum('amount');
        // Positive balance = they owe you, Negative = you owe them
        return ($this->opening_balance + $given) - $received;
    }

    public function getYouOweAttribute(): float
    {
        $balance = $this->balance;
        return $balance < 0 ? abs($balance) : 0;
    }

    public function getTheyOweAttribute(): float
    {
        $balance = $this->balance;
        return $balance > 0 ? $balance : 0;
    }
}


