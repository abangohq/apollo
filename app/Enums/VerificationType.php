<?php

namespace App\Enums;

enum VerificationType: string
{
    case BVN = 'bvn';
    case NIN = 'nin';

    public static function getValues()
    {
        return [
            self::BVN,
            self::NIN
        ];
    }
}
