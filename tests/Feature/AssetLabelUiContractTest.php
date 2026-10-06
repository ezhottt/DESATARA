<?php

namespace Tests\Feature;

use Tests\TestCase;

final class AssetLabelUiContractTest extends TestCase
{
    public function test_asset_register_exposes_selected_and_filtered_bulk_label_actions(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Surface/Index.vue'));

        $this->assertStringContainsString('Cetak label', $source);
        $this->assertStringContainsString('selectedAssets', $source);
        $this->assertStringContainsString('/assets/labels/prepare', $source);
        $this->assertStringContainsString('filterLabelForm', $source);
        $this->assertStringContainsString('prepareFilteredLabels', $source);
        $this->assertStringContainsString('Cetak semua hasil filter', $source);
        $this->assertStringContainsString('Cetak label aset', file_get_contents(resource_path('js/Pages/Assets/Show.vue')));
    }

    public function test_label_errors_are_visible_on_asset_register_and_detail(): void
    {
        $register = file_get_contents(resource_path('js/Pages/Surface/Index.vue'));
        $detail = file_get_contents(resource_path('js/Pages/Assets/Show.vue'));

        $this->assertStringContainsString('labelForm.errors.asset', $register);
        $this->assertStringContainsString('filterLabelForm.errors.asset', $register);
        $this->assertStringContainsString('filterLabelForm.errors.filter_q', $register);
        $this->assertStringContainsString('labelPrint.errors.asset', $detail);
        $this->assertStringContainsString('Register:', $detail);
        $this->assertStringNotContainsString('Tanpa NUP', $detail);
    }

    public function test_label_sheet_keeps_print_qr_and_operational_presets_without_regulatory_claim(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Assets/Labels.vue'));

        $this->assertStringContainsString('QRCode.toDataURL', $source);
        $this->assertStringContainsString('window.print()', $source);
        $this->assertStringContainsString('@media print', $source);
        $this->assertStringContainsString('bukan klaim ukuran regulasi', $source);
        $this->assertStringContainsString('Small - 50 x 25 mm', $source);
        $this->assertStringContainsString('Medium - 70 x 35 mm', $source);
        $this->assertStringContainsString('Large - 100 x 50 mm', $source);
        $this->assertStringNotContainsString('50 x 30 mm', $source);
        $this->assertStringNotContainsString('60 x 40 mm', $source);
    }
}
