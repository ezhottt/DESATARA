<?php

namespace Tests\Feature;

use Tests\TestCase;

final class AssetLabelUiContractTest extends TestCase
{
    public function test_asset_register_exposes_bulk_label_selection(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Surface/Index.vue'));

        $this->assertStringContainsString('Cetak label', $source);
        $this->assertStringContainsString('selectedAssets', $source);
        $this->assertStringContainsString('/assets/labels/prepare', $source);
        $this->assertStringContainsString('Cetak label aset', file_get_contents(resource_path('js/Pages/Assets/Show.vue')));
    }

    public function test_label_sheet_supports_qr_and_two_physical_sizes(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Assets/Labels.vue'));

        $this->assertStringContainsString('50x30', $source);
        $this->assertStringContainsString('60x40', $source);
        $this->assertStringContainsString('QRCode.toDataURL', $source);
        $this->assertStringContainsString('window.print()', $source);
        $this->assertStringContainsString('@media print', $source);
    }
}
