<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assigneable.
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'ability',
    ];

    /**
     * Get the user for the permissions
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
