<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'personal_contact_id',
        'type',
        'amount',
        'transaction_date',
        'note',
        'payment_method',
        'reference_number',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function personalContact(): BelongsTo
    {
        return $this->belongsTo(PersonalContact::class);
    }
}


