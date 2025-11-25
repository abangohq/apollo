<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoRate extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable
     * 
     * @var array<string, string>
     */
    protected $fillable = [
        'rate_range',
        'rate',
        'fee',
        'range_start',
        'range_end',
        'is_published'
    ];

    /**
     * The attributes that should be casts.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'is_published' => 'boolean',
        'range_end' => 'float',
        'range_start' => 'float',
        'rate' => 'float',
        'fee' => 'float'
    ];

    /**
     * Scope to fetch only published
     */
    public function scopePublished(Builder $query)
    {
        return $query->where('is_published', true);
    }
}
