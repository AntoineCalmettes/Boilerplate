<?php

declare(strict_types=1);

namespace App\Command\LocalizeVehicle;

use Domain\Repository\FleetRepositoryInterface;

final readonly class LocalizeVehicleHandler
{
    public function __construct(
        private FleetRepositoryInterface $repo
    ) {}

    public function __invoke(LocalizeVehicleCommand $command): void
    {
        $fleet = $this->repo->findById($command->fleetId) 
            ?? throw new \RuntimeException("Fleet not found");

        $fleet->parkVehicleAtLocation($command->vehicleId, $command->location);
        $this->repo->save($fleet);
    }
}