<?php

namespace App\Nova\Actions;

use App\Notifications\User\AccountSuspensionNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class BanUser extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Ban User';

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $user) {
            $user->update(["status" => 'inactive']);
            $user->notify(new AccountSuspensionNotification('inactive'));
        }

        return Action::message('User disabled successfully!');
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Text::make('Comment', 'comment'),
            DateTime::make('Expires At', 'expired_at'),
        ];
    }
}
