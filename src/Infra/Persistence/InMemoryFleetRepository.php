<?php
declare(strict_types=1);

namespace Infra\Persistence;

use Domain\Repository\FleetRepositoryInterface;
use Domain\Fleet\Fleet;
use Domain\Fleet\FleetId;

final class InMemoryFleetRepository implements FleetRepositoryInterface
{
    /**
     * @var list<Fleet>
     */
    private array $fleets = [];

    public function findById(FleetId $id): ?Fleet
    {
        foreach ($this->fleets as $fleet) {
            if ($fleet->id()->equals($id)) {
                return $fleet;
            }
        }

        return null;
    }

    public function save(Fleet $fleet): void
    {
        foreach ($this->fleets as $index => $existingFleet) {
            if ($existingFleet->id()->equals($fleet->id())) {
                $this->fleets[$index] = $fleet;
                return;
            }
        }

        $this->fleets[] = $fleet;
    }
}
