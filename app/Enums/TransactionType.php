<?php

namespace App\Enums;

enum TransactionType: string
{
    case WITHDRAW = 'withdraw';
    case WALLET = 'wallet';
    case CREDIT = 'credit';
    case DEBIT = 'debit';
    case REVERSAL = 'reversal';
    case WALLET_TRANSFER = 'wallet-transfer';
    case DEPOSIT = 'deposit';
    case FUNDING = 'wallet-funding';
    case BANK_WITHDRAW = 'bank-withdraw';
    case DEPOSIT_CRYPTO = 'crypto';
    case GIFTCARD = 'giftcard';
    case BUY_GIFTCARD = 'buy-giftcard';
    case SELL_GIFTCARD = 'sell-giftcard';
    case AIRTIME = 'airtime';
    case BETTING = 'betting';
    case DATA = 'data';
    case CABLE = 'cable';
    case METER = 'meter';
    case WIFI = 'wifi';
    case BILL = 'bill';
}
