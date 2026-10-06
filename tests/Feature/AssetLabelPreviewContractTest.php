<?php

namespace Tests\Feature;

use Tests\TestCase;

final class AssetLabelPreviewContractTest extends TestCase
{
    public function test_preview_and_print_share_one_label_markup_with_required_presets_and_copy_control(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Assets/Labels.vue'));

        $this->assertStringContainsString('small:', $source);
        $this->assertStringContainsString("width: '50mm'", $source);
        $this->assertStringContainsString("height: '25mm'", $source);
        $this->assertStringContainsString("qr: '15mm'", $source);
        $this->assertStringContainsString('medium:', $source);
        $this->assertStringContainsString("width: '70mm'", $source);
        $this->assertStringContainsString("height: '35mm'", $source);
        $this->assertStringContainsString('large:', $source);
        $this->assertStringContainsString("width: '100mm'", $source);
        $this->assertStringContainsString("height: '50mm'", $source);
        $this->assertStringContainsString("const size = ref('medium')", $source);
        $this->assertStringContainsString('copies', $source);
        $this->assertStringContainsString('Kode Inventaris', $source);
        $this->assertStringContainsString('inventory_code', $source);
        $this->assertStringContainsString('@media print', $source);
        $this->assertStringContainsString('break-inside:avoid', $source);
        $this->assertStringContainsString('page-break-inside:avoid', $source);
    }

    public function test_small_label_keeps_qr_quiet_zone_and_long_text_wrap_contract(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Assets/Labels.vue'));

        $this->assertStringContainsString('margin: 2', $source);
        $this->assertStringContainsString('overflow-wrap:anywhere', $source);
        $this->assertStringContainsString('word-break:break-word', $source);
        $this->assertStringContainsString('object-fit:contain', $source);
        $this->assertStringNotContainsString('AppShell', $source);
    }

    public function test_asset_form_searches_master_classification_and_does_not_allow_manual_nup(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Assets/Form.vue'));

        $this->assertStringContainsString('classificationQuery', $source);
        $this->assertStringContainsString('filteredClassifications', $source);
        $this->assertStringContainsString('Tanggal perolehan', $source);
        $this->assertStringContainsString('NUP dibuat otomatis', $source);
        $this->assertStringNotContainsString('v-model="form.register_number"', $source);
    }
}
