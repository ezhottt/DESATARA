<?php

namespace App\Services\Inventory;

use App\Models\InventoryDiscrepancy;
use App\Models\InventoryItem;
use App\Models\InventoryReconciliation;
use App\Models\InventorySession;
use App\Models\Tenant;
use App\Models\User;
use DateTimeInterface;

final class InventoryService
{
    public function __construct(private ManageInventory $inventory) {}

    public function createSession(Tenant $tenant, User $actor, string $period, DateTimeInterface $reference): InventorySession
    {
        return $this->inventory->createSession($tenant, $actor, 'Inventory '.$period, $period, [], $reference);
    }

    public function freeze(InventorySession $session, User $actor): InventorySession
    {
        return $this->inventory->freezeDataset($session);
    }

    /** @param array<string, mixed> $data */
    public function observe(InventoryItem $item, User $actor, array $data): InventoryItem
    {
        return $this->inventory->observeItem($item->session, $item, $actor, $data);
    }

    public function reconcile(InventoryDiscrepancy $discrepancy, User $actor, string $type, string $key): InventoryReconciliation
    {
        return $this->inventory->reconcile($discrepancy, $actor, $type, 'Reconciled through controlled inventory action.', $key);
    }

    public function finalize(InventorySession $session, User $actor): InventorySession
    {
        return $this->inventory->finalize($session, $actor);
    }
}
