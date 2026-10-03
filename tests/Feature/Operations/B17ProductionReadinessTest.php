<?php

namespace Tests\Feature\Operations;

use Tests\TestCase;

class B17ProductionReadinessTest extends TestCase
{
    public function test_health_endpoint_is_available_without_exposing_configuration(): void
    {
        $this->get('/up')->assertOk()->assertDontSee('APP_KEY');
    }

    public function test_production_readiness_command_fails_closed_without_operational_evidence(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:synthetic-test-key',
            'app.url' => 'https://desatara.example',
            'database.default' => 'pgsql',
            'filesystems.default' => 'local',
            'filesystems.disks.local.root' => storage_path('app/private'),
            'mail.default' => 'log',
            'cache.default' => 'database',
            'session.driver' => 'database',
            'queue.default' => 'database',
        ]);

        $this->artisan('desatara:check-readiness', ['--production' => true])
            ->expectsOutputToContain('PASS  APP_ENV is production')
            ->expectsOutputToContain('FAIL  Restore smoke evidence is recorded')
            ->doesntExpectOutputToContain('synthetic-test-key')
            ->assertExitCode(1);
    }

    public function test_production_readiness_command_passes_with_required_operational_evidence(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:synthetic-test-key',
            'app.url' => 'https://desatara.example',
            'database.default' => 'pgsql',
            'filesystems.default' => 'local',
            'filesystems.disks.local.root' => storage_path('app/private'),
            'mail.default' => 'log',
            'cache.default' => 'database',
            'session.driver' => 'database',
            'queue.default' => 'database',
            'operations.readiness.tls_evidence' => 'tls-check-2026-10-03',
            'operations.readiness.backup_evidence' => 'backup-check-2026-10-03',
            'operations.readiness.restore_evidence' => 'restore-check-2026-10-03',
            'operations.readiness.queue_evidence' => 'queue-check-2026-10-03',
            'operations.readiness.scheduler_evidence' => 'scheduler-check-2026-10-03',
        ]);

        $this->artisan('desatara:check-readiness', ['--production' => true])
            ->expectsOutputToContain('PASS  Restore smoke evidence is recorded')
            ->doesntExpectOutputToContain('synthetic-test-key')
            ->assertExitCode(0);
    }

    public function test_production_readiness_command_fails_closed_for_insecure_configuration(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => true,
            'app.key' => 'base64:synthetic-test-key',
            'app.url' => 'http://desatara.example',
            'database.default' => 'pgsql',
            'filesystems.default' => 'local',
            'filesystems.disks.local.root' => storage_path('app/private'),
            'mail.default' => 'log',
            'cache.default' => 'array',
            'session.driver' => 'array',
            'queue.default' => 'sync',
        ]);

        $this->artisan('desatara:check-readiness', ['--production' => true])
            ->expectsOutputToContain('FAIL  APP_DEBUG is disabled')
            ->expectsOutputToContain('FAIL  APP_URL uses HTTPS')
            ->assertExitCode(1);
    }
}
