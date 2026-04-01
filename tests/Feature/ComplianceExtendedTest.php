<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Motomedialab\Compliance\Console\Commands\ComplianceCheckCommand;
use Motomedialab\Compliance\Console\Commands\CompliancePruneCommand;
use Motomedialab\Compliance\Models\ComplianceCheck;
use Motomedialab\Compliance\Tests\Stubs\Models\SoftDeletableModel;
use Motomedialab\Compliance\Tests\Stubs\Models\TestModel;

beforeEach(function () {
    Schema::create('test_models', function (Blueprint $table) {
        $table->id();
        $table->boolean('allow_delete')->default(true);
        $table->boolean('allow_before_deletion')->nullable();
        $table->timestamps();
    });

    Schema::create('soft_deletable_models', function (Blueprint $table) {
        $table->id();
        $table->boolean('allow_delete')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });
});

it('can handle multiple models in configuration', function () {
    config()->set('compliance.models', [
        TestModel::class => [
            'column' => 'created_at',
        ],
        SoftDeletableModel::class => [
            'column' => 'created_at',
        ],
    ]);

    TestModel::factory(3)->deletable()->create();
    SoftDeletableModel::factory(2)->deletable()->create();

    $this->artisan(ComplianceCheckCommand::class)
        ->expectsOutput('Scheduled 3 Motomedialab\Compliance\Tests\Stubs\Models\TestModel records for deletion')
        ->expectsOutput('Scheduled 2 Motomedialab\Compliance\Tests\Stubs\Models\SoftDeletableModel records for deletion')
        ->assertExitCode(0);

    expect(ComplianceCheck::count())->toBe(5);
});

it('respects force_delete configuration', function () {
    config()->set('compliance.models', [
        SoftDeletableModel::class => [
            'column' => 'created_at',
            'force_delete' => false,
        ],
    ]);

    $model = SoftDeletableModel::factory()->deletable()->create();
    ComplianceCheck::create([
        'model_type' => SoftDeletableModel::class,
        'model_id' => $model->id,
        'deletion_date' => now()->subDay(),
    ]);

    $this->artisan(CompliancePruneCommand::class)
        ->assertExitCode(0);

    // Should be soft deleted, not force deleted
    $this->assertSoftDeleted('soft_deletable_models', ['id' => $model->id]);
    expect(SoftDeletableModel::withTrashed()->find($model->id))->not->toBeNull();
});

it('force deletes when force_delete is true', function () {
    config()->set('compliance.models', [
        SoftDeletableModel::class => [
            'column' => 'created_at',
            'force_delete' => true,
        ],
    ]);

    $model = SoftDeletableModel::factory()->deletable()->create();
    ComplianceCheck::create([
        'model_type' => SoftDeletableModel::class,
        'model_id' => $model->id,
        'deletion_date' => now()->subDay(),
    ]);

    $this->artisan(CompliancePruneCommand::class)
        ->assertExitCode(0);

    $this->assertDatabaseMissing('soft_deletable_models', ['id' => $model->id]);
});

it('cleans up compliance checks when model is manually deleted', function () {
    config()->set('compliance.models', [
        TestModel::class => ['column' => 'created_at'],
    ]);

    $model = TestModel::factory()->deletable()->create();
    $model->complianceScheduleDeletion(now()->addDays(15));

    expect(ComplianceCheck::count())->toBe(1);

    $model->delete();

    expect(ComplianceCheck::count())->toBe(0);
});

it('prune command cleans up orphan compliance checks', function () {
    config()->set('compliance.models', [
        TestModel::class => ['column' => 'created_at'],
    ]);

    // Create a check for a non-existent model
    ComplianceCheck::create([
        'model_type' => TestModel::class,
        'model_id' => 999,
        'deletion_date' => now()->subDay(),
    ]);

    expect(ComplianceCheck::count())->toBe(1);

    $this->artisan(CompliancePruneCommand::class)
        ->assertExitCode(0);

    expect(ComplianceCheck::count())->toBe(0);
});

it('spares the model but removes the check if beforeComplianceDeletion returns false', function () {
    config()->set('compliance.models', [
        TestModel::class => ['column' => 'created_at'],
    ]);

    $model = TestModel::factory()->deletable()->create(['allow_before_deletion' => false]);

    ComplianceCheck::create([
        'model_type' => TestModel::class,
        'model_id' => $model->id,
        'deletion_date' => now()->subDay(),
    ]);

    $this->artisan(CompliancePruneCommand::class)
        ->assertExitCode(0);

    // Model should still exist
    $this->assertDatabaseHas('test_models', ['id' => $model->id]);

    // But the check should be gone
    expect(ComplianceCheck::count())->toBe(0);
});
