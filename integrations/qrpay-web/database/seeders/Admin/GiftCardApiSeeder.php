<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\ReloadlyApi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GiftCardApiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $gift_card_apis = array(
            array('provider' => 'RELOADLY','type' => 'GIFT-CARD','credentials' => '{"client_id":"TITAN_CLIENT_ID","secret_key":"TITAN_SECRET_KEY","production_base_url":"https:\\/\\/giftcards.reloadly.com","sandbox_base_url":"https:\\/\\/giftcards-sandbox.reloadly.com"}','status' => '1','env' => 'sandbox','created_at' =>now(),'updated_at' =>now())
          );

        ReloadlyApi::insert($gift_card_apis);
    }
}
