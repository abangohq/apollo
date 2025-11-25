<?php

namespace App\Enums;

enum Tranx: string
{
   case DEBIT = 'debit';
   case CREDIT = 'credit';

   case TRANX_PENDING = 'pending';
   case TRANX_SUCCESS  = 'successful';
   case TRANX_FAILED  = 'failed';
   case TRANX_REJECTED = 'rejected';

   /**
    * Withdraw channel
    */
   case WD_REDBILLER = 'REDBILLER';
   case WD_MONNIFY = 'MONNIFY';
   case WD_FINCRA = 'FINCRA';
   case WD_ADMIN = 'ADMIN';

   /**
    * Morhp types
    */
   case DEPOSIT = 'deposit';
   case WITHDRAW = 'withdraw';
   case DATA = 'data';
   case AIRTIME = 'airtime';
   case WIFI = 'wifi';
   case BETTING = 'betting';
   case METER = 'meter';
   case CABLE = 'cable';
   case SWAP = 'swap';
   case RECONCILE = 'reconcile';
   case BONUS = 'bonus';

   /**
    * Withdrawal channel
    */
   case AUTO = 'automated';
   case MANUAL = 'manual';

   /**
    * Cryptos
    */
   case BTC = 'BTC';
   case BITCOIN = 'bitcoin';
   case ETHEREUM = 'ethereum';
   case TRON = 'tron';
   case SOLANA = 'solana';
   case XRP = 'xrp';



   case CRYPTO = 'crypto';
   case XPROCESSING = '0XProcessing';
   case VAULTODY = 'vaultody';
}
