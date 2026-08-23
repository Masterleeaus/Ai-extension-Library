<?php

namespace App\Extensions\MarketingBot\System\Models\Whatsapp;

use Illuminate\Database\Eloquent\Model;

class WhatsappChannel extends Model
{
    protected $table = 'ext_whatsapp_channels';

    protected $fillable = [
        'user_id',
        'whatsapp_provider',
        'whatsapp_sid',
        'whatsapp_token',
        'whatsapp_phone',
        'whatsapp_sandbox_phone',
        'whatsapp_environment',
        'meta_access_token',
        'meta_phone_number_id',
        'meta_verify_token',
        'meta_waba_id',
    ];

    protected $casts = [
        'whatsapp_sid' => 'encrypted',
        'whatsapp_token' => 'encrypted',
        'meta_access_token' => 'encrypted',
        'meta_verify_token' => 'encrypted',
    ];

    public function isSandbox(): bool
    {
        return $this->whatsapp_environment === 'sandbox';
    }

    public function isMeta(): bool
    {
        return $this->whatsapp_provider === 'meta';
    }

    public function isTwilio(): bool
    {
        return $this->whatsapp_provider !== 'meta';
    }
}
