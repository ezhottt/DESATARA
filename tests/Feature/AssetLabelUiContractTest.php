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

    public function test_label_sheet_keeps_print_and_qr_without_claiming_regulatory_sticker_size(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Assets/Labels.vue'));

        $this->assertStringContainsString('QRCode.toDataURL', $source);
        $this->assertStringContainsString('window.print()', $source);
        $this->assertStringContainsString('@media print', $source);
        $this->assertStringContainsString('Ukuran fisik label mengikuti kebutuhan media/printer desa', $source);
        $this->assertStringNotContainsString('50 x 30 mm', $source);
        $this->assertStringNotContainsString('60 x 40 mm', $source);
    }
}
