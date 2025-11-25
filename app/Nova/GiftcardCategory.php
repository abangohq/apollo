<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Outl1ne\NovaSortable\Traits\HasSortableRows;

/**
 * @property mixed $image
 */
class GiftcardCategory extends Resource
{
    use HasSortableRows;

    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\GiftcardCategory>
     */
    public static $model = \App\Models\GiftcardCategory::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

    public static function authorizedToCreate(Request $request)
    {
        if ($request->viaResource) {
            return false;
        }
        return parent::authorizedToCreate($request);
    }

    public function authorizeToReplicate(Request $request)
    {
        return false;
    }

    public function authorizedToReplicate(Request $request)
    {
        return false;
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()
                ->displayUsing(function ($value) {
                    return Str::limit($value, '8', '..');
                })->sortable(),

            Text::make('Name'),

            Boolean::make('Active')
                ->trueValue(true)
                ->falseValue(false),

            Image::make('Logo Image')
                ->thumbnail(fn ($value) => $value)
                ->preview(fn ($value) => $value)
                ->store(function (NovaRequest $request, $model) {
                    return [
                        'logo_image' => cloudinary()->upload($request->logo_image->getRealPath(), [
                            'folder' => 'koyn/giftcard_categories',
                        ])->getSecurePath(),
                    ];
                })
                ->rounded()
                ->disableDownload(),

            Image::make('Preview Image')
                ->thumbnail(fn ($value) => $value)
                ->preview(fn ($value) => $value)
                ->store(function (NovaRequest $request, $model) {
                    return [
                        'preview_image' => cloudinary()->upload($request->preview_image->getRealPath(), [
                            'folder' => 'koyn/giftcard_categories',
                        ])->getSecurePath(),
                    ];
                })
                ->rounded()
                ->disableDownload(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }

    /**
     * Register a callback to be called after the resource is created.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    // public static function afterUpdate(NovaRequest $request, Model $model)
    // {
    //     if ($model->wasChanged('logo_image')) {
    //         Log::info('Logo Image was changed', ['logo_image' => $model->file('logo_image')->getRealPath()]);
    //         $model->update([
    //             'logo_image' => cloudinary()->upload($model->logo_image->getRealPath(), [
    //                 'folder' => 'koyn/giftcard_categories'
    //                 ])->getSecurePath()
    //         ]);
    //     }

    //     if ($model->wasChanged('preview_image')) {
    //         $model->update([
    //             'preview_image' => cloudinary()->upload($model->preview_image->getRealPath(), [
    //                 'folder' => 'koyn/giftcard_categories'
    //                 ])->getSecurePath()
    //         ]);
    //     }
    // }
}
