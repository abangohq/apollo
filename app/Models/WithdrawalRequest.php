<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'amount',
        'account_balance',
        'reason',
        'status',
        'transaction_ref',
        'bank',
        'settled_by',
        'rejection_reason'
    ];

    // protected $casts = [
    //     'rejection_reason' => 'array'
    // ];

    // protected $appends = ['rejection_reason'];

    // public function getRejectionReasonAttribute($value){
    //     return "{$value->reason}";
    // }

    public function user() {
        return $this->belongsTo(User::class,'user_id');
    }

    public function account() {
        return $this->belongsTo(BankAccount::class,'user_id');
    }

    public function rejectionReason() {
        return $this->belongsTo(RejectionReason::class,'rejection_reason');
    }

}
