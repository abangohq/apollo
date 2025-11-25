<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TradeImage extends Model
{
    use HasUuids, SoftDeletes;

    protected $hidden = [
        'trade_id',
        'id',
        'deleted_at',
    ];

    protected static $logOnlyDirty = true;

    protected $fillable = ['image_url', 'trade_id'];

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }
}
