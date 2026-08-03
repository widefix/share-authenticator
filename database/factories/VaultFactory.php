<?php

namespace Database\Factories;

use App\Models\Vault;
use App\Models\Workspace;
use App\Services\Shared\NameNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vault> */
class VaultFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'workspace_id' => Workspace::factory(),
            'name' => $name,
            'normalized_name' => app(NameNormalizer::class)->normalize($name),
            'archived_at' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn () => ['archived_at' => now()]);
    }
}
