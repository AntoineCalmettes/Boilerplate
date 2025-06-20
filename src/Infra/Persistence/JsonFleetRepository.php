<?php
declare(strict_types=1);

namespace Infra\Persistence;

use Domain\Repository\FleetRepositoryInterface;
use Domain\Fleet\Fleet;
use Domain\Fleet\FleetId;

final class JsonFleetRepository implements FleetRepositoryInterface
{
    private string $filePath;

    /** @var Fleet[] */
    private array $fleets = [];

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->load();
    }

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
        $fleets = $this->load();
    
        $fleets[$fleet->id()->id()] = serialize($fleet);
    
        file_put_contents($this->filePath, serialize($fleets));
    }

    private function load(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }
        $data = file_get_contents($this->filePath);
        $fleets = unserialize($data);

        if (!is_array($fleets)) {
            return [];
        }
    
        return $fleets; 
    }

    public function findAll(): array
    {
        $json = file_get_contents($this->filePath); 
        $data = json_decode($json, true); 

        if (!is_array($data)) {
            return [];
        }

        return array_map(fn($fleetData) => Fleet::fromArray($fleetData), $data);
    }
}
