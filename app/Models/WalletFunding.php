<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletFunding extends Model
{
    use HasFactory;

    protected $casts = [
        'initiated_at' => 'datetime'
    ];
    protected $fillable = [
        'business',
        'virtual_account',
        'source_currency',
        'destination_currency',
        'source_amount',
        'destination_amount',
        'amount_received',
        'fee',
        'customer_name',
        'settlement_destination',
        'status',
        'initiated_at',
        'reference'
    ];
}
