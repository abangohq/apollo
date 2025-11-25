<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'balance',
        'wallet_currency',
        'is_flagged'
    ];

    /**
     * The attributes that should be casts.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'is_flagged' => 'boolean',
    ];

    /**
     * Get the wallet user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
