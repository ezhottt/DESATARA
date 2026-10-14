<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The dedicated demo environment must pass isolation checks before migrations or seed changes.
Artisan::command('desatara:check-public-demo', function (): void {
    \App\Support\PublicDemoGuard::assertIsolated();
    $this->info('Public demo runtime isolation checks passed.');
})->purpose('Verify online demo runtime isolation before any migration');

Artisan::command('desatara:bootstrap-public-demo', function (): void {
    \App\Support\PublicDemoGuard::assertIsolated();
    if (\App\Models\Tenant::query()->where('village_code', 'DEMO-CIKADU')->exists()) {
        $this->info('Demo tenant already exists. Existing records were preserved.');
        return;
    }

    $this->call('db:seed', ['--class' => \Database\Seeders\DemoSeeder::class, '--force' => true]);
    $this->info('Synthetic demo dataset installed.');
})->purpose('Seed a fresh isolated demo DB without overwriting later demo changes');
