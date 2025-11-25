<?php

namespace App\Models;

use App\Jobs\UpdatePendingTradesWithNewSubCategoryRateJob;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;

/**
 * @property mixed $id
 */
class Giftcard extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'giftcard_category_id',
        'name',
        'image',
        'wait_time',
        'minimum_amount',
        'maximum_amount',
        'currency',
        'high_rate',
        'active',
        'terms',
        'rate',
        'sort_order',
    ];

    protected static $logOnlyDirty = true;

    protected $casts = [
        'high_rate' => 'boolean',
        'active' => 'boolean',
    ];

    public $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => true,
    ];

    protected static function booted()
    {
        // ? RATE EDITOR
        // * We need to prevent a condition where a user updates a single giftcard from the
        // * subcategories page and then the bulk rate editor form is submitted. If this occurs
        // * we risk losing the edit made by the user who updates from the subcategories edit page.
        // * So, we will track the last user to edit the giftcard in a cache value that is being
        // * watched by the bulk rate editor update handler.
        static::updated(function (self $giftcard) {
            if ($giftcard->wasChanged('rate') and Auth::check()) {
                Cache::put('__bulkRateEditor:LastUpdatedBy', Auth::user()->username);
            }
        });

        // ? AUTO-UPDATE PENDING TRADES WITH UPDATED RATES.
        // * When the rate is updated on a giftcard, we want to automatically update all pending trades
        // * that are associated with the giftcard with the new rate value.
        static::updated(function (self $giftcard) {
            if ($giftcard->wasChanged('rate')) {
                UpdatePendingTradesWithNewSubCategoryRateJob::dispatch([
                    'giftCardID' => $giftcard->id,
                    'newRate' => $giftcard->rate,
                ]);
            }
        });
    }

    public function giftcardCategory(): BelongsTo
    {
        return $this->belongsTo(GiftcardCategory::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*']);
    }
}
