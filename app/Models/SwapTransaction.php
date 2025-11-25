<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SwapTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'reference',
        'swap_tranx_id',
        'swap_type',
        'status',
        'currency_from',
        'currency_to',
        'payin_address',
        'payout_address',
        'refund_address',
        'is_app_address',
        'amount_expected_from',
        'amount_expected_to',
        'pay_till',
        'network_fee',
        'track_url'
    ];

    /**
     * 
     * The attributes that should be casts
     */
    protected $casts = [
        'is_app_address' => 'boolean',
    ];

    /**
     * Get the user that owns this swap transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $appends = ['currency_from_logo', 'currency_to_logo'];

    public function getCurrencyFromLogoAttribute()
    {
        $symbol = strtolower($this->currency_from);
        return "https://cdn.changelly.com/icons/{$symbol}.png";
    }

    public function getCurrencyToLogoAttribute()
    {
        $symbol = strtolower($this->currency_to);
        return "https://cdn.changelly.com/icons/{$symbol}.png";
    }
}
