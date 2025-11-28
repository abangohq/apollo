<?php

namespace App\Models;

use App\Traits\TraitBelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @mixin IdeHelperMediaViewLog
 */
class MediaViewLog extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'context' => 'array',
        'viewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function media(): MorphTo
    {
        return $this->morphTo();
    }
}
