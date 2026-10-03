<?php

namespace App\Http\Controllers;

use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;

class TenantContextController extends Controller
{
    public function __invoke(TenantContext $context): JsonResponse
    {
        $tenant = $context->tenant();

        return response()->json(['tenant' => ['uuid' => $tenant->uuid, 'name' => $tenant->name, 'status' => $tenant->status]]);
    }
}
