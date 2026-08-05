<?php

declare(strict_types=1);

namespace App\Extensions\Reviews\System\Events;
class ExtensionEvent{public function __construct(public readonly string$name,public readonly array$payload=[],public readonly string$extension='reviews'){}}
