<?php

namespace App\Services\Workflow;

use App\Models\ApprovalAction;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\SubjectType;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTransition;
use App\Models\WorkflowVersion;
use App\Support\Authority\AuthoritySnapshotter;
use App\Support\Authorization\PermissionResolver;
use App\Support\Tenancy\ActiveTenantMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class WorkflowApprovalEngine
{
    public function __construct(private ActiveTenantMembership $memberships, private PermissionResolver $permissions, private AuthoritySnapshotter $snapshots) {}

    /** @param list<array{permission?:string,authority?:string|array<string,mixed>}> $steps */
    public function start(Tenant $tenant, User $actor, string $workflow, int|Model $subject, array $steps = []): ApprovalRequest
    {
        $this->member($tenant, $actor);
        $subjectType = SubjectType::query()->where('code', $workflow)->where('status', 'active')->firstOrFail();
        $subjectId = $this->subjectId($tenant, $subjectType, $subject);
        $definition = WorkflowDefinition::query()->where('code', $workflow)->where('status', 'active')->firstOrFail();
        $version = WorkflowVersion::query()->where('workflow_definition_id', $definition->id)->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', today()))
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', today()))
            ->orderByDesc('version')->firstOrFail();
        $definitionData = json_decode((string) $version->getRawOriginal('definition'), true);
        $steps = $steps ?: ($definitionData['steps'] ?? []);
        if ($steps === []) {
            throw new RuntimeException('At least one approval step is required.');
        }

        return DB::transaction(function () use ($tenant, $actor, $subjectType, $version, $subjectId, $steps) {
            $membership = $this->memberships->query($actor->id, $tenant->id)->firstOrFail();
            $now = now();
            $instance = WorkflowInstance::query()->create(['tenant_id' => $tenant->id, 'workflow_version_id' => $version->id, 'subject_type_id' => $subjectType->id, 'subject_id' => $subjectId, 'current_state' => 'SUBMITTED', 'started_by' => $actor->id, 'started_at' => $now]);
            WorkflowTransition::query()->create(['tenant_id' => $tenant->id, 'workflow_instance_id' => $instance->id, 'from_state' => null, 'to_state' => 'SUBMITTED', 'action' => 'submit', 'actor_membership_id' => $membership->id, 'occurred_at' => $now]);
            $request = ApprovalRequest::query()->create(['tenant_id' => $tenant->id, 'workflow_instance_id' => $instance->id, 'subject_type_id' => $subjectType->id, 'subject_id' => $subjectId, 'status' => 'pending', 'requested_by' => $actor->id, 'submitted_at' => $now]);
            foreach ($steps as $i => $step) {
                $permissionId = isset($step['permission']) ? Permission::query()->where('code', $step['permission'])->valueOrFail('id') : null;
                $authority = $step['authority'] ?? null;
                $parts = is_string($authority) ? explode(':', $authority, 2) : [];
                ApprovalStep::query()->create(['tenant_id' => $tenant->id, 'approval_request_id' => $request->id, 'sequence' => $i + 1, 'required_permission_id' => $permissionId, 'authority_requirement' => is_string($authority) ? ['code' => $parts[0], 'scope' => $parts[1] ?? ''] : $authority, 'status' => $i === 0 ? 'pending' : 'waiting']);
            }

            return $request->load(['steps', 'workflowInstance']);
        });
    }

    public function act(Tenant $tenant, User $actor, ApprovalRequest $request, string $action, string $idempotencyKey, ?string $notes = null): ApprovalRequest
    {
        if (! in_array($action, ['approve', 'reject'], true) || $idempotencyKey === '') {
            throw new RuntimeException('Invalid approval action.');
        }
        $this->member($tenant, $actor);

        return DB::transaction(function () use ($tenant, $actor, $request, $action, $idempotencyKey, $notes) {
            $request = ApprovalRequest::query()->whereKey($request->id)->where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();
            if (ApprovalAction::query()->where('tenant_id', $tenant->id)->where('idempotency_key', $idempotencyKey)->exists()) {
                return $request->load('workflowInstance');
            }
            if ($request->status !== 'pending') {
                throw new RuntimeException('Approval request is no longer pending.');
            }
            $step = ApprovalStep::query()->where('tenant_id', $tenant->id)->where('approval_request_id', $request->id)->where('status', 'pending')->lockForUpdate()->firstOrFail();
            if ($step->required_permission_id && ! $this->permissions->allows($actor, $tenant, Permission::query()->findOrFail($step->required_permission_id)->code)) {
                throw new AuthorizationException('Approval permission is required.');
            }
            $workflow = $request->workflowInstance()->with('workflowVersion')->firstOrFail();
            $versionDefinition = json_decode((string) $workflow->workflowVersion->getRawOriginal('definition'), true);
            if (! ($versionDefinition['allow_self_approval'] ?? false) && $request->requested_by === $actor->id) {
                throw new AuthorizationException('Self-approval is forbidden.');
            }
            $membership = $this->memberships->query($actor->id, $tenant->id)->firstOrFail();
            $authorityId = null;
            if ($step->authority_requirement) {
                $authority = json_decode((string) $step->getRawOriginal('authority_requirement'), true);
                if (! is_array($authority) || ! isset($authority['code'])) {
                    throw new RuntimeException('Invalid authority requirement.');
                }
                $authorityId = $this->snapshots->capture($actor, $tenant, $authority['code'], $authority['scope'] ?? '')->id;
            }
            $now = now();
            ApprovalAction::query()->create(['tenant_id' => $tenant->id, 'approval_request_id' => $request->id, 'approval_step_id' => $step->id, 'actor_membership_id' => $membership->id, 'authority_snapshot_id' => $authorityId, 'action' => $action, 'notes' => $notes, 'idempotency_key' => $idempotencyKey, 'acted_at' => $now]);
            $step->update(['status' => $action === 'approve' ? 'approved' : 'rejected', 'acted_at' => $now]);
            $instance = WorkflowInstance::query()->whereKey($request->workflow_instance_id)->where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();
            if ($action === 'reject') {
                $this->transition($tenant, $instance, $membership->id, 'REJECTED', 'reject', $idempotencyKey, $now);
                $request->update(['status' => 'rejected', 'completed_at' => $now]);
            } else {
                $next = ApprovalStep::query()->where('approval_request_id', $request->id)->where('status', 'waiting')->orderBy('sequence')->first();
                if ($next) {
                    $next->update(['status' => 'pending']);
                } else {
                    $this->transition($tenant, $instance, $membership->id, 'APPROVED', 'approve', $idempotencyKey, $now);
                    $request->update(['status' => 'approved', 'completed_at' => $now]);
                }
            }
            AuditLog::query()->create(['tenant_id' => $tenant->id, 'actor_id' => $actor->id, 'action' => 'workflow.approval.'.$action, 'subject_type' => 'approval_request', 'subject_id' => $request->id, 'before_state' => ['status' => 'pending'], 'after_state' => ['status' => $request->status], 'occurred_at' => $now]);

            return $request->fresh(['steps', 'workflowInstance']);
        });
    }

    public function finalize(Tenant $tenant, User $actor, ApprovalRequest $request, string $idempotencyKey): ApprovalRequest
    {
        $this->member($tenant, $actor);

        return DB::transaction(function () use ($tenant, $actor, $request, $idempotencyKey) {
            $request = ApprovalRequest::query()->whereKey($request->id)->where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();
            $instance = WorkflowInstance::query()->whereKey($request->workflow_instance_id)->where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();
            if ($instance->current_state === 'FINALIZED') {
                return $request->fresh(['workflowInstance']);
            }
            if ($request->status !== 'approved' || $idempotencyKey === '') {
                throw new RuntimeException('Approved workflow is required before finalization.');
            }
            $membership = $this->memberships->query($actor->id, $tenant->id)->firstOrFail();
            $this->transition($tenant, $instance, $membership->id, 'FINALIZED', 'finalize', $idempotencyKey, now());
            $request->update(['status' => 'finalized']);
            AuditLog::query()->create(['tenant_id' => $tenant->id, 'actor_id' => $actor->id, 'action' => 'workflow.finalized', 'subject_type' => 'approval_request', 'subject_id' => $request->id, 'before_state' => ['status' => 'approved'], 'after_state' => ['status' => 'finalized'], 'occurred_at' => now()]);

            return $request->fresh(['workflowInstance']);
        });
    }

    private function transition(Tenant $tenant, WorkflowInstance $instance, int $membershipId, string $state, string $action, string $key, mixed $at): void
    {
        WorkflowTransition::query()->create(['tenant_id' => $tenant->id, 'workflow_instance_id' => $instance->id, 'from_state' => $instance->current_state, 'to_state' => $state, 'action' => $action, 'actor_membership_id' => $membershipId, 'idempotency_key' => $key, 'occurred_at' => $at]);
        $instance->update(['current_state' => $state, 'completed_at' => $state === 'FINALIZED' ? $at : null, 'lock_version' => $instance->lock_version + 1]);
    }

    private function subjectId(Tenant $tenant, SubjectType $type, int|Model $subject): int
    {
        $id = $subject instanceof Model ? (int) $subject->getKey() : $subject;
        $model = match ($type->model_key) {
            'asset' => Asset::class, default => null
        };
        if ($model === null || ! $model::query()->whereKey($id)->where('tenant_id', $tenant->id)->exists()) {
            throw new AuthorizationException('Workflow subject is outside the active tenant.');
        }

        return $id;
    }

    private function member(Tenant $tenant, User $actor): void
    {
        if (! $tenant->isOperational() || ! $this->memberships->exists($actor->id, $tenant->id)) {
            throw new AuthorizationException('Active tenant membership is required.');
        }
    }
}
