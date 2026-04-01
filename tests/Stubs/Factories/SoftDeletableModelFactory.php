<?php

namespace Motomedialab\Compliance\Tests\Stubs\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Motomedialab\Compliance\Tests\Stubs\Models\SoftDeletableModel;

/**
 * @extends Factory<SoftDeletableModel>
 */
class SoftDeletableModelFactory extends TestModelFactory
{
    protected $model = SoftDeletableModel::class;
}
