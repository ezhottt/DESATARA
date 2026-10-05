<?php

namespace Tests\Feature;

use Tests\TestCase;

final class AssetNupContractTest extends TestCase
{
    public function test_current_asset_ui_and_label_use_nup_terminology(): void
    {
        $form = file_get_contents(resource_path('js/Pages/Assets/Form.vue'));
        $label = file_get_contents(resource_path('js/Pages/Assets/Labels.vue'));

        $this->assertStringContainsString('NUP', $form);
        $this->assertStringNotContainsString('Nomor register<input', $form);
        $this->assertStringContainsString('NUP:', $label);
        $this->assertStringNotContainsString('No. register:', $label);
    }

    public function test_legacy_import_keeps_register_headers_as_compatibility_aliases_and_accepts_nup(): void
    {
        $reader = file_get_contents(app_path('Services/Interoperability/LegacyAssetFileReader.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ProductSurfaceController.php'));

        $this->assertStringContainsString("\$raw['nup']", $reader);
        $this->assertStringContainsString("'NUP'", $controller);
        $this->assertStringContainsString("\$raw['nomor_register']", $reader);
    }
}
