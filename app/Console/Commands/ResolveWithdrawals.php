<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Services\Payment\RedbillerService;
use App\Services\WalletService;
use App\Traits\WalletEntity;
use Illuminate\Console\Command;
use App\Services\Payment\MonnifyService;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Enums\Status;
use App\Enums\Tranx;
use App\Models\WalletTransaction;
use App\Notifications\User\WithdrawReversalNotification;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Facades\DB;

class ResolveWithdrawals extends Command
{
    use WalletEntity;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'withdrawal:resolve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resolve pending withdrawals by verifying their status with payment providers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $withdrawals = Withdrawal::where('status', 'pending')->with('user')->get();

        if ($withdrawals->isEmpty()) {
            $this->info('No pending withdrawals found.');
            return 0;
        }

        $this->info("Processing {$withdrawals->count()} pending withdrawals...");
        $processedCount = 0;

        foreach ($withdrawals as $withdrawal) {
            try {
                $this->line("Processing withdrawal {$withdrawal->reference} for user {$withdrawal->user->email}...");

                if ($withdrawal->platform == 'MONNIFY') {
                    $this->processMonnifyWithdrawal($withdrawal);
                } else {
                    $this->processRedbillerWithdrawal($withdrawal);
                }

                $this->info("Withdrawal {$withdrawal->reference} processed successfully.");

                $processedCount++;

            } catch (Exception $e) {
                Log::error('Error processing withdrawal', [
                    'withdrawal_id' => $withdrawal->id,
                    'reference' => $withdrawal->reference,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $this->error("Error processing withdrawal {$withdrawal->reference}: {$e->getMessage()}");
            }
        }

        $this->info("Completed processing {$processedCount} withdrawals.");
        return 0;
    }

    /**
     * Process Monnify withdrawal verification and reversal
     */
    private function processMonnifyWithdrawal(Withdrawal $withdrawal): void
    {
        try {
            $monnifyService = new MonnifyService();
            $response = $monnifyService->transferStatus($withdrawal->reference);

            $status = $response?->responseBody?->status;
            $needsReversal = !$response || !in_array($status, ['SUCCESS', 'COMPLETED']);

            if ($needsReversal) {
                $monnifyService->reversal($withdrawal);
                Log::info('Monnify withdrawal reversed', [
                    'withdrawal_id' => $withdrawal->id,
                    'reference' => $withdrawal->reference,
                    'transaction_status' => $status
                ]);
            }

        } catch (Exception $e) {
            Log::error('Error processing Monnify withdrawal', [
                'withdrawal_id' => $withdrawal->id,
                'reference' => $withdrawal->reference,
                'error' => $e->getMessage()
            ]);

            // Attempt reversal on error
            try {
                (new MonnifyService())->reversal($withdrawal);
                Log::info('Monnify withdrawal reversed due to verification error', [
                    'withdrawal_id' => $withdrawal->id,
                    'reference' => $withdrawal->reference
                ]);
            } catch (Exception $reversalError) {
                Log::error('Failed to reverse Monnify withdrawal', [
                    'withdrawal_id' => $withdrawal->id,
                    'reference' => $withdrawal->reference,
                    'reversal_error' => $reversalError->getMessage()
                ]);
            }
        }
    }

    /**
     * Process Redbiller withdrawal verification and reversal
     */
    private function processRedbillerWithdrawal(Withdrawal $withdrawal): void
    {
        try {
            $redbillerService = new RedbillerService();
            $response = $redbillerService->verifyTransaction($withdrawal->reference);

            // Check if we should reverse: transaction doesn't exist OR has failed status OR unrecognized reference (400)
            $providerStatus = (isset($response) && isset($response->meta) && isset($response->meta->status)) ? $response->meta->status : null;
            $providerReference = (isset($response) && isset($response->details) && isset($response->details->reference)) ? $response->details->reference : null;
            $httpResponseCode = (isset($response) && isset($response->response)) ? $response->response : null;

            $shouldReverse = ($response === null)
                          || in_array($providerStatus, ['Rejected', 'Cancelled', 'Declined'], true)
                          || ($httpResponseCode === 400 && (
                                ((isset($response->status) && (string)$response->status === 'false')) ||
                                (isset($response->message) && stripos($response->message, "don't recognize the reference") !== false)
                             ));

            if ($shouldReverse) {
                $this->reverseWithdrawal(
                    $withdrawal,
                    $providerStatus,
                    $providerReference
                );
            }

        } catch (Exception $e) {
            Log::error('Error processing Redbiller withdrawal', [
                'withdrawal_id' => $withdrawal->id,
                'reference' => $withdrawal->reference,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Build reversal transaction payload for wallet entry
     */
    private function reversalTranxPayload(Withdrawal $withdrawal, $charge = 0)
    {
        return [
            'user_id' => $withdrawal->user_id,
            'reference' => "RVSL-{$withdrawal->reference}",
            'transaction_type' => Tranx::WITHDRAW,
            'transaction_id' => $withdrawal->id,
            'entry' => Tranx::CREDIT,
            'status' => Tranx::TRANX_SUCCESS,
            'narration' => "Rvsl of N{$withdrawal->amount} withdrawal request",
            'amount' => $withdrawal->amount,
            'charge' => $charge,
            'total_amount' => intval($charge) + $withdrawal->amount,
            'wallet_balance' => $withdrawal->user->wallet()->value('balance'),
            'is_reversal' => true,
        ];
    }

    /**
     * Perform withdrawal reversal atomically and idempotently.
     */
    private function reverseWithdrawal(Withdrawal $withdrawal, ?string $providerStatus = null, ?string $providerReference = null): void
    {
        // Prevent duplicate reversals
        if (TransactionRepository::hasReversal($withdrawal->id, Tranx::WITHDRAW)) {
            Log::info('Skipping withdrawal reversal - reversal already exists', [
                'withdrawal_id' => $withdrawal->id,
                'reference' => $withdrawal->reference,
            ]);
            return;
        }

        try {
            DB::beginTransaction();

            $this->credit($withdrawal->user_id, $withdrawal->amount);
            $tranxPayload = $this->reversalTranxPayload($withdrawal);

            WalletTransaction::create($tranxPayload);

            $withdrawal->update([
                'status' => Status::FAILED,
                'provider_status' => $providerStatus,
                'provider_reference' => $providerReference,
            ]);

            $withdrawal->user->notify(new WithdrawReversalNotification($withdrawal));

            DB::commit();

            Log::info('Withdrawal reversed', [
                'withdrawal_id' => $withdrawal->id,
                'reference' => $withdrawal->reference,
                'provider_status' => $providerStatus ?? 'not_found',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
