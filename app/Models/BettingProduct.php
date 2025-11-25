<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BettingProduct extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'product',
        'logo',
        'minimum_amount',
        'maximum_amount',
        'status'
    ];

    /**
     * Scope to retrieve only active providers
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }
}
