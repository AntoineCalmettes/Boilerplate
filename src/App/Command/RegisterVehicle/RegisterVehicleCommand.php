<?php

declare(strict_types=1);

namespace App\Command\RegisterVehicle;

use Domain\Fleet\FleetId;
use Domain\Vehicle\VehicleId;

final readonly class RegisterVehicleCommand
{
    public function __construct(
        public FleetId $fleetId,
        public VehicleId $vehicleId,
        public string $vehicleDescription,
    ) {}
}