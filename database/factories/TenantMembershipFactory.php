<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TenantMembership> */
class TenantMembershipFactory extends Factory
{
    protected $model = TenantMembership::class;

    public function definition(): array
    {
        return ['tenant_id' => Tenant::factory(), 'user_id' => User::factory(), 'status' => 'invited'];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active', 'joined_at' => now(), 'valid_from' => now()->subMinute(), 'valid_until' => null]);
    }
}
