<?php

namespace App\Nova\Actions;

use App\Models\GiftcardCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\MultiSelect;
use Laravel\Nova\Http\Requests\NovaRequest;

class AssignCategory extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Assign Category';

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $team) {
            $existingCategories = $team->categories->pluck('id')->toArray();
            $newCategories = array_diff($fields->categories, $existingCategories);
            $categoriesToRemove = array_diff($existingCategories, $fields->categories);

            foreach ($newCategories as $newCategory) {
                $team->categories()->attach($newCategory, ['id' => Str::uuid()]);
            }

            foreach ($categoriesToRemove as $categoryToRemove) {
                $team->categories()->detach($categoryToRemove);
            }
        }

        return Action::message('Categories successfully assigned to team!');
    }


    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        $selectedCategories = $request->resourceId
        ? Team::findOrFail($request->resourceId)->categories()->pluck('giftcard_category_id')->toArray()
        : [];

        $categories = GiftcardCategory::all()->mapWithKeys(function ($category) {
            return [$category->id => $category->name];
        });

        return [
            MultiSelect::make('Categories')->options($categories)
                ->default($selectedCategories),
        ];
    }
}
