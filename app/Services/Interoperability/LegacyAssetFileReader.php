<?php

namespace App\Services\Interoperability;

use App\Models\AssetClassification;
use App\Models\AssetLocation;
use App\Models\FundingSource;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

final class LegacyAssetFileReader
{
    /** @return list<array<string, mixed>> */
    public function read(UploadedFile $file, Tenant $tenant): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv' => $this->readCsv($file->getRealPath(), $tenant),
            'xlsx' => $this->readXlsx($file->getRealPath(), $tenant),
            'xls' => throw new RuntimeException('Format .xls lama belum didukung. Simpan ulang file sebagai .xlsx atau .csv.'),
            default => throw new RuntimeException('Gunakan file CSV atau XLSX.'),
        };
    }

    /** @return list<array<string, mixed>> */
    private function readCsv(string $path, Tenant $tenant): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('File CSV tidak dapat dibaca.');
        }

        try {
            $headers = fgetcsv($handle);
            if (! is_array($headers)) {
                throw new RuntimeException('File CSV tidak memiliki header.');
            }

            $rows = [];
            while (($values = fgetcsv($handle)) !== false) {
                if ($values === [null]) {
                    continue;
                }
                $rows[] = $this->map($headers, $values, $tenant);
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /** @return list<array<string, mixed>> */
    private function readXlsx(string $path, Tenant $tenant): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi ZIP PHP diperlukan untuk membaca XLSX.');
        }
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File XLSX tidak valid.');
        }

        try {
            $shared = $this->sharedStrings($zip);
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if (! is_string($xml)) {
                throw new RuntimeException('Worksheet pertama XLSX tidak ditemukan.');
            }
            $sheet = simplexml_load_string($xml);
            if ($sheet === false) {
                throw new RuntimeException('Worksheet XLSX tidak dapat dibaca.');
            }

            $matrix = [];
            foreach ($sheet->sheetData->row as $row) {
                $values = [];
                foreach ($row->c as $cell) {
                    $type = (string) $cell['t'];
                    $value = (string) $cell->v;
                    if ($type === 's') {
                        $value = $shared[(int) $value] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $value = (string) $cell->is->t;
                    }
                    $values[] = $value;
                }
                $matrix[] = $values;
            }

            $headers = array_shift($matrix) ?? [];

            return array_values(array_map(fn (array $values) => $this->map($headers, $values, $tenant), array_filter($matrix, fn (array $row) => $row !== [])));
        } finally {
            $zip->close();
        }
    }

    /** @return list<string> */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if (! is_string($xml)) {
            return [];
        }
        $document = simplexml_load_string($xml);
        if ($document === false) {
            return [];
        }

        $strings = [];
        foreach ($document->si as $item) {
            $strings[] = isset($item->t) ? (string) $item->t : implode('', array_map(fn ($run) => (string) $run->t, iterator_to_array($item->r)));
        }

        return $strings;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<string|null>  $values
     * @return array<string, mixed>
     */
    private function map(array $headers, array $values, Tenant $tenant): array
    {
        $raw = [];
        foreach ($headers as $index => $header) {
            $raw[$this->header((string) $header)] = $values[$index] ?? null;
        }

        $classificationCode = $raw['kode_klasifikasi'] ?? $raw['klasifikasi'] ?? null;
        $unitCode = $raw['satuan'] ?? 'UNIT';
        $fundingCode = $raw['sumber_dana'] ?? null;
        $locationCode = $raw['lokasi'] ?? null;

        return array_filter([
            'name' => $this->text($raw['nama_barang'] ?? $raw['nama_aset'] ?? null),
            'asset_code' => $this->text($raw['kode_barang'] ?? $raw['kode_aset'] ?? null),
            'register_number' => $this->text($raw['nup'] ?? $raw['nomor_urut_pendaftaran'] ?? $raw['nomor_register'] ?? $raw['no_register'] ?? null),
            'classification_id' => $classificationCode ? AssetClassification::query()->where('code', trim((string) $classificationCode))->where('status', 'active')->value('id') : null,
            'acquisition_year' => $this->integer($raw['tahun_perolehan'] ?? null),
            'acquisition_origin' => $this->text($raw['asal_perolehan'] ?? null),
            'acquisition_value' => $this->money($raw['harga_perolehan'] ?? $raw['nilai_perolehan'] ?? null),
            'quantity' => $this->decimal($raw['jumlah'] ?? 1),
            'unit_id' => Unit::query()->where('code', strtoupper(trim((string) $unitCode)))->where('status', 'active')->value('id'),
            'funding_source_id' => $fundingCode ? FundingSource::query()->where('tenant_id', $tenant->id)->where('code', trim((string) $fundingCode))->value('id') : null,
            'current_location_id' => $locationCode ? AssetLocation::query()->where('tenant_id', $tenant->id)->where('code', trim((string) $locationCode))->value('id') : null,
            'condition' => $this->condition($raw['kondisi'] ?? null),
            'lifecycle_status' => 'active',
            'verification_status' => 'unverified',
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function header(string $value): string
    {
        $value = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value));
        $value = preg_replace('/[^a-z0-9]+/u', '_', $value) ?? $value;

        return trim($value, '_');
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function integer(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function decimal(mixed $value): float
    {
        $normalized = str_replace(',', '.', trim((string) $value));

        return is_numeric($normalized) ? (float) $normalized : 1.0;
    }

    private function money(mixed $value): ?float
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }
        $normalized = preg_replace('/[^0-9,-]/', '', $value) ?? '';
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function condition(mixed $value): string
    {
        return match (strtolower(trim((string) $value))) {
            'baik', 'good' => 'good',
            'rusak ringan', 'cukup', 'fair' => 'fair',
            'rusak', 'rusak berat', 'damaged' => 'damaged',
            default => 'good',
        };
    }
}
