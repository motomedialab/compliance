<?php

use Illuminate\Support\Facades\Config;
use Motomedialab\Compliance\Tests\Stubs\Models\TestModel;

it('can determine the grace period from the config', function () {

    Config::set('compliance.models', [
        TestModel::class => [
            'column' => 'created_at',
            'delete_after_days' => 365 * 2, // 3 years
            'deletion_grace_period' => 17 // 17 days
        ]
    ]);

    $model = new TestModel();
    expect($model->complianceGracePeriod())->toBe(17)
        ->and($model->complianceCheckColumn())->toBe('created_at')
        ->and($model->complianceDeleteAfterDays())->toBe(365 * 2);
});

it('will assume the defaults if not set in the config', function () {
    Config::set('compliance.models', [TestModel::class]);

    $model = new TestModel();
    expect($model->complianceGracePeriod())->toBe(15)
        ->and($model->complianceCheckColumn())->toBe('last_login_at')
        ->and($model->complianceDeleteAfterDays())->toBe(365 * 3);
});
