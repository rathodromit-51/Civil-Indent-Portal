<?php

namespace Database\Factories;

use App\Models\Indent;
use App\Models\IndentParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndentParticipant>
 */
class IndentParticipantFactory extends Factory
{
    protected $model = IndentParticipant::class;

    public function definition(): array
    {
        return [
            'indent_id' => Indent::factory(),
            'cpf' => fn () => User::factory()->create()->cpf,
        ];
    }
}
