<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoWithdrawal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable
     * 
     * @var array<string, string>
     */
    protected $fillable = [
        'staff_id',
        'platform',
        'recipient_address',
        'amount',
        'request_status',
        'request_id',
    ];

    /**
     * Get the staff user for this action
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
