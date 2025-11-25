<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AirtimeProduct extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignement
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'product',
        'code',
        'logo',
        'status',
        'minimum_amount',
        'maximum_amount',
    ];
}
