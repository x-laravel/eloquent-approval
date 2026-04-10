<?php

namespace XLaravel\EloquentApproval\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XLaravel\EloquentApproval\ApprovalFactoryStates;
use XLaravel\EloquentApproval\Tests\Models\Entity;

class EntityFactory extends Factory
{
    use ApprovalFactoryStates;

    protected $model = Entity::class;

    public function definition(): array
    {
        return [
            'attr_1' => $this->faker->word,
            'attr_2' => $this->faker->word,
            'attr_3' => $this->faker->word,
        ];
    }
}