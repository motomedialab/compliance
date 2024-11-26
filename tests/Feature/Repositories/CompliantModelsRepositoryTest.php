<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;
use Motomedialab\Compliance\Repositories\ComplianceModelsRepository;
use Motomedialab\Compliance\Tests\Stubs\Models\TestModel;

beforeEach(function () {
    Schema::create('test_models', function (Blueprint $table) {
        $table->id();

        $table->timestamps();
    });
});

it('can get a count of old records', function () {

    config()->set('compliance.models', [TestModel::class => [
        'column' => 'created_at',
    ]]);

    TestModel::factory()->deletable()->create();

    // call our action
    $collection = (new ComplianceModelsRepository())->getModelsByClassName(TestModel::class);

    // check we got one returned record
    expect($collection)
        ->toBeInstanceOf(LazyCollection::class)
        ->toHaveCount(1);
});
