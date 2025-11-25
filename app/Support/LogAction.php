<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogAction
{
   /**
    * Log daily withdrawal limit attempt
    */
   public static function withdrawLimit(mixed $amount)
   {
      Log::info('Attempt to withdraw more than daily limit:', [
         'user' => auth()->user()->email,
         'amount' => $amount,
         'ip' => request()->ip()
      ]);
   }
}
