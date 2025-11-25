<?php

namespace Database\Seeders;

use App\Models\MeterProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MeterProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'id' => '1',
                'name' => 'Abuja',
                'logo' => 'https://res.cloudinary.com/diyxzs220/image/upload/v1698229298/bills/phpIpSDHL_v6miia.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '2',
                'name' => 'Eko',
                'logo' => 'https://res.cloudinary.com/diyxzs220/image/upload/v1698225840/bills/phpCFUa3r_u2iv3s.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '3',
                'name' => 'Enugu',
                'logo' => 'https://res.cloudinary.com/diyxzs220/image/upload/v1698225809/bills/phpoQPRFK_xzcjtl.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '4',
                'name' => 'Jos',
                'logo' => 'https://res.cloudinary.com/diyxzs220/image/upload/v1698226500/bills/php7Ww33r_v27uw9.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '5',
                'name' => 'Ibadan',
                'logo' => 'https://res.cloudinary.com/diyxzs220/image/upload/v1698225825/bills/phpAa2Ou4_mppcfb.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '6',
                'name' => 'Ikeja',
                'logo' => 'https://res.cloudinary.com/diyxzs220/image/upload/v1698225648/bills/php0SuDad_fxzj4x.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '7',
                'name' => 'Kaduna',
                'logo' => 'https://res.cloudinary.com/diyxzs220/image/upload/v1698229349/bills/phpYE7VNx_dqy2vn.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '8',
                'name' => 'Kano',
                'logo' => 'https://res.cloudinary.com/diyxzs220/image/upload/v1698228718/bills/phphhTmHb_cx9mny.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '9',
                'name' => 'Porthacourt',
                'logo' => 'https://res.cloudinary.com/diyxzs220/image/upload/v1698225951/bills/phprUUFGj_oykwqn.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ];

        MeterProduct::query()->truncate();
        MeterProduct::insert($data);
    }
}
