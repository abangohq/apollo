<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletHistory extends Model
{
    use HasFactory;

    protected $casts = [
        'previous_balance' => 'float',
        'current_balance' => 'float'
    ];

    protected $fillable = ['wallet_id', 'transaction_id', 'amount', 'type', 'previous_balance', 'current_balance'];
}
