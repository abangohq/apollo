<?php

namespace App\Jobs;

use App\Services\KycService;
use Illuminate\Bus\Queueable;
use App\Enums\VerificationType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Traits\Conditionable;

class BvnFaceMatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Conditionable;

    public $tries = 5;

    private KycService $kycService;

    /**
     * Create a new job instance.
     */
    public function __construct(public $data)
    {
        $this->afterCommit();

        $this->kycService = app(KycService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $transformedData = $this->kycService->extractVerificationInformationFromProvider($this->data, VerificationType::BVN);

        logger('BVN + SELFIE verification transformed data', [$transformedData]);
        
        if ($transformedData === null) {
            logger('BVN verification failed: Unable to extract verification information from provider data');
            return;
        }
        
        $this->kycService->processCustomerVerification($transformedData);
    }
}
