<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignUpBonus extends Model
{
    use HasUuids, HasFactory;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'bonus_amount',
        'required_trade_volume',
        'current_trade_volume',
        'status',
        'unlocked_at',
        'claimed_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'bonus_amount' => 'decimal:2',
        'required_trade_volume' => 'decimal:2',
        'current_trade_volume' => 'decimal:2',
        'unlocked_at' => 'datetime',
        'claimed_at' => 'datetime'
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_UNLOCKED = 'unlocked';
    const STATUS_CLAIMED = 'claimed';
    const STATUS_EXPIRED = 'expired';

    /**
     * Get the user that owns the sign-up bonus.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the bonus is eligible to be unlocked
     */
    public function isEligibleForUnlock(): bool
    {
        return $this->status === self::STATUS_PENDING && 
               $this->current_trade_volume >= $this->required_trade_volume;
    }

    /**
     * Check if the bonus can be claimed
     */
    public function canBeClaimed(): bool
    {
        return $this->status === self::STATUS_UNLOCKED;
    }

    /**
     * Update the current trade volume
     */
    public function updateTradeVolume(float $newVolume): void
    {
        $this->update(['current_trade_volume' => $newVolume]);
        
        // Check if bonus should be unlocked
        if ($this->isEligibleForUnlock()) {
            $this->unlock();
        }
    }

    /**
     * Unlock the bonus
     */
    public function unlock(): void
    {
        $this->update([
            'status' => self::STATUS_UNLOCKED,
            'unlocked_at' => now()
        ]);
    }

    /**
     * Mark the bonus as claimed
     */
    public function claim(): void
    {
        $this->update([
            'status' => self::STATUS_CLAIMED,
            'claimed_at' => now()
        ]);
    }

    /**
     * Get the progress percentage
     */
    public function getProgressPercentage(): float
    {
        if ($this->required_trade_volume <= 0) {
            return 0;
        }
        
        return min(100, ($this->current_trade_volume / $this->required_trade_volume) * 100);
    }

    /**
     * Get remaining trade volume needed
     */
    public function getRemainingTradeVolume(): float
    {
        return max(0, $this->required_trade_volume - $this->current_trade_volume);
    }
}