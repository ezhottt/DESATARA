<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ProductSurfaceUiContractTest extends TestCase
{
    public function test_product_surfaces_use_template_unwrapped_surface_and_localized_labels(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Surface/Index.vue'));

        $template = substr($source, strpos($source, '<template>'));
        $this->assertStringNotContainsString("surface.value==='", $template);
        $this->assertStringContainsString("const pageTitle=()=>surface.value==='master-data'", $source);
        $this->assertStringContainsString("fair:'Rusak ringan'", $source);
        $this->assertStringNotContainsString('Ã', $source);
        $this->assertStringNotContainsString('Â', $source);
    }

    public function test_search_localizes_fair_and_responsible_party_type(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Search.vue'));

        $this->assertStringContainsString("fair: 'Rusak ringan'", $source);
        $this->assertStringContainsString("organizational_unit: 'Unit organisasi'", $source);
        $this->assertStringNotContainsString('{{ party.party_type }}', $source);
    }
}
