<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $slug = 'ext_ai_music_pro';
        $settings = Setting::getCache();
        $freeOpenAiItems = $settings->free_open_ai_items;
        if (is_array($freeOpenAiItems) && ! in_array($slug, $freeOpenAiItems)) {
            $freeOpenAiItems[] = $slug;
            $settings->free_open_ai_items = $freeOpenAiItems;
            $settings->save();
            Setting::forgetCache();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
