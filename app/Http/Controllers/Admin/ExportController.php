<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DataExport;
use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Models\CryptoTransaction;
use App\Models\AirtimeTopUp;
use App\Models\DataTopUp;
use App\Models\CableTopUp;
use App\Models\BettingTopUp;
use App\Models\MeterTopUp;
use App\Models\WifiTopUp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use League\Csv\Writer;
use Maatwebsite\Excel\Facades\Excel;


class ExportController extends Controller
{
    /**
     * Export data based on date range
     *
     * @param Request $request
     */
    public function export(Request $request)
    {
        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:d/m/Y',
            'end_date' => 'required|date_format:d/m/Y|after_or_equal:start_date',
            'model' => 'required|in:crypto_transactions,airtime_top_ups,data_top_ups,cable_top_ups,betting_top_ups,meter_top_ups,wifi_top_ups,users,withdrawals',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Parse dates
        $startDate = $request->date('start_date', 'd/m/Y')->startOfDay();
        $endDate = $request->date('end_date', 'd/m/Y')->endOfDay();

        // Handle users export differently since it uses a different export class
        if ($request->model === 'users') {
            return $this->exportUsers($startDate, $endDate);
        }

        // Get data based on model
        $data = $this->getDataByModel(
            $request->model,
            $startDate,
            $endDate
        );

        return $this->exportCsv($data, $request->model);
    }

    /**
     * Get data based on model and date range
     *
     * @param string $model
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getDataByModel($model, $startDate, $endDate)
    {
        switch ($model) {
            case 'crypto_transactions':
                return CryptoTransaction::whereBetween('created_at', [$startDate, $endDate])
                    ->get();
            case 'airtime_top_ups':
                return AirtimeTopUp::whereBetween('created_at', [$startDate, $endDate])
                    ->get();
            case 'data_top_ups':
                return DataTopUp::whereBetween('created_at', [$startDate, $endDate])
                    ->get();
            case 'cable_top_ups':
                return CableTopUp::whereBetween('created_at', [$startDate, $endDate])
                    ->get();
            case 'betting_top_ups':
                return BettingTopUp::whereBetween('created_at', [$startDate, $endDate])
                    ->get();
            case 'meter_top_ups':
                return MeterTopUp::whereBetween('created_at', [$startDate, $endDate])
                    ->get();
            case 'wifi_top_ups':
                return WifiTopUp::whereBetween('created_at', [$startDate, $endDate])
                    ->get();
            case 'withdrawals':
                return \App\Models\Withdrawal::with(['user', 'staff', 'bank'])
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();
            default:
                return collect([]);
        }
    }

    /**
     * Export data as CSV
     *
     * @param \Illuminate\Database\Eloquent\Collection $data
     * @param string $modelName
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    private function exportCsv($data, $modelName)
    {
        $export = new DataExport($data, $this->getHeadersForModel($modelName));
        $filename = $modelName . '_' . date('Y-m-d_His') . '.csv';

        return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
    }

    /**
     * Export data as Excel
     *
     * @param \Illuminate\Database\Eloquent\Collection $data
     * @param string $modelName
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    private function exportExcel($data, $modelName)
    {
        $export = new DataExport($data, $this->getHeadersForModel($modelName));
        $filename = $modelName . '_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Export data as JSON
     *
     * @param \Illuminate\Database\Eloquent\Collection $data
     * @return \Illuminate\Http\JsonResponse
     */
    private function exportJson($data)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
            'exported_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get headers for given model
     *
     * @param string $modelName
     * @return array
     */
    private function getHeadersForModel($modelName)
    {
        switch ($modelName) {
            case 'crypto_transactions':
                return ['address','reference','crypto',
        'crypto_amount','asset_price','conversion_rate',
        'usd_value','payout_amount',
        'confirmations','status','transaction_hash',
        'platform','address','created_at'];
            case 'airtime_top_ups':
                return ['user_id','product','phone_no','amount_requested',
        'amount_paid','discount_percentage','discount_value',
        'reference','status','provider_status','created_at','updated_at'];
            case 'data_top_ups':
                return ['user_id','product','name','code','phone_no',
        'amount_requested','amount_paid','discount_percentage',
        'discount_value','reference','status','provider_status',
        'created_at','updated_at'];
            case 'cable_top_ups':
                return ['user_id','product','name','code','smart_card_no',
        'customer_name','phone_no','amount_requested','amount_paid',
        'discount_percentage','discount_value','reference','status',
        'provider_status','created_at','updated_at'];
            case 'betting_top_ups':
                return ['user_id','product','phone_no','customer_id',
        'amount','charge','profile','reference','status',
        'provider_status','created_at','updated_at'];
            case 'meter_top_ups':
                return ['user_id','product','meter_no','meter_type',
        'customer_name','phone_no','amount_requested','amount_paid',
        'discount_percentage','discount_value','token','units',
        'reference','status','provider_status','created_at','updated_at'];
            case 'wifi_top_ups':
                return ['user_id','product','name','code','device_no',
        'amount_requested','amount_paid','discount_percentage',
        'discount_value','device_number','reference','status',
        'provider_status','created_at','updated_at'];
            case 'withdrawals':
                return [
                    'user_id','user.name','user.email','amount',
                    'reason','status','reference','account_name',
                    'account_number','bank_code','bank_name','provider_reference',
                    'provider_status','settled_by','staff.name','rejection_id',
                    'channel','platform','created_at','updated_at'
                ];
            default:
                return [];
        }
    }

    /**
     * Export users data
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    private function exportUsers($startDate, $endDate)
    {
        $export = new UsersExport($startDate, $endDate);
        $filename = 'users_' . date('Y-m-d_His') . '.csv';

        return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
    }
}
