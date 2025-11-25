<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kyc\KycRequest;
use App\Http\Requests\Kyc\UpdateKycRequest;
use App\Jobs\BvnFaceMatchJob;
use App\Repositories\KycRepository;
use Illuminate\Http\Request;
use App\Services\KycService;
use App\Enums\VerificationType;

class KycController extends Controller
{
    public function __construct(
        public KycService $kycService,
        private KycRepository $kycRepository
    ) {}

    public function getVerification() {
        $data = $this->kycService->fetchCustomerVerifications();

        return $this->success($data);
    }

    public function processVerification(Request $request) {
        logger('Dojah webhook', $request->all());

        if(strtolower($request->verification_type) === VerificationType::BVN->value || strtolower($request->verification_type) === VerificationType::NIN->value) {
            $this->handleSelfieKYCWithFaceMatchWebhook($request);
        }



        return $this->success();
     }

    public function createVerification(KycRequest $request) {
        $verificationType = $request->input('verification_type');

        $data = $this->kycService->createVerificationRequest($verificationType);

        return $this->success($data);
    }

    public function handleSelfieKYCWithFaceMatchWebhook(Request $request)
    {
        logger('selfie webhook', $request->all());
        BvnFaceMatchJob::dispatch($request->all());
    }



    public function kycs() {
        return $this->kycRepository->kycs();
    }

    public function update(UpdateKycRequest $request) {
        $id = $request->route('id');

        $data = $request->validationData();

        return $this->kycRepository->updateKyc($id, $data);
    }
}
