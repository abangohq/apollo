<?php

namespace App\Nova;

use App\Nova\Actions\CreditWallet;
use App\Nova\Actions\DebitWallet;
use App\Nova\Actions\UnbanUser;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class BannedUser extends User
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\User>
     */
    public static $model = \App\Models\User::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'email', 'username', 'phone',
    ];

    public static function indexQuery(NovaRequest $request, $query)
    {
        return parent::indexQuery($request, $query)->where('status', 'inactive');
    }

    public static function authorizedToCreate(Request $request)
    {
        return false;
    }

    public function authorizedToDelete(Request $request)
    {
        return false;
    }

    public function authorizeToReplicate(Request $request)
    {
        return false;
    }

    public function authorizedToReplicate(Request $request)
    {
        return false;
    }

    // Inherit fields, cards, filters, lenses from User

    /**
     * Get the actions available for the resource.
     *
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [
            (new UnbanUser)->confirmText('Are you sure you want to unban this user?')
                ->confirmButtonText('Yes')
                ->cancelButtonText('No'),
            (new CreditWallet)->showInline()
                ->confirmText('Are you sure you want to credit this user?')
                ->confirmButtonText('Yes')
                ->cancelButtonText('No')
                ->canSee(fn () => $request->user()->type === 'super_admin' ?? false),
            (new DebitWallet)
                ->showInline()
                ->confirmText('Are you sure you want to debit this user?')
                ->confirmButtonText('Yes')
                ->cancelButtonText('No'),
        ];
    }
}
