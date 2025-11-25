<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoWalletAddressHistory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'crypto_wallet_address_history';

    protected $fillable = [
        'crypto_wallet_id',
        'old_address',
        'new_address',
        'chain',
        'reason',
        'metadata',
        'changed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'changed_at' => 'datetime',
    ];

    public function cryptoWallet(): BelongsTo
    {
        return $this->belongsTo(CryptoWallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new address history record
     */
    public static function recordAddressChange(
        CryptoWallet $wallet,
        string $oldAddress,
        string $newAddress,
        string $reason = 'migration',
        array $metadata = []
    ): self {
        return self::create([
            'crypto_wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'old_address' => $oldAddress,
            'new_address' => $newAddress,
            'chain' => $wallet->chain,
            'reason' => $reason,
            'metadata' => $metadata,
            'changed_at' => now(),
        ]);
    }
}
