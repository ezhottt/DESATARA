<?php

namespace Tests\Feature;

use Tests\TestCase;

final class AssetLabelComplianceTest extends TestCase
{
    public function test_label_uses_administrative_item_code_and_nup_as_primary_identity(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ProductSurfaceController.php'));
        $page = file_get_contents(resource_path('js/Pages/Assets/Labels.vue'));

        $this->assertStringContainsString("'item_code' => \$asset->classification?->code", $controller);
        $this->assertStringContainsString('Kode barang:', $page);
        $this->assertStringContainsString('NUP:', $page);
        $this->assertStringNotContainsString('Ukuran label', $page);
        $this->assertStringContainsString('QR DESATARA adalah elemen tambahan', $page);
    }
}
