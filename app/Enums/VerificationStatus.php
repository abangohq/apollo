<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PROCESSED = 'processed';

    case SUCCESSFUL = 'successful';

    case COMPLETED = 'completed';
    case ABANDONED = 'abandoned';

    const REJECTED = 'rejected';

    const FAILED = 'failed';

    public static function getValues()
    {
        return [
            self::PENDING,
            self::PROCESSING,
            self::SUCCESSFUL,
            self::REJECTED,
            self::COMPLETED,
            self::ABANDONED
        ];
    }
}
