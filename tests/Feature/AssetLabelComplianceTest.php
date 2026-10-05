<?php

namespace Tests\Feature;

use Tests\TestCase;

final class AssetLabelComplianceTest extends TestCase
{
    public function test_label_uses_regulatory_identity_sources_without_claiming_preset_sizes_are_regulation(): void
    {
        $identity = file_get_contents(app_path('Services/Assets/AssetIdentityService.php'));
        $page = file_get_contents(resource_path('js/Pages/Assets/Labels.vue'));

        $this->assertStringContainsString('$tenant->village_code', $identity);
        $this->assertStringContainsString('$asset->classification->code', $identity);
        $this->assertStringContainsString('$asset->acquisition_date', $identity);
        $this->assertStringContainsString('$asset->register_number', $identity);
        $this->assertStringContainsString('Kode Inventaris', $page);
        $this->assertStringContainsString('NUP:', $page);
        $this->assertStringContainsString('PEMERINTAH DESA', $page);
        $this->assertStringContainsString('bukan klaim ukuran regulasi', $page);
        $this->assertStringContainsString('UUID publik aset yang stabil', $page);
    }
}
