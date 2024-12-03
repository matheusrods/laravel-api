<?php

// database/factories/CollaboratorFactory.php
namespace Database\Factories;

use App\Models\Collaborator;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollaboratorFactory extends Factory
{
    protected $model = Collaborator::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'cpf' => $this->faker->unique()->numerify('###########'),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
