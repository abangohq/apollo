<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Casts\RoleCast;
use App\Enums\Tranx;
use App\Enums\VerificationStatus;
use App\Enums\VerificationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SignUpBonus;
use App\Models\Referral;
use App\Models\ReferralClaim;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public const MAX_LOGIN_ATTEMPT = 5;
    public const MAX_TIER_LEVEL = 3;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'user_type',
        'role'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'pin',
        'tier_id',
        'passcode',
        'dob',
        'bvn',
        'password',
        'phone',
        'referral_code',
        'device_token',
        'device_type',
        'status',
        'credits',
        'avatar',
        'referral_amount_available',
        'referral_amount_redeemed',
        'failed_login_attempts',
        'heard_about_us',
        'email_verified_at',
        'has_biometric',
        'face_id',
        'touch_id',
        'is_flagged',
        'flag_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'bvn',
        'pin',
        'passcode',
        'remember_token',
        'verification_token',
        'verification_token_expiration',
        'user_type',
        'role',
        'face_id',
        'touch_id',
    ];

    /**
     * The attributes should be casts.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => RoleCast::class,
        'has_biometric' => 'boolean',
        'is_flagged' => 'boolean',
    ];

    protected $appends = [
        'total_crypto_trade',
        'total_withdrawn',
        'has_pin',
        'wallet_balance',
        'has_verifiedBVN',
        'has_default_bank_account',
        'has_withdrawn',
        'crypto_wallets'
    ];

    public function hasExceededLoginAttempts(): bool
    {
        return $this->failed_login_attempts >= self::MAX_LOGIN_ATTEMPT;
    }

    public function resetFailedAttempts(): bool
    {
        return $this->update(['failed_login_attempts' => 0]);
    }

    public function getHasVerifiedBVNAttribute()
    {
        return $this->kycs()->whereVerificationType(VerificationType::BVN)->where('status', VerificationStatus::COMPLETED)->exists();
    }

    public function getHasDefaultBankAccountAttribute()
    {
        $ubank = BankAccount::where('user_id', $this->id)->where('is_primary', 1);
        $bankCount = $ubank->count();
        return ($bankCount > 0);
    }

    public function getWalletBalanceAttribute()
    {
        $wallet = Wallet::where('user_id', $this->id)->first();
        return $wallet ? $wallet->balance : 0.0;
    }

    public function getHasWithdrawnAttribute()
    {
        return Withdrawal::where('user_id', $this->id)->first() != null;
    }

    public function getNextTierLevel(): int
    {
        return $this->tier_id < self::MAX_TIER_LEVEL ? $this->tier_id + 1 : $this->tier_id;
    }

    public function getTotalCryptoTradeAttribute()
    {
        $amount = (float) CryptoTransaction::where('status', 'successful')->where('user_id', $this->id)->sum('usd_value');

        return $amount;
    }

    /**
     * Get the user total withdrawal made
     */
    public function getTotalWithdrawnAttribute()
    {
        return floatval($this->withdrawals()->where('status', Tranx::TRANX_SUCCESS)->sum('amount'));
    }

    public function getHasPinAttribute()
    {
        return $this->pin !== null;
    }

    public function getCryptoWalletsAttribute()
    {
        return $this->cryptowallets()->get();
    }

    /**
     * Get the user's available referral amount
     */
    public function getReferralAmountAvailableAttribute()
    {
        // Calculate total referral earnings from referrals table
        $totalReferralEarnings = Referral::where('code', $this->referral_code)->sum('reward_amount');

        // Calculate claimed amount using the claims tracking system
        $referralAmountRedeemed = ReferralClaim::where('user_id', $this->id)->sum('amount_claimed');

        // Calculate available amount
        return (int) ($totalReferralEarnings - $referralAmountRedeemed);
    }

    /**
     * Get the user's redeemed referral amount
     */
    public function getReferralAmountRedeemedAttribute()
    {
        return (int) ReferralClaim::where('user_id', $this->id)->sum('amount_claimed');
    }

    public static function getReservedUsernames()
    {
        return ['admin', 'root'];
    }

    public function routeNotificationForFcm($notification)
    {
        return $this->device_token;
    }

    public function routeNotificationForMail()
    {
        return $this->email;
    }

    public function airtime()
    {
        return $this->hasMany(AirtimeTopUp::class);
    }

    public function betting()
    {
        return $this->hasMany(BettingTopUp::class);
    }

    public function cable()
    {
        return $this->hasMany(CableTopUp::class);
    }

    public function data()
    {
        return $this->hasMany(DataTopUp::class);
    }

    public function wifi()
    {
        return $this->hasMany(WiFiTopUp::class);
    }

    public function meter()
    {
        return $this->hasMany(MeterTopUp::class);
    }

    public function banks()
    {
        return $this->hasMany(BankAccount::class, 'user_id');
    }

    public function cryptowallets()
    {
        return $this->hasMany(CryptoWallet::class);
    }

    public function cryptoTransactions()
    {
        return $this->hasMany(CryptoTransaction::class);
    }

    /**
     * Get the user swap transaction
     */
    public function swapTransaction()
    {
        return $this->hasMany(SwapTransaction::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function kyc()
    {
        return $this->hasMany(Kyc::class);
    }

    public function kycs()
    {
        return $this->hasMany(Kyc::class);
    }

    /**
     * Get the user withdraws
     */
    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * Get the permission for this user
     */
    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }

    /**
     * Query scope to retrieve staff users type
     */
    public function scopeStaff(Builder $query)
    {
        return $query->where('users.user_type', 'staff');
    }

    /**
     * Query scope to retrieve staff users type
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('users.status', 'active');
    }

    /**
     * Check if user has a specific ability
     */
    public function hasPermission($ability)
    {
        return $this->permissions()->where('ability', $ability)->exists();
    }

    /**
     * Get the user's sign-up bonus
     */
    public function signUpBonus()
    {
        return $this->hasOne(SignUpBonus::class);
    }

    public function requiresManualReview(): bool
    {
        return (bool) ($this->is_flagged ?? false) || (bool) ($this->wallet?->is_flagged ?? false);
    }
}
