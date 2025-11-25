<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralCode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'reward_amount',
        'active',
        'staff_id'
    ];

    /**
     * The attributes that should be casts.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean', 
    ];

    /**
     * Get the staff for this reward code
     */
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
