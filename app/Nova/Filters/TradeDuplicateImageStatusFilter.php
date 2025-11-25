<?php

namespace App\Nova\Filters;

use Illuminate\Database\Eloquent\Builder;
use Laravel\Nova\Filters\BooleanFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

class TradeDuplicateImageStatusFilter extends BooleanFilter
{
    public $name = "Duplicate Image Flag";

    /**
     * Apply the filter to the given query.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        if ($value['has-duplicates'] and $value['no-duplicates']) {
            return $query;
        }

        return $query
            ->when(
                value: $value['has-duplicates'] ?? false,
                callback: fn (Builder $q) => $q->whereNotNull('duplicate_images_info'),
            )
            ->when(
                value: $value['no-duplicates'] ?? false,
                callback: fn (Builder $q) => $q->whereNull('duplicate_images_info'),
            );
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        return [
            "Has Duplicate Images" => 'has-duplicates',
            "No Duplicate Images" => 'no-duplicates',
        ];
    }
}
