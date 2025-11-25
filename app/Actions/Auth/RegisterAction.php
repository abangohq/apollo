<?php

namespace App\Actions\Auth;

use App\Enums\Prefix;
use App\Enums\Status;
use App\Enums\Tranx;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Reconciliation;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\Task;
use App\Models\User;
use App\Models\VerifyToken;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Notifications\Auth\WelcomeGreeting;
use App\Notifications\User\BonusNotification;
use App\Services\SignUpBonusService;
use App\Support\Utils;
use App\Traits\RespondsWithHttpStatus;
use App\Traits\WalletEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\Conditionable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RegisterAction
{
   use WalletEntity, RespondsWithHttpStatus, Conditionable;

   public function __construct(public RegisterRequest $request)
   {
      //
   }

   /**
    * Handle the action
    */
   public function handle()
   {
      switch ($this->request->route('step')) {
         case 'step-1':
            $this->request->sendToken();
            return $this->success($this->request->validated(), 'Email verification token sent successfully');
         case 'step-2':
            return $this->success(message: 'email verified successfully');
         case 'step-3':
            return $this->success($this->request->validated());
         case 'step-4':
            return $this->success($this->request->validated());
         case 'step-5':
            return $this->success($this->save());
         default:
            throw new NotFoundHttpException();
            break;
      }
   }

   /**
    * Handle saving the data
    */
   public function save()
   {
      try {
         DB::beginTransaction();

         $user = User::create($this->request->userAttributes());
         Wallet::create(['user_id' => $user->id]);
         $this->deleteToken($user);

         // Create sign-up bonus for new user
         $signUpBonusService = new SignUpBonusService();
         $signUpBonusService->createSignUpBonus($user, env('SIGNUP_BONUS'));

         $task = Task::findOrFail(1);
         Utils::completeTask($user->id, $task->id);

         rescue(fn() => $user->notify(new WelcomeGreeting));

         $this->when(isset($this->request->influencer_code))->awardBonus($user);
         DB::commit();

         return $this->federate($user);
      } catch (\Throwable $th) {
         DB::rollBack();
         throw $th; // rethrow for global handler
      }
   }

   /**
    * Give the user referral bonus if code is valid
    */
   public function awardBonus(User $user)
   {
      $code = ReferralCode::where('code', $this->request->influencer_code)->whereActive(true)->first();

      if (!$code) {
         $code = new ReferralCode(['reward_amount' => 0]);
      }

      Referral::create([
         'user_id' => $user->id,
         'code' => $this->request->influencer_code,
         'reward_amount' => $code->reward_amount
      ]);

      if($code->reward_amount > 0){
         $this->credit($user->id, $code->reward_amount);

         $reconcile = Reconciliation::create($this->reconcilePayload($user, $code));
         WalletTransaction::create($this->walletTransaction($reconcile, $code));

         rescue(fn() => $user->notify(new BonusNotification($code)));
      }
   }

   /**
    * Remove all verification token
    */
   public function deleteToken(User $user)
   {
      VerifyToken::whereEmail($user->email)->delete();
   }

   /**
    * Federate the user into our application
    */
   public function federate(User $user): array
   {
      return [
         'token' => $user->createToken('authToken')->plainTextToken,
         'user' =>  new UserResource($user)
      ];
   }

   /**
    * Reconciliation payload
    */
   public function reconcilePayload(User $user, ReferralCode $code)
   {
      return [
         'reference' => Utils::generateReference(Prefix::RECONCILE),
         'user_id' => $user->id,
         'staff_id' => null,
         'origin_tranx_id' => null,
         'entry' => Tranx::CREDIT,
         'amount' => $code->reward_amount,
         'status' => Status::SUCCESSFUL,
         'reason' => 'referral bonus'
      ];
   }

   /**
    * Create the BettingTopUp payload
    */
   public function walletTransaction(Reconciliation $reconciliation, ReferralCode $code, $charge = 0)
   {
      return [
         'user_id' => $reconciliation->user_id,
         'reference' => $reconciliation->reference,
         'transaction_type' => Tranx::RECONCILE,
         'transaction_id' => $reconciliation->id,
         'entry' => $reconciliation->entry,
         'status' => Tranx::TRANX_SUCCESS,
         'narration' => "{$code->code} NGN{$reconciliation->amount} referral bonus",
         'amount' => $reconciliation->amount,
         'charge' => $charge,
         'total_amount' => intval($charge) + $reconciliation->amount,
         'wallet_balance' => User::query()->findOrfail($reconciliation->user_id)->wallet->balance
      ];
   }
}
