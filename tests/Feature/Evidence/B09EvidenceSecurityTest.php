<?php

namespace Tests\Feature\Evidence;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\Evidence\DocumentService;
use App\Services\Evidence\QrTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class B09EvidenceSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_is_private_and_tenant_scoped(): void
    {
        Storage::fake('private');
        [$tenant, $user, $asset] = $this->assetFixture();
        $document = app(DocumentService::class)->store($tenant, $user, $asset, UploadedFile::fake()->createWithContent('evidence.pdf', "%PDF-1.7\ncontent"), 'legal');

        $this->assertSame('private', $document->visibility);
        $this->assertStringStartsNotWith('/', $document->storage_path);
        Storage::disk('private')->assertExists($document->storage_path);
        $this->assertNotNull($document->uuid);
        $this->assertStringNotContainsString('storage/app/public', $document->storage_path);
    }

    public function test_upload_rejects_mime_spoofing_and_path_traversal_filename(): void
    {
        [$tenant, $user, $asset] = $this->assetFixture();
        $service = app(DocumentService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->store($tenant, $user, $asset, UploadedFile::fake()->createWithContent('../secret.php', "%PDF-1.7\ncontent"), 'legal');
    }

    public function test_document_cannot_cross_tenant_asset_boundary(): void
    {
        [$tenant, $user, $asset] = $this->assetFixture();
        $otherTenant = Tenant::factory()->active()->create();
        $otherUser = User::factory()->create();

        $this->expectException(NotFoundHttpException::class);
        app(DocumentService::class)->store($otherTenant, $otherUser, $asset, UploadedFile::fake()->createWithContent('evidence.pdf', "%PDF-1.7\ncontent"), 'legal');
    }

    public function test_qr_is_opaque_revocable_rotatable_and_public_projection_is_allowlisted(): void
    {
        [$tenant, $user, $asset] = $this->assetFixture();
        $service = app(QrTokenService::class);
        [$raw, $token] = $service->issue($tenant, $user, $asset);

        $this->assertGreaterThan(32, strlen($raw));
        $this->assertNull($token->getAttribute('token'));
        $this->assertSame($asset->id, $service->resolve($raw)?->asset_id);
        $this->assertSame(['asset_code', 'name', 'classification', 'status'], array_keys($service->publicProjection($raw)));

        $service->revoke($token);
        $this->assertNull($service->resolve($raw));
        [$rotatedRaw, $rotated] = $service->rotate($tenant, $user, $asset, $token);
        $this->assertNotSame($raw, $rotatedRaw);
        $this->assertSame('active', $rotated->status);
        $this->assertSame($rotated->id, $service->resolve($rotatedRaw)?->id);
    }

    private function assetFixture(): array
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $scheme = ClassificationScheme::query()->create(['code' => uniqid('S'), 'name' => 'Scheme', 'scope' => 'national', 'status' => 'active']);
        $version = ClassificationVersion::query()->create(['classification_scheme_id' => $scheme->id, 'version_label' => '1', 'effective_from' => today(), 'status' => 'published']);
        $classification = AssetClassification::query()->create(['classification_version_id' => $version->id, 'code' => '01', 'name' => 'General', 'level' => 1, 'status' => 'active']);
        $unit = Unit::query()->create(['code' => uniqid('U'), 'name' => 'Unit', 'status' => 'active']);
        $asset = Asset::query()->create(['tenant_id' => $tenant->id, 'classification_id' => $classification->id, 'asset_code' => 'PUB-001', 'name' => 'Public Asset', 'quantity' => 1, 'unit_id' => $unit->id, 'condition' => 'good', 'lifecycle_status' => 'active', 'verification_status' => 'verified', 'created_by' => $user->id, 'updated_by' => $user->id]);

        return [$tenant, $user, $asset];
    }
}
