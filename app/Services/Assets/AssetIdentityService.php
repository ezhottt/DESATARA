<?php

namespace App\Services\Assets;

use App\Models\Asset;
use App\Models\NumberingSequence;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AssetIdentityService
{
    public function nextNup(Tenant $tenant, int $classificationId, int $acquisitionYear): string
    {
        if ($classificationId < 1) {
            throw ValidationException::withMessages(['classification_id' => 'Klasifikasi barang tidak valid.']);
        }
        if ($acquisitionYear < 1900 || $acquisitionYear > 9999) {
            throw ValidationException::withMessages(['acquisition_date' => 'Tahun perolehan tidak valid.']);
        }

        $periodKey = $classificationId.':'.$acquisitionYear;

        return DB::transaction(function () use ($tenant, $classificationId, $acquisitionYear, $periodKey): string {
            $existingMax = Asset::query()
                ->where('tenant_id', $tenant->id)
                ->where('classification_id', $classificationId)
                ->where('acquisition_year', $acquisitionYear)
                ->whereNotNull('register_number')
                ->pluck('register_number')
                ->filter(fn ($value) => is_string($value) && ctype_digit($value))
                ->map(fn (string $value) => (int) $value)
                ->max() ?? 0;

            DB::table('numbering_sequences')->insertOrIgnore([
                'tenant_id' => $tenant->id,
                'sequence_type' => 'asset_nup',
                'period_key' => $periodKey,
                'last_number' => $existingMax,
                'lock_version' => 1,
                'updated_at' => now(),
            ]);

            $sequence = NumberingSequence::query()
                ->where('tenant_id', $tenant->id)
                ->where('sequence_type', 'asset_nup')
                ->where('period_key', $periodKey)
                ->lockForUpdate()
                ->firstOrFail();

            $next = max($sequence->last_number, $existingMax) + 1;
            $sequence->forceFill([
                'last_number' => $next,
                'lock_version' => $sequence->lock_version + 1,
                'updated_at' => now(),
            ])->save();

            return str_pad((string) $next, 3, '0', STR_PAD_LEFT);
        });
    }

    public function inventoryCode(Tenant $tenant, Asset $asset): string
    {
        return $this->labelPayload($tenant, $asset)['inventory_code'];
    }

    /**
     * @return array{
     *   uuid:string,name:string,village_name:string,village_code:string,item_code:string,
     *   acquisition_year:int,register_number:string,inventory_code:string
     * }
     */
    public function labelPayload(Tenant $tenant, Asset $asset): array
    {
        abort_unless((int) $asset->tenant_id === (int) $tenant->id, 404);

        $villageCode = trim((string) $tenant->village_code);
        if (! $this->isAdministrativeVillageCode($villageCode)) {
            throw ValidationException::withMessages([
                'asset' => 'Label belum dapat dicetak karena Kode Wilayah Desa belum tersedia.',
            ]);
        }

        $villageName = $this->labelVillageName((string) $tenant->name);
        if ($villageName === '') {
            throw ValidationException::withMessages([
                'asset' => 'Label belum dapat dicetak karena Nama Desa belum tersedia.',
            ]);
        }

        $classification = $asset->classification;
        $itemCode = trim((string) $classification?->code);
        if (
            $classification === null
            || (int) $classification->level < 7
            || ! $this->isPrintableAdministrativeItemCode($itemCode)
        ) {
            throw ValidationException::withMessages([
                'asset' => 'Label belum dapat dicetak karena Kode Barang belum tersedia.',
            ]);
        }

        if (blank($asset->name)) {
            throw ValidationException::withMessages([
                'asset' => 'Label belum dapat dicetak karena Nama Barang belum tersedia.',
            ]);
        }

        if ($asset->acquisition_date === null) {
            throw ValidationException::withMessages([
                'asset' => 'Label belum dapat dicetak karena Tahun Perolehan belum tersedia.',
            ]);
        }

        $year = CarbonImmutable::parse($asset->acquisition_date)->year;

        $storedRegister = trim((string) $asset->register_number);
        if ($storedRegister === '') {
            throw ValidationException::withMessages([
                'asset' => 'Label belum dapat dicetak karena Nomor Register belum tersedia.',
            ]);
        }
        if (! ctype_digit($storedRegister) || (int) $storedRegister < 1) {
            throw ValidationException::withMessages([
                'asset' => 'Label belum dapat dicetak karena Nomor Register tidak valid.',
            ]);
        }
        if ((float) $asset->quantity !== 1.0) {
            throw ValidationException::withMessages([
                'asset' => 'Label fisik hanya dapat dicetak untuk record aset individual dengan jumlah 1 unit.',
            ]);
        }

        $registerNumber = str_pad($storedRegister, 3, '0', STR_PAD_LEFT);

        return [
            'uuid' => (string) $asset->uuid,
            'name' => (string) $asset->name,
            'village_name' => $villageName,
            'village_code' => $villageCode,
            'item_code' => $itemCode,
            'acquisition_year' => $year,
            'register_number' => $registerNumber,
            'inventory_code' => implode(' / ', [$villageCode, $itemCode, (string) $year, $registerNumber]),
        ];
    }

    private function isAdministrativeVillageCode(string $code): bool
    {
        return preg_match('/^\d{2}\.\d{2}\.\d{2}\.\d{4}$/', $code) === 1;
    }

    private function isPrintableAdministrativeItemCode(string $code): bool
    {
        return preg_match('/^\d+\.\d+\.\d+\.\d{2}\.\d{2}\.\d{2}\.\d{3}$/', $code) === 1;
    }

    private function labelVillageName(string $name): string
    {
        $normalized = trim($name);
        $normalized = preg_replace('/^desa\s+/iu', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+demo$/iu', '', $normalized) ?? $normalized;

        return Str::upper(trim($normalized));
    }
}
