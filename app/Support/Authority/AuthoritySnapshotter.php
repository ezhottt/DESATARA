<?php

namespace App\Support\Authority;

use App\Models\AuthoritySnapshot;
use App\Models\Tenant;
use App\Models\User;
use RuntimeException;

final class AuthoritySnapshotter
{
    public function __construct(private AuthorityResolver $resolver) {}

    public function capture(User $user, Tenant $tenant, string $code, string $scope): AuthoritySnapshot
    {
        $official = $this->resolver->resolve($user, $tenant, $code, $scope) ?? throw new RuntimeException('Valid regulatory authority required.');

        return AuthoritySnapshot::query()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'membership_id' => $official->membership_id, 'tenant_official_id' => $official->id, 'authority_type' => $official->authority_code, 'official_type' => $official->official_type, 'position_name' => $official->position_name, 'appointment_number' => $official->appointment_number, 'valid_from' => $official->valid_from, 'valid_until' => $official->valid_until, 'snapshot_payload' => ['authority_scope' => $official->authority_scope, 'appointment_date' => $official->appointment_date?->toDateString()], 'captured_at' => now()]);
    }
}
