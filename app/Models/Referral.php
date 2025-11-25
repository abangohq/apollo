<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasUuids, HasFactory;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'code',
        'reward_amount'
    ];

    /**
     * Get the user for this referral
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the referred user for this referral
     */
    public function referredBy()
    {
        return $this->belongsTo(User::class, 'code', 'referral_code');
    }
}
