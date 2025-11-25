<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable
     * 
     * @var array<string, string
     */
    protected $fillable = [
        'user_id',
        'reference',
        'transaction_type',
        'transaction_id',
        'entry',
        'status',
        'narration',
        'currency',
        'amount',
        'charge',
        'total_amount',
        'wallet_balance',
        'is_reversal',
        'mode'
    ];

    /**
     * The attributes that should be casts.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'charge' => 'float',
        'total_amount' => 'float',
        'wallet_balance' => 'float',
        'is_reversal' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization
     * 
     * @var array<string>
     */
    protected $hidden = [
        // 'wallet_balance'
    ];
    
    /**
     * Get the parent transactable model
     */
    public function transactable()
    {
        return $this->morphTo(__FUNCTION__, 'transaction_type', 'transaction_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function history()
    {
        return $this->hasOne(WalletHistory::class);
    }

    /**
     * Scope to retrieve successful transactions
     */
    public function scopeSuccessful(Builder $transactions)
    {
        return $transactions->whereStatus(Status::SUCCESSFUL);
    }

    /**
     * Scope to retrieve failed transactions
     */
    public function scopeFailed(Builder $transactions)
    {
        return $transactions->whereStatus(Status::FAILED);
    }
}
