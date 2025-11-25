<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Spatie\Activitylog\LogOptions;

/**
 * @method static create(array $array)
 * @method static where(string $string, $id)
 *
 * @property mixed $user_id
 * @property mixed $amount
 * @property mixed $updated_at
 * @property mixed $user
 * @property mixed $giftcardCategory
 */
class Trade extends Model
{
    use HasUuids;

    protected static $logOnlyDirty = true;

    protected $fillable = [
        'user_id',
        'giftcard_id',
        'transaction_id',
        'rate',
        'amount',
        'units',
        'currency',
        'e_code',
        'payout_method',
        'status',
        'opened_at',
        'duplicate_images_info',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'duplicate_images_info' => 'array',
    ];

    /**
     * The accessors to append to the model's array.
     *
     * @var array
     */
    protected $appends = ['logo'];

    /**
     * Get the logo attribute
     */
    public function getLogoAttribute()
    {
        return $this->loadMissing('giftcard')->loadMissing('giftcard.giftcardCategory')->giftcard->giftcardCategory->logo_image ?? null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(TradeImage::class);
    }

    public function giftcard(): BelongsTo
    {
        return $this->belongsTo(Giftcard::class);
    }

    public function giftcardCategory(): HasOneThrough
    {
        return $this->hasOneThrough(
            GiftcardCategory::class,
            Giftcard::class,
            'id',
            'id',
            'giftcard_id',
            'giftcard_category_id'
        );
    }

    public function approvals(): HasOne
    {
        return $this->HasOne(TradeApproval::class);
    }

    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Transaction::class,
            TradeTransaction::class,
            'trade_id',
            'id',
            'id',
            'transaction_id'
        );
    }

    public function mediaViewLogs(): HasMany
    {
        return $this->hasMany(MediaViewLog::class, 'attachable_id', 'id')
            ->where('attachable_type', static::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*']);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function hasDuplicateImages(): bool
    {
        return ! empty($this->duplicate_images_info) or ! empty($this->has_duplicate_images);
    }
}
