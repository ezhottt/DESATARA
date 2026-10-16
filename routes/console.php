<?php

use App\Models\Tenant;
use App\Support\PublicDemoGuard;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('desatara:check-online-demo', function (): void {
    PublicDemoGuard::assertIsolated();
    $this->info('Konfigurasi lingkungan demo telah memenuhi pemeriksaan isolasi.');
})->purpose('Verify isolated Neon demo environment before any database changes');

Artisan::command('desatara:seed-online-demo', function (): void {
    PublicDemoGuard::assertIsolated();

    if (Tenant::query()->where('village_code', 'DEMO-CIKADU')->exists()) {
        $this->info('Data demo sudah ada. Seeder tidak dijalankan ulang agar perubahan pengguna tidak tertimpa.');

        return;
    }

    $this->call('db:seed', [
        '--class' => DemoSeeder::class,
        '--force' => true,
    ]);
    $this->info('Data sintetis demo berhasil dipersiapkan.');
})->purpose('Install demo fixtures only into the isolated Neon demo database');
