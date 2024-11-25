<?php

namespace Motomedialab\Compliance\Tests\Stubs\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Motomedialab\Compliance\Tests\Stubs\Models\TestModel;

/**
 * @extends Factory<TestModel>
 */
class TestModelFactory extends Factory
{
    protected $model = TestModel::class;

    public function definition(): array
    {
        return [];
    }

    /**
     * Active records sit outside the deletion window
     */
    public function active(): Factory|TestModelFactory
    {

        return $this->state([
            'created_at' => now()->subDays($this->newModel()->complianceDeleteAfterDays())->addDays(30),
        ]);
    }

    /**
     * Records that should be scheduled for deletion
     */
    public function deletable(): Factory|TestModelFactory
    {
        return $this->state([
            'created_at' => now()->subDays($this->newModel()->complianceDeleteAfterDays() + 5),
        ]);
    }
}
