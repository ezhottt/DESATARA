<?php

namespace App\Http\Controllers;

use App\Actions\Assets\ManageHistoricalAssetState;
use App\Models\ApprovalRequest;
use App\Models\Asset;
use App\Models\AssetClassification;
use App\Models\AssetDisposal;
use App\Models\AssetLocation;
use App\Models\AssetMaintenance;
use App\Models\AssetMutation;
use App\Models\AssetPhoto;
use App\Models\AssetReport;
use App\Models\AssetSafeguard;
use App\Models\AssetTransfer;
use App\Models\AssetUsageDetermination;
use App\Models\AssetUtilization;
use App\Models\AssetValuation;
use App\Models\AuditLog;
use App\Models\FundingSource;
use App\Models\ImportError;
use App\Models\ImportJob;
use App\Models\IntegrationExport;
use App\Models\InventoryDiscrepancy;
use App\Models\InventoryItem;
use App\Models\InventorySession;
use App\Models\OrganizationalUnit;
use App\Models\ReportingPeriod;
use App\Models\ReportTemplateVersion;
use App\Models\ResponsibleParty;
use App\Models\Role;
use App\Models\TenantMembership;
use App\Models\TenantSetting;
use App\Models\Unit;
use App\Models\User;
use App\Services\Assets\AssetIdentityService;
use App\Services\Evidence\DocumentService;
use App\Services\Evidence\QrTokenService;
use App\Services\Interoperability\ImportExportService;
use App\Services\Interoperability\LegacyAssetFileReader;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\ManageInventory;
use App\Services\Lifecycle\ManageAssetLifecycleOperations;
use App\Services\Reporting\ReportingService;
use App\Services\Workflow\WorkflowApprovalEngine;
use App\Support\Authorization\PermissionResolver;
use App\Support\Authorization\RoleAssignment;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProductSurfaceController extends Controller
{
    public function assets(Request $request, TenantContext $context): Response
    {
        $q = trim((string) $request->input('q'));
        $items = Asset::query()->where('tenant_id', $context->id())->with('classification')->when($q, fn ($query) => $query->where(fn ($q2) => $q2->where('name', 'like', "%{$q}%")->orWhere('asset_code', 'like', "%{$q}%")->orWhere('register_number', 'like', "%{$q}%")))->latest()->paginate(25)->withQueryString()->through(fn (Asset $asset) => ['uuid' => $asset->uuid, 'name' => $asset->name, 'asset_code' => $asset->asset_code, 'register_number' => $asset->register_number, 'classification' => $asset->classification?->only(['code', 'name']), 'condition' => $asset->condition, 'lifecycle_status' => $asset->lifecycle_status, 'verification_status' => $asset->verification_status]);

        return Inertia::render('Surface/Index', ['surface' => 'assets', 'title' => 'Aset', 'description' => 'Daftar aset tenant aktif.', 'items' => $items, 'filters' => ['q' => $q], 'createUrl' => route('assets.create')]);
    }

    public function prepareAssetLabels(Request $request, TenantContext $context, AssetIdentityService $identity): Response
    {
        $data = $request->validate([
            'asset_uuids' => ['required_without:filter_q', 'array', 'min:1', 'max:100'],
            'asset_uuids.*' => ['required', 'uuid', 'distinct'],
            'filter_q' => ['required_without:asset_uuids', 'nullable', 'string', 'max:100'],
        ]);

        if (isset($data['filter_q'])) {
            $q = trim((string) $data['filter_q']);
            if ($q === '') {
                throw ValidationException::withMessages(['filter_q' => 'Filter pencarian aset harus diisi.']);
            }

            $assets = Asset::query()
                ->where('tenant_id', $context->id())
                ->with('classification')
                ->where(fn ($query) => $query
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('asset_code', 'like', "%{$q}%")
                    ->orWhere('register_number', 'like', "%{$q}%"))
                ->orderBy('id')
                ->limit(101)
                ->get();

            if ($assets->count() > 100) {
                throw ValidationException::withMessages(['filter_q' => 'Hasil filter melebihi 100 aset. Persempit filter sebelum mencetak label.']);
            }
            if ($assets->isEmpty()) {
                throw ValidationException::withMessages(['filter_q' => 'Tidak ada aset yang cocok dengan filter.']);
            }
        } else {
            $assets = Asset::query()
                ->where('tenant_id', $context->id())
                ->with('classification')
                ->whereIn('uuid', $data['asset_uuids'])
                ->get();
            abort_unless($assets->count() === count($data['asset_uuids']), 422);
        }

        $labels = $assets->map(function (Asset $asset) use ($context, $identity): array {
            return $identity->labelPayload($context->tenant(), $asset) + [
                'qr_url' => route('assets.verify', ['uuid' => $asset->uuid]),
            ];
        })->values();

        return Inertia::render('Assets/Labels', [
            'labels' => $labels,
            'tenant' => $context->tenant()->only(['uuid', 'name', 'village_code']),
        ]);
    }

    public function createAsset(TenantContext $context): Response
    {
        return Inertia::render('Assets/Form', ['asset' => null, 'classifications' => AssetClassification::query()->where('status', 'active')->orderBy('code')->get(['id', 'code', 'name']), 'locations' => AssetLocation::query()->where('tenant_id', $context->id())->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']), 'units' => Unit::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']), 'fundingSources' => FundingSource::query()->where(fn ($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $context->id()))->where('status', 'active')->get(['id', 'code', 'name'])]);
    }

    public function storeAsset(Request $request, TenantContext $context, AssetIdentityService $identity): RedirectResponse
    {
        $data = $request->validate([
            'classification_id' => 'required|integer',
            'asset_code' => 'nullable|string|max:150',
            'name' => 'required|string|max:255',
            'acquisition_date' => 'required|date',
            'quantity' => 'required|numeric|in:1',
            'unit_id' => 'required|integer',
            'condition' => 'required|string|max:50',
            'acquisition_value' => 'nullable|numeric|min:0',
            'current_location_id' => 'nullable|integer',
            'current_responsible_party_id' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $classification = AssetClassification::query()->whereKey($data['classification_id'])->where('status', 'active')->first();
        abort_unless($classification !== null, 422);
        abort_unless(Unit::query()->whereKey($data['unit_id'])->where('status', 'active')->exists(), 422);
        if (isset($data['current_location_id'])) {
            abort_unless(AssetLocation::query()->where('tenant_id', $context->id())->whereKey($data['current_location_id'])->exists(), 422);
        }
        if (isset($data['current_responsible_party_id'])) {
            abort_unless(ResponsibleParty::query()->where('tenant_id', $context->id())->whereKey($data['current_responsible_party_id'])->exists(), 422);
        }

        $year = CarbonImmutable::parse($data['acquisition_date'])->year;
        $asset = DB::transaction(function () use ($data, $context, $identity, $year, $request): Asset {
            $nup = $identity->nextNup($context->tenant(), (int) $data['classification_id'], $year);

            return Asset::query()->create($data + [
                'tenant_id' => $context->id(),
                'register_number' => $nup,
                'acquisition_year' => $year,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
                'lifecycle_status' => 'draft',
                'verification_status' => 'unverified',
            ]);
        });

        return redirect()->route('assets.show', $asset->uuid)->with('success', 'Aset berhasil diregistrasikan dengan NUP '.$asset->register_number.'.');
    }

    public function showAsset(Request $request, string $uuid, TenantContext $context, PermissionResolver $permissions): Response
    {
        $asset = Asset::query()->where('tenant_id', $context->id())->where('uuid', $uuid)->with(['classification', 'photos', 'mutations', 'conditionEvents', 'lifecycleEvents', 'responsibilityAssignments', 'classificationAssignments'])->firstOrFail();

        $subjectType = DB::table('subject_types')->where('code', 'asset')->value('id');
        $documents = DB::table('document_links')->join('documents', 'documents.id', '=', 'document_links.document_id')->where('document_links.tenant_id', $context->id())->where('document_links.subject_type_id', $subjectType)->where('document_links.subject_id', $asset->id)->get(['documents.uuid', 'documents.document_type', 'documents.original_filename', 'documents.mime_type', 'documents.size_bytes', 'documents.created_at']);
        $timeline = collect()->merge($asset->conditionEvents->map(fn ($e) => ['type' => 'Kondisi berubah', 'at' => $e->effective_at, 'detail' => $e->previous_condition.' -> '.$e->new_condition]))->merge($asset->lifecycleEvents->map(fn ($e) => ['type' => 'Lifecycle berubah', 'at' => $e->effective_at, 'detail' => $e->from_status.' -> '.$e->to_status]))->merge($asset->mutations->map(fn ($e) => ['type' => 'Mutasi', 'at' => $e->effective_at, 'detail' => 'Lokasi '.$e->origin_location_id.' -> '.$e->destination_location_id]))->sortByDesc('at')->values();

        return Inertia::render('Assets/Show', ['asset' => $asset, 'documents' => $documents, 'timeline' => $timeline, 'canUpdate' => $permissions->allows($request->user(), $context->tenant(), 'assets.update')]);
    }

    public function editAsset(string $uuid, TenantContext $context): Response
    {
        $asset = Asset::query()->where('tenant_id', $context->id())->where('uuid', $uuid)->firstOrFail();

        return Inertia::render('Assets/Form', ['asset' => $asset, 'classifications' => AssetClassification::query()->where('status', 'active')->orderBy('code')->get(['id', 'code', 'name']), 'locations' => AssetLocation::query()->where('tenant_id', $context->id())->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']), 'units' => Unit::query()->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name']), 'fundingSources' => FundingSource::query()->where(fn ($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $context->id()))->where('status', 'active')->get(['id', 'code', 'name'])]);
    }

    public function updateAsset(Request $request, string $uuid, TenantContext $context, ManageHistoricalAssetState $history): RedirectResponse
    {
        $asset = Asset::query()->where('tenant_id', $context->id())->where('uuid', $uuid)->firstOrFail();
        $data = $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string', 'condition' => 'required|string|max:50', 'lock_version' => 'required|integer|min:1']);
        unset($data['lock_version']);
        $history->correct($asset, $data, 'Koreksi melalui surface aset', $request->user()->id, now(), (int) $request->integer('lock_version'), 'web-asset-update');

        return back()->with('success', 'Koreksi aset dicatat sebagai histori.');
    }

    public function masterData(string $type, TenantContext $context): Response
    {
        $models = ['classifications' => AssetClassification::class, 'locations' => AssetLocation::class, 'organizational-units' => OrganizationalUnit::class, 'funding-sources' => FundingSource::class, 'responsible-parties' => ResponsibleParty::class];
        abort_unless(isset($models[$type]), 404);
        $query = $models[$type]::query();
        if ($type === 'funding-sources') {
            $query->where(fn ($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $context->id()));
        } elseif ($type !== 'classifications') {
            $query->where('tenant_id', $context->id());
        }

        return Inertia::render('Surface/Index', ['surface' => 'master-data', 'kind' => $type, 'title' => 'Master data: '.str_replace('-', ' ', $type), 'description' => 'Data dibatasi pada active tenant context.', 'items' => $query->orderBy('id')->paginate(25)]);
    }

    public function createMasterData(string $type): Response
    {
        return Inertia::render('Surface/Index', ['surface' => 'master-form', 'title' => 'Tambah master data', 'items' => ['data' => []]]);
    }

    public function storeMasterData(Request $request, string $type, TenantContext $context): RedirectResponse
    {
        $map = [
            'locations' => [AssetLocation::class, ['code' => ['required', 'string', 'max:100'], 'name' => ['required', 'string', 'max:255'], 'location_type' => ['required', 'string', 'max:50'], 'parent_id' => ['nullable', 'integer']]],
            'organizational-units' => [OrganizationalUnit::class, ['code' => ['required', 'string', 'max:100'], 'name' => ['required', 'string', 'max:255'], 'type' => ['required', 'string', 'max:50'], 'parent_id' => ['nullable', 'integer']]],
            'funding-sources' => [FundingSource::class, ['code' => ['required', 'string', 'max:100'], 'name' => ['required', 'string', 'max:255']]],
            'responsible-parties' => [ResponsibleParty::class, ['party_type' => ['required', 'in:membership,organizational_unit'], 'membership_id' => ['nullable', 'integer'], 'organizational_unit_id' => ['nullable', 'integer']]],
        ];
        abort_unless(isset($map[$type]), 404);
        [$model, $rules] = $map[$type];
        $data = $request->validate($rules);
        $data['tenant_id'] = $context->id();
        $data['status'] = 'active';
        if ($type === 'locations' && isset($data['parent_id'])) {
            abort_unless(AssetLocation::query()->where('tenant_id', $context->id())->whereKey($data['parent_id'])->exists(), 422);
        }
        if ($type === 'organizational-units' && isset($data['parent_id'])) {
            abort_unless(OrganizationalUnit::query()->where('tenant_id', $context->id())->whereKey($data['parent_id'])->exists(), 422);
        }
        if ($type === 'responsible-parties') {
            $valid = ($data['party_type'] === 'membership' && ! empty($data['membership_id']) && empty($data['organizational_unit_id'])) || ($data['party_type'] === 'organizational_unit' && ! empty($data['organizational_unit_id']) && empty($data['membership_id']));
            abort_unless($valid, 422);
            if (! empty($data['membership_id'])) {
                abort_unless(TenantMembership::query()->where('tenant_id', $context->id())->whereKey($data['membership_id'])->exists(), 422);
            }
            if (! empty($data['organizational_unit_id'])) {
                abort_unless(OrganizationalUnit::query()->where('tenant_id', $context->id())->whereKey($data['organizational_unit_id'])->exists(), 422);
            }
        }
        $model::query()->create($data);

        return redirect()->route('master-data.index', $type)->with('success', 'Master data tersimpan.');
    }

    public function inventory(TenantContext $context): Response
    {
        return Inertia::render('Surface/Index', ['surface' => 'inventory', 'title' => 'Inventarisasi', 'description' => 'Observasi dan rekonsiliasi tidak mengubah master aset secara langsung.', 'items' => InventorySession::query()->where('tenant_id', $context->id())->latest()->paginate(25), 'inventoryItems' => InventoryItem::query()->where('tenant_id', $context->id())->latest()->limit(100)->get(), 'discrepancies' => InventoryDiscrepancy::query()->where('tenant_id', $context->id())->where('status', 'open')->latest()->limit(100)->get()]);
    }

    public function createInventory(Request $request, TenantContext $context, ManageInventory $inventory): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:150', 'period_label' => 'required|string|max:100', 'reference_at' => 'required|date']);
        $inventory->createSession($context->tenant(), $request->user(), $data['name'], $data['period_label'], [], new \DateTimeImmutable($data['reference_at']));

        return back()->with('success', 'Sesi inventarisasi dibuat melalui service.');
    }

    public function prepareInventory(string $uuid, TenantContext $context, InventoryService $inventory): RedirectResponse
    {
        $session = InventorySession::query()->where('tenant_id', $context->id())->where('uuid', $uuid)->firstOrFail();
        $inventory->freeze($session, request()->user());

        return back()->with('success', 'Dataset inventarisasi dibekukan dari snapshot aset.');
    }

    public function observeInventory(Request $request, int $id, TenantContext $context, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate(['result' => ['required', 'in:matched,missing,relocated,condition_mismatch,duplicate_suspected'], 'observed_location_id' => ['nullable', 'integer'], 'observed_condition' => ['nullable', 'string', 'max:50']]);
        $item = InventoryItem::query()->where('tenant_id', $context->id())->findOrFail($id);
        $inventory->observe($item, $request->user(), $data);

        return back()->with('success', 'Observasi inventarisasi tersimpan.');
    }

    public function reconcileInventory(Request $request, int $id, TenantContext $context, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate(['resolution_type' => ['required', 'in:no_change,asset_correction,mutation,condition_update,new_asset,duplicate_investigation,other'], 'idempotency_key' => ['required', 'string', 'max:150']]);
        $discrepancy = InventoryDiscrepancy::query()->where('tenant_id', $context->id())->findOrFail($id);
        $inventory->reconcile($discrepancy, $request->user(), $data['resolution_type'], $data['idempotency_key']);

        return back()->with('success', 'Discrepancy direkonsiliasi melalui service. Master aset tidak diubah otomatis.');
    }

    public function finalizeInventory(string $uuid, TenantContext $context, InventoryService $inventory): RedirectResponse
    {
        $session = InventorySession::query()->where('tenant_id', $context->id())->where('uuid', $uuid)->firstOrFail();
        $inventory->finalize($session, request()->user());

        return back()->with('success', 'Inventarisasi difinalisasi dan menjadi snapshot immutable.');
    }

    public function lifecycleAction(Request $request, string $type, TenantContext $context, ManageAssetLifecycleOperations $lifecycle, ManageHistoricalAssetState $history, PermissionResolver $permissions): RedirectResponse
    {
        $this->authorizeLifecyclePermission($request, $context, $permissions, $type);
        $data = $request->all();
        $actor = $request->user();
        if (isset($data['asset_id'])) {
            $data['asset'] = Asset::query()->where('tenant_id', $context->id())->findOrFail((int) $data['asset_id']);
        }
        switch ($type) {
            case 'maintenance': $request->validate(['asset_id' => ['required', 'integer'], 'maintenance_type' => ['required', 'string', 'max:100']]);
                $lifecycle->createMaintenance($context->tenant(), $actor, $data['asset'], $data);
                break;
            case 'mutations': $request->validate(['asset_id' => ['required', 'integer'], 'destination_location_id' => ['required', 'integer'], 'mutation_type' => ['required', 'string', 'max:100'], 'effective_at' => ['required', 'date']]);
                abort_unless(AssetLocation::query()->where('tenant_id', $context->id())->whereKey($data['destination_location_id'])->exists(), 422);
                $history->move($data['asset'], (int) $data['destination_location_id'], $actor->id, new \DateTimeImmutable($data['effective_at']), $data['asset']->lock_version, $data['mutation_type'], $data['reason'] ?? null, $data['idempotency_key'] ?? null);
                break;
            case 'valuation': $request->validate(['asset_id' => ['required', 'integer'], 'purpose' => ['required', 'string'], 'valuation_date' => ['required', 'date'], 'amount' => ['required', 'numeric', 'min:0'], 'valuer_name' => ['required', 'string']]);
                $lifecycle->recordValuation($context->tenant(), $actor, $data['asset'], $data);
                break;
            case 'utilization': $request->validate(['asset_id' => ['required', 'integer'], 'utilization_type' => ['required', 'string'], 'start_at' => ['required', 'date']]);
                $lifecycle->recordUtilization($context->tenant(), $actor, $data['asset'], $data);
                break;
            case 'safeguarding': $request->validate(['asset_id' => ['required', 'integer'], 'safeguard_category' => ['required', 'string']]);
                $lifecycle->recordSafeguard($context->tenant(), $actor, $data['asset'], $data);
                break;
            case 'transfer': case 'transfers': $assetIds = array_values(array_unique($request->validate(['asset_ids' => ['required', 'array', 'min:1'], 'asset_ids.*' => ['integer']])['asset_ids']));
                $assets = Asset::query()->where('tenant_id', $context->id())->whereIn('id', $assetIds)->get()->all();
                if (count($assets) !== count($assetIds)) {
                    abort(404);
                }
                $lifecycle->createTransfer($context->tenant(), $actor, (string) $request->input('transfer_type', 'EXCHANGE'), $assets);
                break;
            case 'disposals': $assetIds = array_values(array_unique($request->validate(['asset_ids' => ['required', 'array', 'min:1'], 'asset_ids.*' => ['integer']])['asset_ids']));
                $assets = Asset::query()->where('tenant_id', $context->id())->whereIn('id', $assetIds)->get()->all();
                if (count($assets) !== count($assetIds)) {
                    abort(404);
                }
                $lifecycle->createDisposal($context->tenant(), $actor, (string) $request->input('disposal_type', 'OTHER'), (string) $request->input('reason'), $assets);
                break;
            case 'usage': $lifecycle->createUsageDetermination($context->tenant(), $actor, (int) $request->input('period_year'), $request->input('decision_number'));
                break;
            default: abort(404);
        }

        return back()->with('success', 'Operasi lifecycle tercatat melalui service.');
    }

    public function lifecycleExecute(Request $request, string $type, int $id, TenantContext $context, ManageAssetLifecycleOperations $lifecycle, PermissionResolver $permissions): RedirectResponse
    {
        $this->authorizeLifecyclePermission($request, $context, $permissions, $type);
        $recordQuery = match ($type) {
            'maintenance' => AssetMaintenance::query(), 'transfer', 'transfers' => AssetTransfer::query(), 'disposals' => AssetDisposal::query(), 'usage' => AssetUsageDetermination::query(), default => abort(404)
        };
        $record = $recordQuery->where('tenant_id', $context->id())->findOrFail($id);
        match ($type) {
            'maintenance' => $lifecycle->completeMaintenance($context->tenant(), $request->user(), $record, $request->validate(['actual_cost' => ['nullable', 'numeric', 'min:0'], 'condition_after' => ['nullable', 'string'], 'notes' => ['nullable', 'string']])),
            'transfer', 'transfers' => $lifecycle->executeTransfer($context->tenant(), $request->user(), $record, $request->validate(['destination_location_id' => ['required', 'integer'], 'idempotency_key' => ['nullable', 'string', 'max:150']])),
            'disposals' => $lifecycle->executeDisposal($context->tenant(), $request->user(), $record, $request->validate(['idempotency_key' => ['nullable', 'string', 'max:150']])),
            'usage' => $lifecycle->finalizeUsageDetermination($context->tenant(), $request->user(), $record),
        };

        return back()->with('success', 'Transisi lifecycle dijalankan dengan guard domain.');
    }

    public function document(Request $request, string $uuid, TenantContext $context, DocumentService $documents): RedirectResponse
    {
        $asset = Asset::query()->where('tenant_id', $context->id())->where('uuid', $uuid)->firstOrFail();
        $data = $request->validate(['file' => ['required', 'file'], 'document_type' => ['required', 'string', 'max:100'], 'photo_category' => ['nullable', 'string', 'max:100']]);
        $document = $documents->store($context->tenant(), $request->user(), $asset, $data['file'], $data['document_type']);
        if (! empty($data['photo_category'])) {
            AssetPhoto::query()->create(['tenant_id' => $context->id(), 'asset_id' => $asset->id, 'document_id' => $document->id, 'category' => $data['photo_category'], 'uploaded_by' => $request->user()->id]);
        }

        return back()->with('success', 'Evidence disimpan private dan ditautkan ke aset.');
    }

    public function qr(string $uuid, TenantContext $context, QrTokenService $qr): RedirectResponse
    {
        $asset = Asset::query()->where('tenant_id', $context->id())->where('uuid', $uuid)->firstOrFail();
        [$raw] = $qr->issue($context->tenant(), request()->user(), $asset);

        return back()->with('qr_token', $raw)->with('success', 'QR aktif diterbitkan. Simpan token ini sebagai nilai sekali tampil.');
    }

    public function lifecycle(string $type, TenantContext $context): Response
    {
        $models = ['maintenance' => AssetMaintenance::class, 'mutations' => AssetMutation::class, 'utilization' => AssetUtilization::class, 'safeguarding' => AssetSafeguard::class, 'valuation' => AssetValuation::class, 'transfers' => AssetTransfer::class, 'disposals' => AssetDisposal::class, 'usage' => AssetUsageDetermination::class];
        abort_unless(isset($models[$type]), 404);

        return Inertia::render('Surface/Index', ['surface' => 'lifecycle', 'kind' => $type, 'title' => ucfirst($type), 'description' => 'Operasi dijalankan melalui service domain dan explicit transition.', 'items' => $models[$type]::query()->where('tenant_id', $context->id())->latest('id')->paginate(25), 'assets' => Asset::query()->where('tenant_id', $context->id())->orderBy('name')->get(['id', 'uuid', 'name', 'asset_code']), 'locations' => AssetLocation::query()->where('tenant_id', $context->id())->where('status', 'active')->orderBy('name')->get(['id', 'code', 'name'])]);
    }

    public function approvals(TenantContext $context): Response
    {
        return Inertia::render('Surface/Index', ['surface' => 'approvals', 'title' => 'Persetujuan', 'items' => ApprovalRequest::query()->where('tenant_id', $context->id())->latest()->paginate(25)]);
    }

    public function approvalDetail(int $id, TenantContext $context): Response
    {
        $approval = ApprovalRequest::query()->where('tenant_id', $context->id())->with(['steps.requiredPermission', 'workflowInstance.workflowVersion'])->findOrFail($id);

        return Inertia::render('Surface/Index', ['surface' => 'approval-detail', 'title' => 'Detail persetujuan #'.$approval->id, 'description' => 'Status dan tahapan berasal dari WorkflowApprovalEngine.', 'approval' => $approval]);
    }

    public function approvalAction(Request $request, int $id, TenantContext $context, WorkflowApprovalEngine $engine): RedirectResponse
    {
        $data = $request->validate(['action' => 'required|string|in:approve,reject', 'idempotency_key' => 'required|string|max:150', 'notes' => 'nullable|string']);
        $approval = ApprovalRequest::query()->where('tenant_id', $context->id())->findOrFail($id);
        $engine->act($context->tenant(), $request->user(), $approval, $data['action'], $data['idempotency_key'], $data['notes'] ?? null);

        return back()->with('success', 'Aksi approval diproses oleh engine.');
    }

    public function reports(TenantContext $context): Response
    {
        return Inertia::render('Surface/Index', ['surface' => 'reports', 'title' => 'Laporan', 'description' => 'Finalized report memiliki snapshot immutable dan hanya dapat direvisi sebagai record baru.', 'items' => AssetReport::query()->where('tenant_id', $context->id())->latest()->paginate(25), 'periods' => ReportingPeriod::query()->where('tenant_id', $context->id())->latest()->get(), 'templates' => ReportTemplateVersion::query()->where('status', 'published')->latest()->get()]);
    }

    public function createPeriod(Request $request, TenantContext $context, ReportingService $reporting): RedirectResponse
    {
        $data = $request->validate(['year' => ['required', 'integer', 'min:1900', 'max:9999'], 'period_type' => ['required', 'in:semester,year'], 'period_number' => ['nullable', 'integer', 'in:1,2'], 'start_date' => ['required', 'date'], 'end_date' => ['required', 'date'], 'deadline' => ['nullable', 'date']]);
        $reporting->createPeriod($context->tenant(), $request->user(), $data['year'], $data['period_type'], $data['period_number'] ?? null, $data['start_date'], $data['end_date'], $data['deadline'] ?? null);

        return back()->with('success', 'Periode pelaporan dibuat.');
    }

    public function createReport(Request $request, TenantContext $context, ReportingService $reporting): RedirectResponse
    {
        $data = $request->validate(['period_id' => ['required', 'integer'], 'template_version_id' => ['required', 'integer']]);
        $period = ReportingPeriod::query()->where('tenant_id', $context->id())->findOrFail($data['period_id']);
        $template = ReportTemplateVersion::query()->whereKey($data['template_version_id'])->where('status', 'published')->firstOrFail();
        $reporting->generate($context->tenant(), $request->user(), $period, $template);

        return back()->with('success', 'Draft laporan dibuat dari template versioned.');
    }

    public function reportAction(Request $request, int $id, string $action, TenantContext $context, ReportingService $reporting): RedirectResponse
    {
        $report = AssetReport::query()->where('tenant_id', $context->id())->findOrFail($id);
        match ($action) {
            'review' => $reporting->review($context->tenant(), $request->user(), $report), 'finalize' => $reporting->finalize($context->tenant(), $request->user(), $report), 'revise' => $reporting->revise($context->tenant(), $request->user(), $report), default => abort(404)
        };

        return back()->with('success', 'Aksi laporan diproses oleh service.');
    }

    public function reportExport(int $id, TenantContext $context, ReportingService $reporting): RedirectResponse
    {
        $report = AssetReport::query()->where('tenant_id', $context->id())->findOrFail($id);
        $document = $reporting->export($context->tenant(), request()->user(), $report);

        return redirect()->route('documents.download', $document->uuid);
    }

    public function imports(TenantContext $context): Response
    {
        return Inertia::render('Imports/Index', [
            'jobs' => ImportJob::query()->where('tenant_id', $context->id())->latest()->paginate(25),
        ]);
    }

    public function importShow(string $uuid, TenantContext $context): Response
    {
        $job = ImportJob::query()->where('tenant_id', $context->id())->where('uuid', $uuid)->with(['rows', 'errors'])->firstOrFail();

        return Inertia::render('Imports/Show', ['job' => $job]);
    }

    public function importFilePreview(Request $request, TenantContext $context, LegacyAssetFileReader $reader, ImportExportService $interop): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:csv,txt,xlsx,xls'],
            'strategy' => ['required', 'in:atomic,partial'],
        ]);
        try {
            $rows = $reader->read($data['file'], $context->tenant());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }
        abort_if(count($rows) > 5000, 422, 'Maksimal 5.000 baris per impor.');
        $job = $interop->preview($context->tenant(), $request->user(), $rows, 'assets', $data['strategy']);

        return redirect()->route('imports.show', $job->uuid);
    }

    public function importFileCommit(Request $request, string $uuid, TenantContext $context, ImportExportService $interop): RedirectResponse
    {
        $job = ImportJob::query()->where('tenant_id', $context->id())->where('uuid', $uuid)->firstOrFail();
        $interop->confirm($context->tenant(), $request->user(), $job);

        return redirect()->route('imports.show', $job->uuid)->with('success', 'Impor aset selesai.');
    }

    public function importTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, ['Nama Barang', 'Kode Barang', 'NUP', 'Tanggal Perolehan', 'Tahun Perolehan', 'Kode Internal', 'Asal Perolehan', 'Harga Perolehan', 'Jumlah', 'Satuan', 'Sumber Dana', 'Lokasi', 'Kondisi']);
            fputcsv($stream, ['Contoh Laptop', 'AST-001', '0001', 'ELEKTRONIK', '2026', 'Pembelian', '12500000', '1', 'UNIT', 'APBDES', 'KANTOR', 'Baik']);
            fclose($stream);
        }, 'template-impor-aset-desatara.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function interoperability(Request $request, ?string $type, TenantContext $context, PermissionResolver $permissions): Response
    {
        $type ??= 'exports';
        $this->authorizePermission($request, $context, $permissions, $type === 'imports' ? 'assets.create' : 'reports.export');
        $model = $type === 'imports' ? ImportJob::class : IntegrationExport::class;
        abort_unless(in_array($type, ['imports', 'exports'], true), 404);

        return Inertia::render('Surface/Index', ['surface' => 'interoperability', 'title' => ucfirst($type), 'description' => 'Preview/commit dan export memakai service interoperability dengan active tenant scope.', 'items' => $model::query()->where('tenant_id', $context->id())->latest()->paginate(25), 'errors' => ImportError::query()->where('tenant_id', $context->id())->latest()->limit(100)->get()]);
    }

    public function export(Request $request, TenantContext $context, ImportExportService $interop): RedirectResponse
    {
        $data = $request->validate(['target_system' => 'required|string|max:100']);
        $export = $interop->prepareExport($context->tenant(), $request->user(), $data['target_system']);
        $interop->markExported($context->tenant(), $request->user(), $export);

        return back()->with('success', 'Export dibuat melalui service interoperability.');
    }

    public function importPreview(Request $request, TenantContext $context, ImportExportService $interop): RedirectResponse
    {
        $data = $request->validate(['rows' => ['required', 'array', 'min:1'], 'strategy' => ['required', 'in:atomic,partial']]);
        $job = $interop->preview($context->tenant(), $request->user(), $data['rows'], 'assets', $data['strategy']);

        return back()->with('success', "Import preview {$job->uuid}: {$job->valid_rows} valid, {$job->invalid_rows} invalid.");
    }

    public function importCommit(Request $request, string $uuid, TenantContext $context, ImportExportService $interop): RedirectResponse
    {
        $job = ImportJob::query()->where('tenant_id', $context->id())->where('uuid', $uuid)->firstOrFail();
        $interop->confirm($context->tenant(), $request->user(), $job);

        return back()->with('success', 'Import dikomit melalui service interoperability.');
    }

    public function administration(Request $request, string $type, TenantContext $context, PermissionResolver $permissions): Response
    {
        $requiredPermission = match ($type) {
            'members' => 'users.manage',
            'audit' => 'audit.view',
            'settings' => 'tenant.settings.update',
            default => abort(404),
        };
        $this->authorizePermission($request, $context, $permissions, $requiredPermission);
        $model = $type === 'members' ? TenantMembership::class : AuditLog::class;
        $items = match ($type) {
            'members' => TenantMembership::query()->where('tenant_id', $context->id())->with(['user:id,name,email', 'roles:id,name,code'])->latest()->paginate(25), 'audit' => AuditLog::query()->where('tenant_id', $context->id())->latest('occurred_at')->paginate(25), 'settings' => TenantSetting::query()->firstOrCreate(['tenant_id' => $context->id()]),
        };

        return Inertia::render('Surface/Index', ['surface' => 'administration', 'kind' => $type, 'title' => 'Administrasi: '.str_replace('-', ' ', $type), 'description' => 'Administrasi tenant tidak memberikan akses otomatis kepada Platform Admin.', 'items' => $items, 'roles' => Role::query()->where('scope_type', 'tenant')->orderBy('name')->get(['id', 'code', 'name'])]);
    }

    public function addMembership(Request $request, TenantContext $context): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'role_id' => ['required', 'integer'], 'valid_from' => ['nullable', 'date'], 'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from']]);
        $user = User::query()->where('email', $data['email'])->firstOrFail();
        $role = Role::query()->where('scope_type', 'tenant')->findOrFail($data['role_id']);
        $membership = TenantMembership::query()->firstOrCreate(['tenant_id' => $context->id(), 'user_id' => $user->id], ['status' => 'invited', 'valid_from' => $data['valid_from'] ?? null, 'valid_until' => $data['valid_until'] ?? null]);
        app(RoleAssignment::class)->assign($membership, $role, $request->user());

        return back()->with('success', 'Membership dan role tenant ditambahkan.');
    }

    public function updateSettings(Request $request, TenantContext $context): RedirectResponse
    {
        $data = $request->validate(['branding' => ['nullable', 'array'], 'numbering_config' => ['nullable', 'array'], 'reporting_config' => ['nullable', 'array'], 'operational_config' => ['nullable', 'array']]);
        TenantSetting::query()->updateOrCreate(['tenant_id' => $context->id()], $data);

        return back()->with('success', 'Pengaturan tenant diperbarui.');
    }

    private function authorizeLifecyclePermission(Request $request, TenantContext $context, PermissionResolver $permissions, string $type): void
    {
        $permission = match ($type) {
            'maintenance' => 'maintenance.manage',
            'mutations' => 'mutations.request',
            'transfer', 'transfers' => 'transfers.request',
            'disposals' => 'disposals.request',
            'valuation', 'utilization', 'safeguarding', 'usage' => 'assets.update',
            default => abort(404),
        };
        $this->authorizePermission($request, $context, $permissions, $permission);
    }

    private function authorizePermission(Request $request, TenantContext $context, PermissionResolver $permissions, string $permission): void
    {
        abort_unless($permissions->allows($request->user(), $context->tenant(), $permission), 403);
    }
}
