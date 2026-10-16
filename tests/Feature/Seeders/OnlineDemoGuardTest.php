<?php

namespace Tests\Feature\Seeders;

use App\Support\PublicDemoGuard;
use RuntimeException;
use Tests\TestCase;

final class OnlineDemoGuardTest extends TestCase
{
    public function test_public_demo_refuses_the_wrong_database(): void
    {
        $this->app['env'] = 'demo';
        config()->set([
            'public_demo.enabled' => true,
            'public_demo.admin_password' => str_repeat('S', 24),
            'app.debug' => false,
            'database.default' => 'pgsql',
            'database.connections.pgsql.url' => 'postgresql://user:pass@ep-anything.aws.neon.tech/other_database',
        ]);

        $this->expectException(RuntimeException::class);
        PublicDemoGuard::assertIsolated();
    }

    public function test_public_demo_refuses_an_unapproved_database_host(): void
    {
        $this->app['env'] = 'demo';
        config()->set([
            'public_demo.enabled' => true,
            'public_demo.admin_password' => str_repeat('S', 24),
            'app.debug' => false,
            'database.default' => 'pgsql',
            'database.connections.pgsql.url' => 'postgresql://user:pass@production.example.com/desatara_demo',
        ]);

        $this->expectException(RuntimeException::class);
        PublicDemoGuard::assertIsolated();
    }

    public function test_public_demo_accepts_separately_configured_neon_database(): void
    {
        $this->app['env'] = 'demo';
        $secret = str_repeat('S', 24);
        config()->set([
            'public_demo.enabled' => true,
            'public_demo.admin_password' => $secret,
            'app.debug' => false,
            'database.default' => 'pgsql',
            'database.connections.pgsql.url' => 'postgresql://user:pass@ep-anything.aws.neon.tech/desatara_demo',
        ]);

        $this->assertSame($secret, PublicDemoGuard::assertIsolated());
    }
}
