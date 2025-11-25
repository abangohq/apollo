<?php

namespace App\Nova\Filters;

use App\Models\GiftcardCategory;
use DigitalCreative\PillFilter\PillFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Laravel\Nova\Http\Requests\NovaRequest;

class GiftCardCategoryPillFilter extends PillFilter
{
    public static function getSelectedFilterCacheKey(): string
    {
        return implode(':', ['__giftCardCategorySelectedFilters', auth()->user()->username]);
    }

    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        // * Persist these filters so that they are automatically applied when we
        // * navigate back to this page from the edit page or elsewhere.
        // * Unfortunately, this apply method does not get called when the filters are cleared
        // * so we won't be able to reset the cached filters here.
        // * We will add our own custom option to the dropdown that we can listen for here when
        // * the user selects it and clear the filter cache entry.
        if (! empty($value) and $value[0] === 'Clear Filter') {
            Cache::forget(self::getSelectedFilterCacheKey());

            return $query;
        }

        if (! empty($value)) {
            Cache::forever(self::getSelectedFilterCacheKey(), $value);
        }

        $query->when(
            ! empty($value),
            fn (Builder $q) => $q->whereIn('giftcard_category_id', $value)
        );
    }

    public function default()
    {
        return Cache::get(self::getSelectedFilterCacheKey(), []);
    }

    /**
     * Get the filter's available options.
     *
     * @return array
     */
    public function options(NovaRequest $request)
    {
        $options = GiftcardCategory::getForDropdown();
        // * We need a signal to clear the cache filters and since our apply method
        // * doesn't get called when the user selects the "All" dropdown option that Nova
        // * adds, we have to add our own.
        $options->prepend('Clear Filter', 'Clear Active Filter');

        return $options;
    }
}
