<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeApproval extends Model
{
    use HasUuids;

    protected $fillable = [
        'assigned_to',
        'status',
        'remark',
    ];

    protected static $logOnlyDirty = true;

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
