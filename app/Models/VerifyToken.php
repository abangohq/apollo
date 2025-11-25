<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerifyToken extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable
     * 
     * @var array<string, string>
     */
    protected $fillable = [
        'user_id',
        'token',
        'email',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'token' => 'hashed',
    ];

    /**
     * Scope the to non expired token
     */
    public function scopeTokenMail(Builder $query, $email)
    {
        return $query->whereEmail($email)->where('expires_at', '>', now());
    }

    /**
     * Scope the to non expired token
     */
    public function scopeValid(Builder $query)
    {
        return $query->where('expires_at', '>', now());
    }
}
