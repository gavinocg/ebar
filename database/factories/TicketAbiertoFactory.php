<?php

namespace Database\Factories;

use App\Models\TicketAbierto;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketAbiertoFactory extends Factory
{
    protected $model = TicketAbierto::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'descripcion' => fake()->sentence(),
        ];
    }
}
