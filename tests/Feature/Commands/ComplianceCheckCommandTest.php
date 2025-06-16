<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Motomedialab\Compliance\Console\Commands\ComplianceCheckCommand;
use Motomedialab\Compliance\Events\ComplianceRecordPendingDeletion;
use Motomedialab\Compliance\Models\ComplianceCheck;
use Motomedialab\Compliance\Tests\Stubs\Models\TestModel;

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

it('will run the check in dry run mode', function () {
    TestModel::factory()->active()->create();

    $this->artisan(ComplianceCheckCommand::class, ['--dry-run' => true])
        ->expectsOutput('Running in dry-run mode. No records will be scheduled for deletion.')
        ->assertExitCode(0);
});

it('can check for non-conforming compliance records and mark them for deletion', function () {
    Event::fake();

    // create five models that dont need to be deleted
    TestModel::factory(count: 5)->active()->create();

    // create four models that should be scheduled for deletion
    TestModel::factory(count: 4)->deletable()->create();

    // and one that cant be deleted
    TestModel::factory()->deletable()->state(['allow_delete' => false])->create();

    $this->artisan(ComplianceCheckCommand::class)
        ->expectsOutput('Scheduled 4 Motomedialab\Compliance\Tests\Stubs\Models\TestModel records for deletion')
        ->assertExitCode(0);

    Event::assertDispatchedTimes(ComplianceRecordPendingDeletion::class, 4);

    expect(ComplianceCheck::count())->toBe(4);
});
