<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CableTopUp extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "product",
        "name",
        "code",
        "smart_card_no",
        "customer_name",
        "phone_no",
        "amount_requested",
        "amount_paid",
        "discount_percentage",
        "discount_value",
        "reference",
        "status",
        "provider_status"
    ];

    /**
     * The accessors to append to the model's array.
     *
     * @var array
     */
    protected $appends = ['logo'];

    /**
     * Get the logo attribute
     */
    public function getLogoAttribute()
    {
        return $this->hasOne(CableProvider::class, 'product', 'product')->value('logo');
    }

    /**
     * Get the topup transaction.
     */
    public function transaction()
    {
        return $this->morphOne(WalletTransaction::class, 'transaction');
    }

    /**
     * Get the user for the cable transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Query scope to find topup by reference
     */
    public function scopeFindByRef(Builder $query, string $reference)
    {
        return $query->where('cable_top_ups.reference', $reference);
    }
}
