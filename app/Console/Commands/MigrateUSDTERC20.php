<?php

namespace App\Console\Commands;

use App\Jobs\MigrateUSDT;
use Illuminate\Console\Command;

class MigrateUSDTERC20 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate-usdt-wallets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        MigrateUSDT::dispatch();

    }
}
