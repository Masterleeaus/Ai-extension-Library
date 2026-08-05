<?php

namespace Database\Seeders\Admin;

use App\Models\VirtualCardApi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VirtualApiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $virtual_card_apis = array(
            array('admin_id' => '1','image' => 'seeder/virtual-card.png','card_details' => 'This card is property of QRPay, Wonderland. Misuse is criminal offence. If found, please return to QRPay or to the nearest bank.','config' => '{"flutterwave_secret_key":"TITAN_FLUTTERWAVE_SECRET_KEY","flutterwave_secret_hash":"TITAN_FLUTTERWAVE_SECRET_HASH","flutterwave_url":"https:\/\/api.flutterwave.com\/v3","sudo_api_key":"TITAN_SUDO_API_KEY","sudo_vault_id":"TITAN_SUDO_VAULT_ID","sudo_url":"https:\/\/api.sandbox.sudo.cards","sudo_mode":"sandbox","stripe_public_key":"TITAN_STRIPE_PUBLIC_KEY","stripe_secret_key":"TITAN_STRIPE_SECRET_KEY","stripe_url":"https:\/\/api.stripe.com\/v1","strowallet_public_key":"TITAN_STROWALLET_PUBLIC_KEY","strowallet_secret_key":"TITAN_STROWALLET_SECRET_KEY","strowallet_url":"https:\/\/strowallet.com\/api\/bitvcard\/","strowallet_mode":"sandbox","name":"strowallet"}','created_at' => now(),'updated_at' => now())
          );
        VirtualCardApi::insert($virtual_card_apis);
    }
}
