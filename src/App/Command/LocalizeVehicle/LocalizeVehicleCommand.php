<?php

declare(strict_types=1);

namespace App\Command\LocalizeVehicle;

use Domain\Fleet\FleetId;
use Domain\Vehicle\VehicleId;
use Domain\Location\Location;

final readonly class LocalizeVehicleCommand
{
    public function __construct(
        public FleetId $fleetId,
        public VehicleId $vehicleId,
        public Location $location
    ) {}
}