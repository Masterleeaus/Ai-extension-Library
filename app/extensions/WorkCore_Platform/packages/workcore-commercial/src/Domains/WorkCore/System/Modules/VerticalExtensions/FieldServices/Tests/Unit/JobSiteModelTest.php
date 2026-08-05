<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Tests\Unit;

use App\Domains\WorkCore\System\Modules\VerticalExtensions\FieldServices\Models\JobSite;
use PHPUnit\Framework\TestCase;

class JobSiteModelTest extends TestCase
{
    public function test_job_site_model_fillable()
    {
        $model = new JobSite();

        $expected = [
            'company_id',
            'customer_id',
            'address',
            'latitude',
            'longitude',
            'site_type',
            'access_instructions',
            'hazard_notes',
            'parking_instructions',
            'gate_code',
            'contact_name',
            'contact_phone',
            'site_active',
            'metadata',
        ];

        $this->assertEquals($expected, $model->getFillable());
    }

    public function test_job_site_table_name()
    {
        $model = new JobSite();
        $this->assertEquals('field_services_job_sites', $model->getTable());
    }

    public function test_job_site_casts()
    {
        $model = new JobSite();
        $casts = $model->getCasts();

        $this->assertEquals('float', $casts['latitude']);
        $this->assertEquals('float', $casts['longitude']);
        $this->assertEquals('boolean', $casts['site_active']);
        $this->assertEquals('json', $casts['metadata']);
    }
}
