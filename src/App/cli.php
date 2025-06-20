<?php

declare(strict_types=1);

use Domain\Fleet\FleetId;
use Domain\Vehicle\Vehicle;
use Domain\Location\Location;
use Domain\Vehicle\VehicleId;
use App\Command\Fleet\CreateFleetCommand;
use App\Command\Fleet\CreateFleetHandler;
use Infra\Persistence\InMemoryFleetRepository;
use App\Command\LocalizeVehicle\LocalizeVehicleCommand;
use App\Command\LocalizeVehicle\LocalizeVehicleHandler;
use App\Command\RegisterVehicle\RegisterVehicleCommand;
use App\Command\RegisterVehicle\RegisterVehicleHandler;

require_once __DIR__ . '/../../vendor/autoload.php';

final readonly class CliApplication
{
    public function __construct(
        private InMemoryFleetRepository $repository = new InMemoryFleetRepository()
    ) {}

    public function run(array $argv): void
    {
        array_shift($argv); // Remove script name
        $command = $argv[0] ?? throw new InvalidArgumentException('No command provided');

        match ($command) {
            'test-all' => $this->handleTestAll(),
            'create' => $this->handleCreate($argv[1] ?? null),
            'register-vehicle' => $this->handleRegisterVehicle(
                $argv[1] ?? null, 
                $argv[2] ?? null
            ),
            'localize-vehicle' => $this->handleLocalizeVehicle($argv),
            default => $this->showHelp()
        };
    }

    private function handleTestAll(): void
    {
        $userId = '123';
        $vehiclePlate = 'ABC123';

        // Create fleet
        $createHandler = new CreateFleetHandler($this->repository);
        $createCommand = new CreateFleetCommand($userId);
        $fleetId = $createHandler->handle($createCommand);
        echo "Fleet created with ID: {$fleetId->id()}\n";

        // Register vehicle
        $vehicleId = new VehicleId($vehiclePlate, strtolower($vehiclePlate));
        $registerCommand = new RegisterVehicleCommand(
            fleetId: $fleetId,
            vehicleId: $vehicleId,
            vehicleDescription: "Vehicle {$vehiclePlate}"
        );
        $registerHandler = new RegisterVehicleHandler($this->repository);
        
        try {
            $registerHandler($registerCommand);
            echo "Vehicle {$vehiclePlate} registered in fleet {$fleetId->id()}\n";
        } catch (Exception $e) {
            echo "Error during registration: {$e->getMessage()}\n";
            return;
        }

        // Localize vehicle
        $location = new Location("marseille", "2.123", "0.0");
        $localizeCommand = new LocalizeVehicleCommand($fleetId, $vehicleId, $location);
        $localizeHandler = new LocalizeVehicleHandler($this->repository);

        try {
            $localizeHandler($localizeCommand);
            echo "Vehicle {$vehiclePlate} localized to {$location}\n";
        } catch (Exception $e) {
            echo "Error during localization: {$e->getMessage()}\n";
        }
    }

    private function handleCreate(?string $userId): void
    {
        $userId ??= throw new InvalidArgumentException("Usage: php cli.php create <userId>");

        $handler = new CreateFleetHandler($this->repository);
        $createCommand = new CreateFleetCommand($userId);
        $fleetId = $handler->handle($createCommand);

        echo "Fleet created with ID: {$fleetId->id()}\n";
    }

    private function handleRegisterVehicle(?string $fleetIdRaw, ?string $vehiclePlate): void
    {
        if ($fleetIdRaw === null || $vehiclePlate === null) {
            throw new InvalidArgumentException("Usage: php cli.php register-vehicle <fleetId> <vehiclePlateNumber>");
        }

        $handler = new RegisterVehicleHandler($this->repository);
        $fleetId = new FleetId(id: $fleetIdRaw);
        $vehicleId = new VehicleId($vehiclePlate, strtolower($vehiclePlate));
        $command = new RegisterVehicleCommand(
            $fleetId, 
            $vehicleId, 
            "Vehicle {$vehiclePlate}"
        );

        try {
            $handler($command);
            echo "Vehicle {$vehiclePlate} registered in fleet {$fleetIdRaw}\n";
        } catch (DomainException $e) {
            echo "Error: {$e->getMessage()}\n";
        }
    }

    private function handleLocalizeVehicle(array $argv): void
    {
        if (count($argv) < 5) {
            throw new InvalidArgumentException("Usage: php cli.php localize-vehicle <fleetId> <vehiclePlate> <lat> <lng> [alt]");
        }

        $fleetId = new FleetId($argv[1]);
        $vehicleId = new VehicleId($argv[2], strtolower($argv[2]));
        $lat = (float) $argv[3];
        $lng = (float) $argv[4];
        $alt = isset($argv[5]) ? (float) $argv[5] : null;

        $location = new Location($lat, $lng, $alt);
        $handler = new LocalizeVehicleHandler($this->repository);
        $command = new LocalizeVehicleCommand($fleetId, $vehicleId, $location);

        try {
            $handler($command);
            echo "Vehicle localized successfully\n";
        } catch (Exception $e) {
            echo "Error: {$e->getMessage()}\n";
        }
    }

    private function showHelp(): never
    {
        echo "Unknown command.\n";
        echo "Usage:\n";
        echo "  php cli.php test-all\n";
        echo "  php cli.php create <userId>\n";
        echo "  php cli.php register-vehicle <fleetId> <vehiclePlateNumber>\n";
        echo "  php cli.php localize-vehicle <fleetId> <vehiclePlate> <lat> <lng> [alt]\n";
        exit(1);
    }
}

// Application entry point
try {
    $app = new CliApplication();
    $app->run($_SERVER['argv'] ?? []);
} catch (InvalidArgumentException $e) {
    echo "Error: {$e->getMessage()}\n";
    exit(1);
} catch (Throwable $e) {
    echo "Unexpected error: {$e->getMessage()}\n";
    exit(1);
}