<?php

namespace Tests\Feature;

use Tests\TestCase;

final class AssetNupContractTest extends TestCase
{
    public function test_asset_label_uses_register_terminology_while_existing_nup_domain_compatibility_remains(): void
    {
        $form = file_get_contents(resource_path('js/Pages/Assets/Form.vue'));
        $label = file_get_contents(resource_path('js/Pages/Assets/Labels.vue'));

        $this->assertStringContainsString('NUP', $form);
        $this->assertStringContainsString('Register:', $label);
        $this->assertStringContainsString('label.register_number', $label);
        $this->assertStringNotContainsString('NUP:', $label);
        $this->assertStringNotContainsString('label.nup', $label);
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
