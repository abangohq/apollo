<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reconciliation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assigneable.
     * 
     * @var array<string, string>
     */
    protected $fillable = [
        'reference',
        'user_id',
        'staff_id',
        'origin_tranx_id', // this is meant for the original transaction 
        'entry',
        'amount',
        'reason',
        'status'
    ];

    /**
     * Get the transactable transaction.
     */
    public function transaction()
    {
        return $this->morphOne(WalletTransaction::class, 'transaction');
    }

    /**
     * Get the user for this reconciliation
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the staff for this reconciliation
     */
    public function staff()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transaction for this reconciliation
     */
    public function originTransaction()
    {
        return $this->belongsTo(WalletTransaction::class, 'origin_tranx_id', 'id');
    }
}
