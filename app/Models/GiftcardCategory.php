<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;

/**
 * @method static where()
 * @method static findOrFail($giftcard_category_id)
 *
 * @property mixed $giftcard_id
 * @property mixed $id
 */
class GiftcardCategory extends Model
{
    use HasUuids, SoftDeletes;

    public const CACHE_KEY_DROPDOWN = 'giftcard-categories-dropdown';

    protected $fillable = ['name', 'active', 'logo_image', 'preview_image'];

    protected static $logOnlyDirty = true;

    protected $casts = [
        'active' => 'boolean',
    ];

    public $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => true,
    ];

    protected static function booted()
    {
        static::saved(fn () => Cache::forget(static::CACHE_KEY_DROPDOWN));
    }

    public function giftcards(): HasMany
    {
        return $this->hasMany(Giftcard::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*']);
    }

    public static function getForDropdown(): Collection
    {
        return Cache::remember(
            key: static::CACHE_KEY_DROPDOWN,
            ttl: now()->addMinutes(10),
            callback: fn () => static::query()
                ->where('active', true)
                ->withCount('giftcards')
                ->orderBy('sort_order')
                ->orderByDesc('giftcards_count')
                ->get(['id', 'name', DB::raw('COUNT(giftcards.id) as giftcards_count')])
                ->map(fn (GiftcardCategory $giftcardCategory) => [
                    'id' => $giftcardCategory->id,
                    'name' => $giftcardCategory->name." ({$giftcardCategory->giftcards_count})",
                ])
                ->pluck('id', 'name')
        );
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class);
    }
}
