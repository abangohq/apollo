<?php

namespace App\Nova\Actions;

use App\Models\User;
use App\Notifications\SendPushNotification as SendFirebasePushNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class SendPushNotification extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $users = User::with('devices')->has('devices')->get();

        foreach ($users as $user) {
            $user->notify(new SendFirebasePushNotification(
                $fields->title,
                $fields->message
            ));
        }
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Text::make('Title', 'title')
                ->rules('required', 'string')
                ->help('The title of the push notification'),
            Textarea::make('Message', 'message')
                ->rules('required', 'string')
                ->rows(2)
                ->help('The message to be sent in the push notification'),
        ];
    }
}
