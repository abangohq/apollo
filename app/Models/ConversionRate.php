<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversionRate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable
     * 
     * @var array<string, string>
     */
    protected $fillable = [
        'crypto_id',
        'rate_range',
        'rate',
        'range_start',
        'range_end',
        'is_published'
    ];

    /**
     * Get the asset for this rate
     */
    public function asset()
    {
        return $this->belongsTo(CryptoAsset::class, 'crypto_id', 'id');
    }
}
