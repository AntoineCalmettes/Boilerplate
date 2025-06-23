<?php

declare(strict_types=1);

namespace App\Command\RegisterVehicle;

use Domain\Vehicle\Vehicle;
use Infra\Repository\FleetRepositoryInterface;

final readonly class RegisterVehicleHandler
{
    public function __construct(
        private FleetRepositoryInterface $fleetRepository,
    ) {}

    public function __invoke(RegisterVehicleCommand $command): void
    {
        $fleet = $this->fleetRepository->findById($command->fleetId) 
            ?? throw new \RuntimeException("Fleet not found: " . $command->fleetId->id());

        // TODO: On peut créer un service si la logique devient plus complexe mais dans ce cas, on peut rester simple.
        $vehicle = new Vehicle($command->vehicleId, $command->vehicleDescription);

        $fleet->addVehicle($vehicle);
        $this->fleetRepository->save($fleet);
    }
}