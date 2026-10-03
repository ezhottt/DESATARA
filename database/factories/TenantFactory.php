<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Tenant> */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return ['uuid' => (string) Str::uuid(), 'name' => fake()->company().' Desa', 'province' => 'Jawa Barat', 'regency' => 'Cianjur', 'district' => 'Cikadu', 'timezone' => 'Asia/Jakarta', 'locale' => 'id', 'status' => 'pending'];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active', 'activated_at' => now(), 'suspended_at' => null]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => 'suspended', 'activated_at' => now()->subDay(), 'suspended_at' => now()]);
    }
}
