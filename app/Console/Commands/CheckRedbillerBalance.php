<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendRedbillerBalanceAlert;
use App\Services\Payment\RedbillerService;

class CheckRedbillerBalance extends Command
{
    protected $signature = 'redbiller:check-balance';
    protected $description = 'Check Redbiller balance and send alert if low';

    public function handle()
    {
        $redbiller = app(RedbillerService::class);
        $balanceResponse = $redbiller->balance();

        if ($balanceResponse && isset($balanceResponse->details) && $balanceResponse->details->available < config('services.redbiller.low_balance_threshold')) {
            SendRedbillerBalanceAlert::dispatch($balanceResponse->details->available);
            $this->info('Low balance alert sent.');
        } elseif ($balanceResponse) {
            $this->info('Balance is sufficient.');
        } else {
            $this->error('Failed to retrieve balance.');
        }

        return 0;
    }
}