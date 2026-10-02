<?php

namespace Tests\Feature\Workflow;

use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\ClassificationScheme;
use App\Models\ClassificationVersion;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubjectType;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\TenantOfficial;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowVersion;
use App\Services\Workflow\WorkflowApprovalEngine;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class WorkflowApprovalEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_workflow_version_keeps_definition_and_is_immutable(): void
    {
        $definition = WorkflowDefinition::query()->create(['code' => 'asset.approval', 'name' => 'Asset approval', 'status' => 'active']);
        $version = WorkflowVersion::query()->create(['workflow_definition_id' => $definition->id, 'version' => 1, 'definition' => ['steps' => [['permission' => 'asset.approve']]], 'effective_from' => today(), 'status' => 'published', 'published_at' => now()]);

        $this->assertSame('asset.approve', $version->definition['steps'][0]['permission']);
        $this->expectException(LogicException::class);
        $version->update(['definition' => ['steps' => []]]);
    }

    public function test_cross_tenant_subject_is_rejected_before_workflow_is_created(): void
    {
        [$tenant, $user] = $this->memberContext();
        [, , $foreignAsset] = $this->assetContext();
        $this->publishedWorkflow();

        $this->expectException(AuthorizationException::class);
        app(WorkflowApprovalEngine::class)->start($tenant, $user, 'asset.approval', $foreignAsset, [['permission' => 'asset.approve']]);
    }

    public function test_approval_is_idempotent_updates_workflow_and_finalization_is_immutable(): void
    {
        [$tenant, $requester] = $this->memberContext();
        $approver = User::factory()->create();
        TenantMembership::factory()->active()->for($approver)->for($tenant)->create();
        $this->grant($tenant, $approver, 'asset.approve');
        $asset = $this->assetContext($tenant)[2];
        $this->publishedWorkflow();
        $engine = app(WorkflowApprovalEngine::class);
        $request = $engine->start($tenant, $requester, 'asset.approval', $asset, [['permission' => 'asset.approve']]);

        $engine->act($tenant, $approver, $request, 'approve', 'approval-1');
        $engine->act($tenant, $approver, $request, 'approve', 'approval-1');
        $this->assertDatabaseCount('approval_actions', 1);
        $this->assertSame('approved', $request->refresh()->status);
        $this->assertSame('APPROVED', $request->workflowInstance->refresh()->current_state);

        $engine->finalize($tenant, $approver, $request, 'finalize-1');
        $this->assertSame('FINALIZED', $request->workflowInstance->refresh()->current_state);
        $this->assertSame('finalized', $request->refresh()->status);
        $this->expectException(LogicException::class);
        $request->workflowInstance->update(['current_state' => 'DRAFT']);
    }

    public function test_self_approval_and_expired_authority_are_rejected(): void
    {
        [$tenant, $requester, $membership] = $this->memberContext(true);
        $this->grant($tenant, $requester, 'asset.approve');
        TenantOfficial::query()->create(['tenant_id' => $tenant->id, 'membership_id' => $membership->id, 'official_type' => 'official', 'position_name' => 'Official', 'authority_code' => 'asset.approve', 'authority_scope' => ['asset'], 'valid_from' => today()->subDays(3), 'valid_until' => today()->subDay(), 'status' => 'expired']);
        $asset = $this->assetContext($tenant)[2];
        $this->publishedWorkflow();
        $request = app(WorkflowApprovalEngine::class)->start($tenant, $requester, 'asset.approval', $asset, [['permission' => 'asset.approve', 'authority' => 'asset.approve:asset']]);

        $this->expectException(AuthorizationException::class);
        app(WorkflowApprovalEngine::class)->act($tenant, $requester, $request, 'approve', 'approval-1');
    }

    private function publishedWorkflow(): WorkflowVersion
    {
        SubjectType::query()->firstOrCreate(['code' => 'asset.approval'], ['name' => 'Asset approval', 'model_key' => 'asset', 'status' => 'active']);
        $definition = WorkflowDefinition::query()->create(['code' => 'asset.approval', 'name' => 'Asset approval', 'status' => 'active']);

        return WorkflowVersion::query()->create(['workflow_definition_id' => $definition->id, 'version' => 1, 'definition' => ['steps' => [['permission' => 'asset.approve']]], 'effective_from' => today(), 'status' => 'published', 'published_at' => now()]);
    }

    private function grant(Tenant $tenant, User $user, string $permissionCode): void
    {
        $membership = TenantMembership::query()->where('tenant_id', $tenant->id)->where('user_id', $user->id)->firstOrFail();
        $role = Role::query()->firstOrCreate(['code' => 'workflow-approver', 'scope_type' => 'tenant'], ['name' => 'Workflow approver', 'is_system' => false]);
        $permission = Permission::query()->firstOrCreate(['code' => $permissionCode], ['domain' => 'workflow', 'action' => 'approve']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $membership->roles()->syncWithoutDetaching([$role->id => ['assigned_at' => now()]]);
    }

    private function memberContext(bool $withMembership = false): array
    {
        $tenant = Tenant::factory()->active()->create();
        $user = User::factory()->create();
        $membership = TenantMembership::factory()->active()->for($user)->for($tenant)->create();

        return $withMembership ? [$tenant, $user, $membership] : [$tenant, $user];
    }

    private function assetContext(?Tenant $tenant = null): array
    {
        $tenant ??= Tenant::factory()->active()->create();
        $user = User::factory()->create();
        TenantMembership::factory()->active()->for($user)->for($tenant)->create();
        $scheme = ClassificationScheme::query()->create(['code' => uniqid('S'), 'name' => 'Scheme', 'scope' => 'national', 'status' => 'active']);
        $version = ClassificationVersion::query()->create(['classification_scheme_id' => $scheme->id, 'version_label' => '1', 'effective_from' => today(), 'status' => 'published']);
        $classification = AssetClassification::query()->create(['classification_version_id' => $version->id, 'code' => uniqid('C'), 'name' => 'General', 'level' => 1, 'status' => 'active']);
        $unit = Unit::query()->create(['code' => uniqid('U'), 'name' => 'Unit', 'status' => 'active']);
        $asset = Asset::query()->create(['tenant_id' => $tenant->id, 'classification_id' => $classification->id, 'name' => 'Asset', 'quantity' => 1, 'unit_id' => $unit->id, 'condition' => 'good', 'lifecycle_status' => 'active', 'verification_status' => 'verified', 'created_by' => $user->id, 'updated_by' => $user->id]);

        return [$tenant, $user, $asset];
    }
}
