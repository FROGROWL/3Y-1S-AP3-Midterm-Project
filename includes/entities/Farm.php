<?php
namespace AgriInfo\Entities;

use InvalidArgumentException;
use PDO;

final class Farm
{
    private const COLUMNS = ['farmer_id', 'farm_name', 'location', 'area_hectares', 'soil_type_id', 'irrigation_type_id'];

    public function __construct(private PDO $pdo) {}

    public function getAll(): array
    {
        return $this->pdo->query('SELECT * FROM farm ORDER BY farm_id')->fetchAll();
    }

    public function getById(int $farmId): array|false
    {
        $statement = $this->pdo->prepare('SELECT * FROM farm WHERE farm_id = :id');
        $statement->execute(['id' => $farmId]);
        return $statement->fetch();
    }

    public function create(array $data): int
    {
        $data = $this->validatedData($data);
        $statement = $this->pdo->prepare(
            'INSERT INTO farm (farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id)
             VALUES (:farmer_id, :farm_name, :location, :area_hectares, :soil_type_id, :irrigation_type_id)
             RETURNING farm_id'
        );
        $statement->execute($data);
        return (int) $statement->fetchColumn();
    }

    public function update(int $farmId, array $data): bool
    {
        $data = $this->validatedData($data);
        $statement = $this->pdo->prepare(
            'UPDATE farm SET farmer_id = :farmer_id, farm_name = :farm_name,
             location = :location, area_hectares = :area_hectares,
             soil_type_id = :soil_type_id, irrigation_type_id = :irrigation_type_id,
             updated_at = now() WHERE farm_id = :id'
        );
        $statement->execute($data + ['id' => $farmId]);
        return $statement->rowCount() > 0;
    }

    public function delete(int $farmId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM farm WHERE farm_id = :id');
        $statement->execute(['id' => $farmId]);
        return $statement->rowCount() > 0;
    }

    private function validatedData(array $data): array
    {
        foreach (self::COLUMNS as $column) {
            $data[$column] ??= null;
        }
        if (array_diff(array_keys($data), self::COLUMNS)) {
            throw new InvalidArgumentException('Unknown farm field.');
        }
        return $data;
    }
}
