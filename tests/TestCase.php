<?php

namespace Motomedialab\Compliance\Tests;

use Motomedialab\Compliance\Providers\ComplianceServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ComplianceServiceProvider::class
        ];
    }
}
