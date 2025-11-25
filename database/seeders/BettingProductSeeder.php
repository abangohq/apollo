<?php

namespace Database\Seeders;

use App\Models\BettingProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BettingProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'id' => '1',
                'product' => 'BETBONANZA',
                'logo' => 'https://api.live.redbiller.com/logos/betting/betbonanza.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '500000.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '2',
                'product' => 'BET9JA',
                'logo' => 'https://api.live.redbiller.com/logos/betting/bet9ja.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '100000.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '3',
                'product' => 'SPORTYBET',
                'logo' => 'https://api.live.redbiller.com/logos/betting/sportybet.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '100000.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '4',
                'product' => 'BETKING',
                'logo' => 'https://api.live.redbiller.com/logos/betting/betking.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '100000.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '5',
                'product' => 'BETLION',
                'logo' => 'https://api.live.redbiller.com/logos/betting/betlion.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '100000.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '6',
                'product' => 'ONE_XBET',
                'logo' => 'https://api.live.redbiller.com/logos/betting/one_xbet.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '500000.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '7',
                'product' => 'BETWAY',
                'logo' => 'https://api.live.redbiller.com/logos/betting/betway.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '100000.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '8',
                'product' => 'MERRYBET',
                'logo' => 'https://api.live.redbiller.com/logos/betting/merrybet.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '100000.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '9',
                'product' => 'BANGBET',
                'logo' => 'https://api.live.redbiller.com/logos/betting/bangbet.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '200000.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '10',
                'product' => 'BET9JA_AGENT',
                'logo' => 'https://api.live.redbiller.com/logos/betting/bet9ja_agent.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '500000.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '11',
                'product' => 'NAIJABET',
                'logo' => 'https://api.live.redbiller.com/logos/betting/naijabet.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '100000.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '12',
                'product' => 'MYLOTTOHUB',
                'logo' => 'https://api.live.redbiller.com/logos/betting/mylottohub.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '500000.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '13',
                'product' => 'CLOUDBET',
                'logo' => 'https://api.live.redbiller.com/logos/betting/cloudbet.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '0.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '14',
                'product' => 'PARIPESA',
                'logo' => 'https://api.live.redbiller.com/logos/betting/paripesa.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '2000000.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '15',
                'product' => 'NAIRAMILLION',
                'logo' => 'https://api.live.redbiller.com/logos/betting/nairamillion.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '100000.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '16',
                'product' => 'NAIRABET',
                'logo' => 'https://api.live.redbiller.com/logos/betting/nairabet.png',
                'minimum_amount' => '100.00',
                'maximum_amount' => '250000.00',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        BettingProduct::query()->truncate();
        
        BettingProduct::insert($data);
    }
}
