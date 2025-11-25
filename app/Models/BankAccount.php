<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'bank_id',
        'bank_code',
        'account_name',
        'account_number',
        'is_primary',
        'image',
        'bank_name'
    ];

    protected $casts = [
        'is_primary' => 'boolean'
    ];

    public function customer()
    {
        return $this->belongsTo('App\Models\User', 'customer_id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Query scope for primary account
     */
    public function scopePrimaryAcct(Builder $query)
    {
        return $query->where('is_primary', true);
    }
}
