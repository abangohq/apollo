<?php

namespace App\Providers;

use App\Nova\AdminUser;
use App\Nova\ApprovedTrade;
use App\Nova\BannedUser;
use App\Nova\GiftcardCategory;
use App\Nova\Kyc;
use App\Nova\PendingTrade;
use App\Nova\RejectedTrade;
use App\Nova\Subcategory;
use App\Nova\Trade;
use App\Nova\User;
use App\Nova\Withdrawal;
use Bolechen\NovaActivitylog\NovaActivitylog;
use Bolechen\NovaActivitylog\Resources\Activitylog;
use Faradele\GctnGiftcardRatesEditor\GctnGiftcardRatesEditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Dashboards\Main;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Nova::serving(function (\Laravel\Nova\Events\ServingNova $event) {
            Nova::script('apollo-polyfills', public_path('js/nova-polyfills.js'));
        });

        Nova::withBreadcrumbs();
        Nova::withoutNotificationCenter();

        // * define policies for accessing the bulk rate editor
        Gate::define(
            'giftCardRateEditorView',
            fn ($user) => $user->user_type === 'admin' || $user->user_type === 'staff'
        );
        Gate::define(
            'giftCardRateEditorUpdate',
            fn ($user) => $user->user_type === 'admin' || $user->user_type === 'staff'
        );

        // * customise uri path
        GctnGiftcardRatesEditor::$customUriPath = 'koyn-bulk-rate-editor';

        Nova::mainMenu(function (Request $request) {
            return [
                MenuSection::dashboard(Main::class)
                    ->icon('chart-bar'),
                MenuSection::make('Giftcards', [
                    MenuItem::resource(GiftcardCategory::class)
                        ->canSee(fn ($request) => $request->user()->user_type === 'admin' || $request->user()->user_type === 'staff'),
                    MenuItem::resource(Subcategory::class),

                    MenuSection::make('Bulk Rates Editor')
                        ->path(GctnGiftcardRatesEditor::$customUriPath)
                        ->icon('clipboard-list')
                        ->canSee(fn ($request) => $request->user()->user_type === 'admin' || $request->user()->user_type === 'staff'
                        ),
                ])
                    ->icon('gift')
                    ->collapsable(),

                MenuSection::make('Trades', [
                    MenuItem::resource(ApprovedTrade::class)
                        ->canSee(fn ($request) => $request->user()->user_type === 'admin'),
                    MenuItem::resource(PendingTrade::class)
                        ->canSee(fn ($request) => $request->user()->user_type === 'admin' || $request->user()->user_type === 'staff'),
                    MenuItem::resource(Trade::class)
                        ->canSee(fn ($request) => $request->user()->user_type === 'admin' || $request->user()->user_type === 'staff'),
                    MenuItem::resource(RejectedTrade::class)
                        ->canSee(fn ($request) => $request->user()->user_type === 'admin' || $request->user()->user_type === 'staff'),
                ])
                    ->icon('briefcase')
                    ->collapsable(),
                MenuSection::make('IAM', [
                    MenuItem::resource(User::class),
                    MenuItem::resource(BannedUser::class),
                    MenuSection::resource(AdminUser::class)->icon('user')
                    ->canSee(fn ($request) => $request->user()->user_type === 'admin'),
                    // MenuItem::resource(Team::class)->canSee(fn ($request) => $request->user()->user_type === 'admin'),
                    MenuItem::resource(Kyc::class),
                ])
                    ->icon('user')
                    ->collapsable(),
                MenuSection::resource(Withdrawal::class)
                    ->icon('cash')
                    ->canSee(fn ($request) => $request->user()->user_type === 'admin' || $request->user()->user_type === 'staff'),
                    // ->withBadge(badgeCallback: fn () => \App\Models\Withdrawal::where([
                    //     ['status', '=', 'pending']
                    // ])->count(), 'danger'),

            ];
        });
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
                ->withAuthenticationRoutes()
                ->withPasswordResetRoutes()
                ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return $user->type === 'admin' || $user->type === 'staff' || $user->type === 'manager';
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [
            new Main(),
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [
            // NovaActivitylog::make()->canSee(function ($request) {
            //     return $request->user()->type === 'admin';
            // }),
            GctnGiftcardRatesEditor::make()
                ->canSee(fn ($request) => $request->user()->user_type === 'admin' || $request->user()->user_type === 'staff'),
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
