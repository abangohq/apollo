<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'address',
        'reference',
        'crypto',
        'crypto_amount',
        'asset_price',
        'conversion_rate',
        'usd_value',
        'fee',
        'payout_amount',
        'payout_currency',
        'confirmations',
        'status',
        'transaction_hash',
        'transaction_link',
        'platform',
        'address'
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
        return $this->hasOne(CryptoAsset::class, 'symbol', 'crypto')->value('logo');
    }

    /**
     * Get the topup transaction.
     */
    public function transaction()
    {
        return $this->morphOne(WalletTransaction::class, 'transaction');
    }

    /**
     * Get the crypto transaction user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
