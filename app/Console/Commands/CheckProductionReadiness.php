<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckProductionReadiness extends Command
{
    protected $signature = 'desatara:check-readiness {--production : Validate production-only requirements}';

    protected $description = 'Validate deployment configuration without printing secret values';

    public function handle(): int
    {
        $checks = [
            'APP_ENV is set' => filled(config('app.env')),
            'APP_DEBUG is disabled' => config('app.debug') === false,
            'APP_KEY is set' => filled(config('app.key')),
            'APP_URL is valid' => filter_var(config('app.url'), FILTER_VALIDATE_URL) !== false,
            'PostgreSQL is configured' => config('database.default') === 'pgsql',
            'Private filesystem is configured' => in_array(config('filesystems.default'), ['private', 'local'], true)
                && ! str_contains((string) config('filesystems.disks.'.config('filesystems.default').'.root'), 'app/public'),
            'Mail transport is configured' => filled(config('mail.default')),
        ];

        if ($this->option('production')) {
            $checks['APP_ENV is production'] = config('app.env') === 'production';
            $checks['APP_URL uses HTTPS'] = parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https';
            $checks['Cache is not array-backed'] = config('cache.default') !== 'array';
            $checks['Session is not array-backed'] = config('session.driver') !== 'array';
            $checks['Queue is not synchronous'] = config('queue.default') !== 'sync';
            $checks['TLS evidence is recorded'] = filled(config('operations.readiness.tls_evidence'));
            $checks['Backup evidence is recorded'] = filled(config('operations.readiness.backup_evidence'));
            $checks['Restore smoke evidence is recorded'] = filled(config('operations.readiness.restore_evidence'));
            $checks['Queue worker evidence is recorded'] = filled(config('operations.readiness.queue_evidence'));
            $checks['Scheduler evidence is recorded'] = filled(config('operations.readiness.scheduler_evidence'));
        }

        $failed = false;

        foreach ($checks as $label => $passed) {
            $this->line(($passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>')."  {$label}");
            $failed = $failed || ! $passed;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
