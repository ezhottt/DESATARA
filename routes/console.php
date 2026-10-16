<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('desatara:check-online-demo', function (): void {
    \App\Support\PublicDemoGuard::assertIsolated();
    $this->info('Konfigurasi lingkungan demo telah memenuhi pemeriksaan isolasi.');
})->purpose('Verify isolated Neon demo environment before any database changes');

Artisan::command('desatara:seed-online-demo', function (): void {
    \App\Support\PublicDemoGuard::assertIsolated();

    if (\App\Models\Tenant::query()->where('village_code', 'DEMO-CIKADU')->exists()) {
        $this->info('Data demo sudah ada. Seeder tidak dijalankan ulang agar perubahan pengguna tidak tertimpa.');
        return;
    }

    $this->call('db:seed', [
        '--class' => \Database\Seeders\DemoSeeder::class,
        '--force' => true,
    ]);
    $this->info('Data sintetis demo berhasil dipersiapkan.');
})->purpose('Install demo fixtures only into the isolated Neon demo database');
