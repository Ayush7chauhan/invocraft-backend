<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'mobile_number',
        'name',
        'email',
        'shop_name',
        'owner_name',
        'shop_address',
        'business_type',
        'gst_number',
        'password',
        'is_registration_complete',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'         => 'datetime',
            'password'                  => 'hashed',
            'is_registration_complete'  => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function parties(): HasMany
    {
        return $this->hasMany(Party::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function setting(): HasOne
    {
        return $this->hasOne(Setting::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Get or create the shop settings for this user.
     */
    public function getOrCreateSetting(): Setting
    {
        return $this->setting ?? Setting::create(['user_id' => $this->id]);
    }
}
