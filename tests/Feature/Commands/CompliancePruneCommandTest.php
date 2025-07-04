<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Motomedialab\Compliance\Console\Commands\CompliancePruneCommand;
use Motomedialab\Compliance\Events\ComplianceDeleting;
use Motomedialab\Compliance\Models\ComplianceCheck;
use Motomedialab\Compliance\Tests\Stubs\Models\TestModel;

beforeEach(function () {

    // clear our morph map
    Relation::morphMap([], false);

    config()->set('compliance.models', [
        TestModel::class => [
            'column' => 'created_at',
        ],
    ]);

    Schema::create('test_models', function (Blueprint $table) {
        $table->id();

        $table->boolean('allow_delete')->default(true);

        $table->timestamps();
    });
});

it('will run the prune in dry run mode', function () {
    TestModel::factory()->active()->create();

    $this->artisan(CompliancePruneCommand::class, ['--dry-run' => true])
        ->expectsOutput('Running in dry-run mode. No records will be deleted.')
        ->assertExitCode(0);
});

it('wont delete any records when there arent any', function () {
    TestModel::factory()->active()->create();

    $this->artisan(CompliancePruneCommand::class)
        ->expectsOutput('No records to prune for ' . TestModel::class)
        ->assertExitCode(0);
});

it('it will check for compliance once again before deleting', function () {

    Event::fake();

    TestModel::factory()->deletable()->create(['allow_delete' => false]);
    ComplianceCheck::create(['model_type' => TestModel::class, 'model_id' => 1, 'deletion_date' => now()->subDay()]);

    $this->artisan(CompliancePruneCommand::class)
        ->expectsOutput('Found 0 non-compliant Motomedialab\Compliance\Tests\Stubs\Models\TestModel records that were deleted.')
        ->assertExitCode(0);

    Event::assertNotDispatched(ComplianceDeleting::class);
});

it('can handle morph aliases', function () {
    Relation::morphMap(['test' => TestModel::class]);

    TestModel::factory()->deletable()->create(['allow_delete' => true]);
    ComplianceCheck::create(['model_type' => 'test', 'model_id' => 1, 'deletion_date' => now()->subDay()]);

    $this->artisan(CompliancePruneCommand::class)
        ->expectsOutput('Found 1 non-compliant Motomedialab\Compliance\Tests\Stubs\Models\TestModel records that were deleted.')
        ->assertExitCode(0);
});

it('it will delete and emit event for deleted records', function () {

    Event::fake(ComplianceDeleting::class);

    TestModel::factory()->deletable()->create();
    ComplianceCheck::create(['model_type' => TestModel::class, 'model_id' => 1, 'deletion_date' => now()->subDay()]);

    $this->artisan(CompliancePruneCommand::class)
        ->expectsOutput('Found 1 non-compliant Motomedialab\Compliance\Tests\Stubs\Models\TestModel records that were deleted.')
        ->assertExitCode(0);

    Event::assertDispatchedTimes(ComplianceDeleting::class);

    expect(ComplianceCheck::count())->toBe(0);
});
