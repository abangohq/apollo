<?php

namespace App\Enums;

enum Prefix: string
{
    case WALLET_WITHDRAWAL = 'WW';
    case WALLET_DEPOSIT = 'WD';
    case AIRTIME_TOPUP = 'AT';
    case CABLE_TOPUP = 'CT';
    case BETTING_TOPUP = 'BT';
    case DATA_TOPUP = 'DT';
    case METER_TOPUP = 'MT';
    case WIFI_TOPUP = 'WT';
    case SWAP = 'ST';
    case RECONCILE = 'RC';
    case BONUS = 'BN';

    case CRYPTO = 'CD';
}
