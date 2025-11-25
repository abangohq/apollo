<?php

namespace App\Support;

use App\Enums\Prefix;
use App\Enums\Status;
use App\Enums\Tranx;
use App\Models\AirtimeTopUp;
use App\Models\BettingTopUp;
use App\Models\CableTopUp;
use App\Models\CryptoTransaction;
use App\Models\DataTopUp;
use App\Models\MeterTopUp;
use App\Models\WifiTopUp;
use App\Models\Withdrawal;
use App\Models\SwapTransaction;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use function Laravel\Prompts\error;

class Utils
{
    /**
     * Generate OTP code
     * 
     * @return string
     */
    public static function generateToken()
    {
        return (string) mt_rand(1000, 9999);
    }

    /**
     * Transaction medium match
     * 
     * @return int|null
     */
    public static function tranxMedium(?string $val)
    {
        $value = strtolower($val);
        return match ($value) {
            'funds' => 1,
            'withdraw' => 2,
            'bills' => 3,
            'virtualcard' => 4,
        };
    }

    /**
     * Get role int match
     */
    public static function userRole(?string $val)
    {
        $value = strtolower($val);
        return match ($value) {
            'user' => 1,
            'agent' => 2,
            default => null
        };
    }

    /**
     * Return alowed type for transactions
     */
    public static function tranxTypes()
    {
        return [
            'withdraw',
            'data',
            'airtime',
            'meter',
            'betting',
            'cable',
            'wifi',
            'crypto',
            'swap'
        ];
    }

    /**
     * Return transaction status
     */
    public static function tranxStatus()
    {
        return collect([
            Tranx::TRANX_PENDING,
            Tranx::TRANX_SUCCESS,
            Tranx::TRANX_FAILED,
            Tranx::TRANX_REJECTED
        ])->map(fn ($st) => $st->value)->toArray();
    }

    /**
     * Get the morh models
     */
    public static function tranxMorphModels()
    {
        return [
            Withdrawal::class,
            DataTopUp::class,
            AirtimeTopUp::class,
            MeterTopUp::class,
            BettingTopUp::class,
            CableTopUp::class,
            WifiTopUp::class,
            CryptoTransaction::class,
            SwapTransaction::class
        ];
    }

    /**
     * Get Transaction type morphyKey
     */
    public static function tranxTypeKey(?string $val)
    {
        $value = strtolower($val);
        return match ($value) {
            'withdraw' => 'withdraw',
            'data' => 'data',
            'airtime' => 'airtime',
            'meter' => 'meter',
            'betting' => 'betting',
            'cable' => 'cable',
            'wifi' =>  'wifi',
            'crypto' => 'crypto',
            'bills' => 'bills',
            default => null
        };
    }

    /**
     * Withdrawal status map
     */
    public static function withdrawalStatus(?string $val)
    {
        $value = strtolower($val);
        return match ($value) {
            'pending' => Status::PENDING,
            'successful' => Status::SUCCESSFUL,
            'rejected' => Status::REJECTED,
            default => null
        };
    }

    /**
     * User column sort
     */
    public static function userSortColumn(?string $column)
    {
        $value = strtolower($column);
        return match ($value) {
            'balance' => 'balance',
            'total_trade' => 'total_trade',
            default => 'users.created_at'
        };
    }

    /**
     * User column sort direction
     */
    public static function userSortDirection(?string $dir)
    {
        $value = strtolower($dir);
        return match ($value) {
            'asc' => 'asc',
            'desc' => 'desc',
            default => 'desc'
        };
    }

    /**
     * Generate reference for transactions
     */
    public static function generateReference(?Prefix $prefix = null)
    {
        $prefix = $prefix?->value;
        $reference = strtoupper(Str::random(16));
        return !is_null($prefix) ? "{$prefix}{$reference}" : $reference;
    }

    /**
     * generate transaction reference
     * 
     * @return string
     */
    public static function tranxRef()
    {
        return bin2hex(random_bytes(25));
    }

    /**
     * Get cursor from page ur;
     */
    public static function getCursor(?String $url)
    {
        if (is_string($url)) {
            $queryString = parse_url($url, PHP_URL_QUERY);
            parse_str($queryString, $queryParams);

            if (isset($queryParams['cursor'])) {
                return $queryParams['cursor'];
            }
        }

        return null;
    }

    /**
     * Generate referral code for registration
     */
    public static function referralCode()
    {
        return Str::random(6);
    }

    /**
     * Complete the designated task
     */
    public static function completeTask($user_id, $task_id)
    {
        $user_task = DB::table('user_task')
            ->where('user_id', $user_id)->where('task_id', $task_id)->first();

        if (!$user_task) {
            DB::table('user_task')
                ->insert([
                    'user_id' => $user_id,
                    'task_id' => $task_id,
                    'completed_at' => now(),
                ]);
        }

        return true;
    }

    /**
     * Log error if condition is met
     */
    public static function LogAlertIf($condition, $message, $context)
    {
        if ($condition) {
            Log::alert($message, $context);
        }
    }

    public static function getImageAsBase64(string $imageUrl): string
    {
        $imageData = file_get_contents($imageUrl);

        if (!$imageData) {
            throw new \Exception('Unable to fetch image from URL');
        }

        return base64_encode($imageData);

        // $imageInfo = getimagesizefromstring($imageData);

        // logger($imageInfo);
        // $mimeType = $imageInfo['mime'];

        // $base64Image = base64_encode($imageData);

        // $base64ImageWithMimeType = "data:{$mimeType};base64,{$base64Image}";

        // return $base64ImageWithMimeType;
    }
}
