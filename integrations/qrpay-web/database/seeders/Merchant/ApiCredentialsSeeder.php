<?php

namespace Database\Seeders\Merchant;

use App\Constants\PaymentGatewayConst;
use App\Models\Merchants\DeveloperApiCredential;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApiCredentialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'merchant_id'      => 1,
                'name'              => 'Test Name',
                'client_id'         => "TITAN_MERCHANT_CLIENT_ID",
                'client_secret'     => "TITAN_MERCHANT_CLIENT_SECRET",
                'mode'              => PaymentGatewayConst::ENV_SANDBOX,
                'status'            => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]
        ];

        DeveloperApiCredential::insert($data);

    }
}
