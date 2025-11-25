<?php

namespace App\Support;

use App\Enums\VerificationType;

class WebhookResponse
{
    public ?string $verification_status;

    public ?string $verification_value;

    public ?string $verification_type;
    
    public ?string $reference;
    public $user_data;

    public $location_data;

    public ?string $doc_url;

    public function __construct($verificationType, $verificationStatus, $verificationValue, $reference, $docUrl, $userData = null, $locationData = null)
    {
        $this->verification_type = $verificationType;
        $this->verification_status = $verificationStatus;
        $this->reference = $reference;
        $this->user_data = $userData;
        $this->doc_url = $docUrl;
        $this->verification_value = $verificationValue;
        $this->location_data = $locationData;
    }

    public static function createForBvnVerification($verificationStatus, $verificationValue, $reference, $userData, $selfieUrl): WebhookResponse
    {
        return new self(VerificationType::BVN->value, $verificationStatus, $verificationValue, $reference, $selfieUrl, $userData);
    }



    public function getDocumentUrl(): string
    {
        return $this->doc_url;
    }

    public function getStatus(): string
    {
        return $this->verification_status;
    }

    public function getVerificationStatus(): string
    {
        return $this->verification_status;
    }

    public function getAddresses(): array
    {
        return $this->user_data;
    }
}
