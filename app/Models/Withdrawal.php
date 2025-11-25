<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable
     * 
     * @var array<string, string
     */
    protected $fillable = [
        'user_id',
        'amount',
        'reason',
        'status',
        'reference',
        'bank_code',
        'bank_id',
        'bank_name',
        'account_name',
        'account_number',
        'bank_logo',
        'provider_reference',
        'provider_status',
        'settled_by',
        'rejection_id',
        'platform',
        'channel'
    ];

    /**
     * Get the withdrawal's transaction.
     */
    public function transaction()
    {
        return $this->morphOne(WalletTransaction::class, 'transaction');
    }

    /**
     * Get the bank for this withdrawal
     */
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function account()
    {
        return $this->belongsTo(BankAccount::class, 'user_id');
    }

    public function rejectionReason()
    {
        return $this->belongsTo('App\Models\RejectionReason', 'rejection_reason');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    /**
     * Query scope to find withdrawal by reference
     */
    public function scopeFindByRef(Builder $query, string $reference)
    {
        return $query->where('withdrawals.reference', $reference);
    }
}
