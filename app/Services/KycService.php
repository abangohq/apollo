<?php

namespace App\Services;

use App\Enums\VerificationStatus;
use App\Enums\VerificationType;
use App\Models\Kyc;
use App\Notifications\Kyc\BvnVerificationNotification;
use App\Support\WebhookResponse;
use Illuminate\Support\Facades\DB;

class KycService
{

    public function __construct()
    {
    }

    public function fetchCustomerVerifications()
    {
        return kyc::where('user_id', auth()->user()->id)->get();
    }

    public function createVerificationRequest(string $verificationType)
    {
        $reference = $this->generateUniqueReference();

        $kyc = Kyc::where(['reference' => $reference, 'verification_type' => $verificationType])->first();

        if ($kyc?->status === VerificationStatus::COMPLETED->value) {
            return response()->json(['success' => false, 'message' => 'This verification has been performed and successful'], 409);
        }

        return DB::transaction(function () use ($reference, $verificationType) {
            $kyc = Kyc::updateOrCreate(
                [
                    'user_id' => request()->user()->id,
                    'verification_type' => $verificationType,
                ],
                [
                    'verification_value' => null,
                    'reference' => $reference,
                    'status' => VerificationStatus::PENDING,
                ]
            );

            $kyc->refresh();

            return $kyc;
        });
    }

    private function generateUniqueReference(): string
    {
        do {
            $reference = time() . mt_rand(10000000000, 99999999999);
        } while (Kyc::where('reference', $reference)->exists());

        return (string) $reference;
    }

    public function processCustomerVerification(WebhookResponse $response)
    {
        if(!isset($response->reference)) {
            logger("Verification does not include reference");
            return;
        }

        return DB::transaction(function () use ($response) {
            $kyc = Kyc::with('user')->where('reference', $response->reference)->first();

            logger('Kyc verification object', [$kyc]);

            if (!$kyc || $kyc?->user?->tier_id >= 2) {
                return;
            }

            $status = strtolower($response->verification_status);
            if (in_array($status, [VerificationStatus::REJECTED, VerificationStatus::FAILED])) {
                $this->handleRejectedVerification($kyc);
                return;
            }

            if ($status === VerificationStatus::ABANDONED->value) {
                $this->handleAbandonedVerification($kyc);
                return;
            }

            match ($kyc->verification_type) {
                VerificationType::BVN->value => $this->handleBvnVerification($kyc, $response),
                VerificationType::NIN->value => $this->handleBvnVerification($kyc, $response),
                default => null,
            };
        });
    }


    private function handleRejectedVerification($kyc)
    {
        $kyc->update(['status' => VerificationStatus::REJECTED]);
        $kyc->refresh();

        match ($kyc->verification_type) {
            VerificationType::BVN->value => $kyc->user->notify(new BvnVerificationNotification($kyc)),
            default => null,
        };
    }



    private function handleBvnVerification($kyc, WebhookResponse $response)
    {
        try {
            $this->upgradeUserTier($kyc, $response);
            // Refresh the KYC object to get the updated status
            $kyc->refresh();
            $kyc->user->notify(new BvnVerificationNotification($kyc));
            return;
        } catch(\Exception $e) {
            logger('An error occurred for bvn + face match verification', [$e->getMessage()]);
            // Send notification for failed verification
            $kyc->user->notify(new BvnVerificationNotification($kyc));
        }
    }

    private function upgradeUserTier($kyc, $response)
    {
        $newTier = $kyc->user->getNextTierLevel();
        $kyc->user()->update(['tier_id' => $newTier]);
        $kyc->update([
            'verification_value' => $response->verification_value,
            'status' => VerificationStatus::COMPLETED
        ]);
    }

    private function handleAbandonedVerification($kyc)
    {
        $kyc->update(['status' => VerificationStatus::ABANDONED]);
        $kyc->refresh();
    }

    public function extractVerificationInformationFromProvider($data, VerificationType $verificationType): ?WebhookResponse
    {
        if ($verificationType->value === VerificationType::BVN->value || $verificationType->value === VerificationType::NIN->value) {
            $userData = @$data['data']['user-data']['data'];
            $selfieUrl = @$data['government_data']['data']['image_url'];
            $verificationStatus = @$data['verification_status'];
            $reference = @$data['metadata']['reference'] ?? null;
            $verificationValue = @$data['verification_value'];

            return WebhookResponse::createForBvnVerification($verificationStatus, $verificationValue, $reference, $userData, $selfieUrl);
        }



        return null;
    }
}
