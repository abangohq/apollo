<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoAsset extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass asignable
     *
     * @var array<string, string>
     */
    protected $fillable = [
        'name',
        'symbol',
        'about',
        'status',
        'latest_quote',
        'percent_change_1hr',
        'logo',
        'percent_change_24hr',
        'price_graph_data_points',
        'market_cap',
        'total_supply',
        'circulating_supply',
        'volume',
        'price',
        'term',
        'last_updated'
    ];

    /**
     * The attributes that should be casts
     *
     * @var array<string, string>
     */
    protected $casts = [
        "price_graph_data_points" => "json"
    ];

    /**
     * Get the wallet for this assets
     */
    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Get the exchange rates to use for conversion
     */
    public function conversionRates()
    {
        return $this->hasMany(ConversionRate::class, 'crypto_id', 'id');
    }

    /**
     * scope query for active assets
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }
}
