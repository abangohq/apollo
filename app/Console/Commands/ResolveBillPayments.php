<?php

namespace App\Console\Commands;

use App\Models\AirtimeTopUp;
use App\Models\BettingTopUp;
use App\Models\CableTopUp;
use App\Models\DataTopUp;
use App\Models\MeterTopUp;
use App\Models\WifiTopUp;
use App\Services\Bills\AirtimeBillService;
use App\Services\Bills\BettingBillService;
use App\Services\Bills\CableBillService;
use App\Services\Bills\DataBillService;
use App\Services\Bills\MeterBillService;
use App\Services\Bills\WifiBillService;
use App\Services\Payment\RedbillerService;
use App\Repositories\TransactionRepository;
use App\Enums\Tranx;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResolveBillPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:resolve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resolve pending bill payments by verifying purchases and initiating reversals if needed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting bill payment resolution...');

        $totalResolved = 0;

        // Resolve Airtime purchases
        $totalResolved += $this->resolveAirtimePurchases();

        // Resolve Data purchases
        $totalResolved += $this->resolveDataPurchases();

        // Resolve Cable purchases
        $totalResolved += $this->resolveCablePurchases();

        // Resolve Betting purchases
        $totalResolved += $this->resolveBettingPurchases();

        // Resolve Meter purchases
        $totalResolved += $this->resolveMeterPurchases();

        // Resolve Wifi purchases
        $totalResolved += $this->resolveWifiPurchases();

        $this->info("Bill payment resolution completed. Total bills processed: {$totalResolved}");

        return self::SUCCESS;
    }

    /**
     * Resolve pending airtime purchases
     */
    private function resolveAirtimePurchases(): int
    {
        $pendingTopUps = AirtimeTopUp::where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(5))
            ->get();

        $count = 0;
        $service = app(AirtimeBillService::class);
        $redbillerService = app(RedbillerService::class);

        foreach ($pendingTopUps as $topUp) {
            try {
                $response = $redbillerService->verifyAirtimePurchase([
                    'reference' => $topUp->reference
                ]);

                if (
                    $response->response !== 200 &&
                    !TransactionRepository::hasReversal($topUp->id, Tranx::AIRTIME)
                ) {
                    $service->reversal($topUp);
                }

                $count++;
            } catch (\Throwable $e) {
                Log::error('Failed to resolve airtime purchase', [
                    'topup_id' => $topUp->id,
                    'reference' => $topUp->reference,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }

    /**
     * Resolve pending data purchases
     */
    private function resolveDataPurchases(): int
    {
        $pendingTopUps = DataTopUp::where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(5))
            ->get();

        $count = 0;
        $service = app(DataBillService::class);
        $redbillerService = app(RedbillerService::class);

        foreach ($pendingTopUps as $topUp) {
            try {
                $response = $redbillerService->verifyDataPurchase([
                    'reference' => $topUp->reference
                ]);

                if (
                    $response->response !== 200 &&
                    !TransactionRepository::hasReversal($topUp->id, Tranx::DATA)
                ) {
                    $service->reversal($topUp);
                }

                $count++;
            } catch (\Throwable $e) {
                Log::error('Failed to resolve data purchase', [
                    'topup_id' => $topUp->id,
                    'reference' => $topUp->reference,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }

    /**
     * Resolve pending cable purchases
     */
    private function resolveCablePurchases(): int
    {
        $pendingTopUps = CableTopUp::where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(5))
            ->get();

        $count = 0;
        $service = app(CableBillService::class);
        $redbillerService = app(RedbillerService::class);

        foreach ($pendingTopUps as $topUp) {
            try {
                $response = $redbillerService->verifyCablePlanPurchase([
                    'reference' => $topUp->reference
                ]);

                if (
                    $response->response !== 200 &&
                    !TransactionRepository::hasReversal($topUp->id, Tranx::CABLE)
                ) {
                    $service->reversal($topUp);
                }

                $count++;
            } catch (\Throwable $e) {
                Log::error('Failed to resolve cable purchase', [
                    'topup_id' => $topUp->id,
                    'reference' => $topUp->reference,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }

    /**
     * Resolve pending betting purchases
     */
    private function resolveBettingPurchases(): int
    {
        $pendingTopUps = BettingTopUp::where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(5))
            ->get();

        $count = 0;
        $service = app(BettingBillService::class);
        $redbillerService = app(RedbillerService::class);

        foreach ($pendingTopUps as $topUp) {
            try {
                $response = $redbillerService->verifyBettingAccountCredit([
                    'reference' => $topUp->reference
                ]);

                if (
                    $response->response !== 200 &&
                    !TransactionRepository::hasReversal($topUp->id, Tranx::BETTING)
                ) {
                    $service->reversal($topUp);
                }

                $count++;
            } catch (\Throwable $e) {
                Log::error('Failed to resolve betting purchase', [
                    'topup_id' => $topUp->id,
                    'reference' => $topUp->reference,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }

    /**
     * Resolve pending meter purchases
     */
    private function resolveMeterPurchases(): int
    {
        $pendingTopUps = MeterTopUp::where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(5))
            ->get();

        $count = 0;
        $service = app(MeterBillService::class);
        $redbillerService = app(RedbillerService::class);

        foreach ($pendingTopUps as $topUp) {
            try {
                $response = $redbillerService->verifyDiscoPurchase([
                    'reference' => $topUp->reference
                ]);

                if (
                    $response->response !== 200 &&
                    !TransactionRepository::hasReversal($topUp->id, Tranx::METER)
                ) {
                    $service->reversal($topUp);

                }

                $count++;
            } catch (\Throwable $e) {
                Log::error('Failed to resolve meter purchase', [
                    'topup_id' => $topUp->id,
                    'reference' => $topUp->reference,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }

    /**
     * Resolve pending wifi purchases
     */
    private function resolveWifiPurchases(): int
    {
        $pendingTopUps = WifiTopUp::where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(5))
            ->get();

        $count = 0;
        $service = app(WifiBillService::class);
        $redbillerService = app(RedbillerService::class);

        foreach ($pendingTopUps as $topUp) {
            try {
                $response = $redbillerService->verifyWifiPurchase([
                    'reference' => $topUp->reference
                ]);

                if (
                    $response->response !== 200 &&
                    !TransactionRepository::hasReversal($topUp->id, Tranx::WIFI)
                ) {
                    $service->reversal($topUp);

                }

                $count++;
            } catch (\Throwable $e) {
                Log::error('Failed to resolve wifi purchase', [
                    'topup_id' => $topUp->id,
                    'reference' => $topUp->reference,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }
}
