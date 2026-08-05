<?php

declare(strict_types=1);


namespace App\Domains\WorkCore\System\Modules\Premises\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Domains\WorkCore\System\Modules\Premises\Entities\PremiseSelfServiceRequest;

class PremiseSelfServiceRequestWorkLinked
{
    use Dispatchable, SerializesModels;

    public function __construct(public PremiseSelfServiceRequest $serviceRequest) {}
}
