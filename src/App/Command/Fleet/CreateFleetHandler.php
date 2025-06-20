<?php

declare(strict_types=1);

namespace App\Command\Fleet;

use Domain\Fleet\Fleet;
use Domain\Fleet\FleetId;
use Domain\Repository\FleetRepositoryInterface;

final readonly class CreateFleetHandler
{
    public function __construct(
        private FleetRepositoryInterface $fleetRepository
    ) {}

    public function handle(CreateFleetCommand $command): FleetId
    {
        $fleetId = FleetId::generate();
        $fleet = new Fleet($fleetId);
        
        $this->fleetRepository->save($fleet);

        return $fleetId;
    }
}