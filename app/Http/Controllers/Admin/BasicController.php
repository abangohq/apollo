<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyBook;
use App\Models\User;
use App\Services\Payment\RedbillerService;
use App\Services\Support\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BasicController extends Controller
{
    public function __construct(public AnalyticsService $analytics)
    {
        //
    }

    /**
     * Get the dashboard metrics overview
     */
    public function overview(Request $request, RedbillerService $redbiller)
    {
        $scope = $request->get('scope');
        $overview = $this->analytics->overview($scope);
        return $this->success($overview);
    }

    /**
     * Get redbiller balance
     */
    public function billerStatus(Request $request, RedbillerService $redbiller)
    {
        $details = $redbiller->verifyAllTransactions($request->reference, $request->type);
        return $this->success($details);
    }

    /**
     * Check whether Redbiller balance is below the configured threshold.
     * Returns: { low: bool|null, available: float|null, threshold: int }
     */
    public function redbillerLowBalance(Request $request, RedbillerService $redbiller)
    {
        $response = $redbiller->balance();
        $available = data_get($response, 'details.available');
        $threshold = (int) config('services.redbiller.low_balance_threshold', 0);

        $low = is_numeric($available) ? ((float) $available < $threshold) : null;

        return $this->success([
            'low' => $low
        ]);
    }

    public function heardAboutUs(){
        $mainCategories = [
            'Twitter',
            'Instagram',
            'Google',
            'From a Friend',
            'Snapchat',
            'TikTok'
        ];

        $rawQuery = "CASE
            WHEN heard_about_us IN ('" . implode("','", $mainCategories) . "')
            THEN heard_about_us
            ELSE 'Other'
        END";

        $details = User::select(
            DB::raw($rawQuery . ' as heard_about_us'),
            DB::raw('COUNT(*) as total')
        )
        ->groupBy(DB::raw($rawQuery))
        ->get()
        ->makeHidden((new User)->getAppends());
        return $this->success($details);
    }

    public function bookClosures(Request $request)
    {
        $dailyBooks = DailyBook::paginate(25);
        return $this->success($dailyBooks);
    }

}
