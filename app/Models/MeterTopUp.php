<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeterTopUp extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "product",
        "meter_no",
        "meter_type",
        "customer_name",
        "phone_no",
        "amount_requested",
        "amount_paid",
        "discount_percentage",
        "discount_value",
        "token",
        "units",
        "reference",
        "status",
        'provider_status'
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
        return $this->hasOne(MeterProduct::class, 'name', 'product')->value('logo');
    }

    /**
     * Get the topup transaction.
     */
    public function transaction()
    {
        return $this->morphOne(WalletTransaction::class, 'transaction');
    }

    /**
     * Get the that owns the top transaction.
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
        return $query->where('meter_top_ups.reference', $reference);
    }
}
