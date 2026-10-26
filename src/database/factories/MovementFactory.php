<?php

namespace Database\Factories;

use App\Enums\MovementAction;
use App\Models\Indent;
use App\Models\Movement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movement>
 */
class MovementFactory extends Factory
{
    protected $model = Movement::class;

    public function definition(): array
    {
        return [
            'indent_id' => Indent::factory(),
            'from' => fn () => User::factory()->create()->cpf,
            'to' => fn () => User::factory()->create()->cpf,
            'action' => MovementAction::SUBMIT->value,
            'is_rejected' => false,
            'remark' => fake()->sentence(),
        ];
    }

    /**
     * Movement marked as rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn () => [
            'action' => MovementAction::REJECT->value,
            'is_rejected' => true,
            'remark' => 'Work details insufficient; please revise estimates.',
        ]);
    }

    /**
     * Movement marked as approved.
     */
    public function approved(): static
    {
        return $this->state(fn () => [
            'action' => MovementAction::APPROVE->value,
            'is_rejected' => false,
            'remark' => 'Concurrence granted. Forwarding for execution.',
        ]);
    }

    /**
     * Movement marked as forwarded.
     */
    public function forwarded(): static
    {
        return $this->state(fn () => [
            'action' => MovementAction::FORWARD->value,
            'is_rejected' => false,
            'remark' => 'Forwarded to next approval authority.',
        ]);
    }
}
