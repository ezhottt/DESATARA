<?php

namespace Tests\Feature\Seeders;

use App\Support\PublicDemoGuard;
use Database\Seeders\DemoSeeder;
use RuntimeException;
use Tests\TestCase;

final class PublicDemoEnvironmentTest extends TestCase
{
    public function test_demo_seeder_rejects_production_even_if_demo_flag_is_set(): void
    {
        $this->app['env'] = 'production';
        config()->set('public_demo.enabled', true);

        $this->expectException(RuntimeException::class);
        (new DemoSeeder)->run();
    }

    public function test_public_demo_refuses_non_private_database(): void
    {
        $this->app['env'] = 'demo';
        config()->set([
            'public_demo.enabled' => true,
            'public_demo.admin_password' => str_repeat('a', 24),
            'app.debug' => false,
            'database.default' => 'pgsql',
            'database.connections.pgsql.url' => 'postgres://user:pass@public.example.com:5432/desatara_demo',
        ]);

        $this->expectException(RuntimeException::class);
        PublicDemoGuard::assertIsolated();
    }

    public function test_public_demo_refuses_wrong_database_name(): void
    {
        $this->app['env'] = 'demo';
        config()->set([
            'public_demo.enabled' => true,
            'public_demo.admin_password' => str_repeat('a', 24),
            'app.debug' => false,
            'database.default' => 'pgsql',
            'database.connections.pgsql.url' => 'postgres://user:pass@postgres.railway.internal:5432/production',
        ]);

        $this->expectException(RuntimeException::class);
        PublicDemoGuard::assertIsolated();
    }

    public function test_public_demo_accepts_only_isolated_configuration(): void
    {
        $this->app['env'] = 'demo';
        $password = str_repeat('R', 24);
        config()->set([
            'public_demo.enabled' => true,
            'public_demo.admin_password' => $password,
            'app.debug' => false,
            'database.default' => 'pgsql',
            'database.connections.pgsql.url' => 'postgres://user:pass@postgres.railway.internal:5432/desatara_demo',
        ]);

        $this->assertSame($password, PublicDemoGuard::assertIsolated());
    }
}
