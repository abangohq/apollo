<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserData extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'country_code',
        'timezone',
        'longitude',
        'latitude',
        'ip_address',
        'city',
        'region',
        'region_name',
        'user_agent'
    ];

}
