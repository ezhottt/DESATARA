<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_root_renders_desatara_foundation_through_inertia(): void
    {
        $this->withoutVite();

        $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Foundation')
                ->where('product', 'DESATARA')
            );
    }
}
