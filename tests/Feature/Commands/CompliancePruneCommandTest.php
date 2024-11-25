<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Motomedialab\Compliance\Console\Commands\CompliancePruneCommand;
use Motomedialab\Compliance\Events\ComplianceDeleting;
use Motomedialab\Compliance\Models\ComplianceCheck;
use Motomedialab\Compliance\Tests\Stubs\Models\TestModel;

covers(CompliancePruneCommand::class);


beforeEach(function () {
    config()->set('compliance.models', [
        TestModel::class => [
            'column' => 'created_at',
        ]
    ]);

    Schema::create('test_models', function (Blueprint $table) {
        $table->id();

        $table->boolean('allow_delete')->default(true);

        $table->timestamps();
    });
});

it('wont delete any records when there arent any', function () {
    $this->artisan(CompliancePruneCommand::class)
        ->expectsOutput('No records to prune')
        ->assertExitCode(0);
});

it('it will check for compliance once again before deleting', function () {

    Event::fake();

    TestModel::factory()->deletable()->create(['allow_delete' => false]);
    ComplianceCheck::create(['model_type' => TestModel::class, 'model_id' => 1, 'deletion_date' => now()->subDay()]);

    $this->artisan(CompliancePruneCommand::class)
        ->expectsOutput('Deleted 0 non-compliant records')
        ->assertExitCode(0);

    Event::assertNotDispatched(ComplianceDeleting::class);
});

it('it will delete and emit event for deleted records', function () {

    Event::fake(ComplianceDeleting::class);

    TestModel::factory()->deletable()->create();
    ComplianceCheck::create(['model_type' => TestModel::class, 'model_id' => 1, 'deletion_date' => now()->subDay()]);

    $this->artisan(CompliancePruneCommand::class)
        ->expectsOutput('Deleted 1 non-compliant records')
        ->assertExitCode(0);

    Event::assertDispatchedTimes(ComplianceDeleting::class);

    expect(ComplianceCheck::count())->toBe(0);
});
