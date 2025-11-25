<?php

namespace App\Console\Commands;

use App\Jobs\MigrateOldWallets;
use Illuminate\Console\Command;

class MigrateWallets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate-wallets';

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
        MigrateOldWallets::dispatch();

    }
}
