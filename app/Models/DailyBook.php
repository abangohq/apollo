<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

/**
 * @property mixed $bank_code
 * @property mixed $account_name
 * @property mixed $account_number
 * @property mixed $bank_name
 */
class DailyBook extends Model
{
    use HasUuids, HasFactory;

    protected $table = 'daily_book_closures';

    protected $fillable = ['date', 'total_credits', 'total_debits', 'total_charges', 'total_transactions', 'net_position'];

   
}
