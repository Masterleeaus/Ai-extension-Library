<?php

declare(strict_types=1);

namespace App\Extensions\ChatbotWhatsapp\System\Providers;
class VonageProvider extends AbstractHttpMessagingProvider { public function key():string{return 'vonage';} public function send(array $message):array{ return $this->fallback($message); } }
