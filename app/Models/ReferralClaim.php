<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'referral_id',
        'amount_claimed',
        'claimed_at'
    ];

    protected $casts = [
        'amount_claimed' => 'decimal:2',
        'claimed_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }
}
