<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use App\Enums\VerificationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kyc extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'verification_type',
        'verification_value',
        'reference',
        'status'
    ];

    public function hasPreviouslyProcessed(): bool
    {
        return $this->status == VerificationStatus::PROCESSED;
    }

    public function getDeservedKycTier(): int
    {
        $hasBvn = $this->user->kycs()->where('verification_type', VerificationType::BVN)->exists();
    
        return match (true) {
            $hasBvn => 2,
            default => 1,
        };
    }
    
    public function scopeByBvn($query, $bvn)
    {
        return $query->where('verification_value', $bvn)->where('verification_type', VerificationType::BVN);
    }

    /**
     * Get the KYC user information.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
