<?php
declare(strict_types=1);

namespace Infra\Repository;

use Domain\Fleet\Fleet;
use Domain\Fleet\FleetId;

interface FleetRepositoryInterface
{
    public function findById(FleetId $id): ?Fleet;

    public function save(Fleet $fleet): void;
}
