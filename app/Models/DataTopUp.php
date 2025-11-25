<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataTopUp extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assigneable.
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        "user_id",
        "product",
        "name",
        "code",
        "phone_no",
        "amount_requested",
        "amount_paid",
        "discount_percentage",
        "discount_value",
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
        return $this->hasOne(IspProvider::class, 'product', 'product')->value('logo');
    }

    /**
     * Get the topup transaction.
     */
    public function transaction()
    {
        return $this->morphOne(WalletTransaction::class, 'transaction');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Query scope to find topup by reference
     */
    public function scopeFindByRef(Builder $query, string $reference)
    {
        return $query->where('data_top_ups.reference', $reference);
    }
}
