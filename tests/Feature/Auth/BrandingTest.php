<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class BrandingTest extends TestCase
{
    public function test_final_brand_assets_exist_with_web_friendly_names(): void
    {
        foreach ([
            'desatara-logo.png',
            'desatara-logo-horizontal.png',
            'desatara-symbol.png',
            'desatara-symbol-black.png',
            'desatara-symbol-white.png',
            'desatara-palette.png',
        ] as $asset) {
            $this->assertFileExists(public_path('logo/'.$asset));
        }
    }

    public function test_auth_components_reference_the_final_horizontal_logo(): void
    {
        foreach (['Login.vue', 'ForgotPassword.vue', 'ResetPassword.vue'] as $component) {
            $source = file_get_contents(resource_path('js/Pages/Auth/'.$component));

            $this->assertIsString($source);
            $this->assertStringContainsString('/logo/desatara-logo-horizontal.png', $source);
        }
    }

    public function test_application_shell_exposes_desatara_favicon(): void
    {
        $this->withoutVite();

        $this->get('/login')
            ->assertOk()
            ->assertSee('logo/desatara-symbol.png', false)
            ->assertSee('rel="icon"', false)
            ->assertSee('name="theme-color" content="#0B2E5B"', false);
    }
}
