<?php
namespace AgriInfo\Entities;

use InvalidArgumentException;
use PDO;

final class Planting
{
    private const COLUMNS = ['farm_id', 'variety_id', 'planting_date', 'area_planted', 'seed_quantity', 'expected_harvest_date', 'status_id'];

    public function __construct(private PDO $pdo) {}

    public function getAll(): array
    {
        return $this->pdo->query('SELECT * FROM planting ORDER BY planting_id')->fetchAll();
    }

    public function getById(int $plantingId): array|false
    {
        $statement = $this->pdo->prepare('SELECT * FROM planting WHERE planting_id = :id');
        $statement->execute(['id' => $plantingId]);
        return $statement->fetch();
    }

    public function create(array $data): int
    {
        $data = $this->validatedData($data);
        $statement = $this->pdo->prepare(
            'INSERT INTO planting (farm_id, variety_id, planting_date, area_planted, seed_quantity, expected_harvest_date, status_id)
             VALUES (:farm_id, :variety_id, :planting_date, :area_planted, :seed_quantity, :expected_harvest_date, :status_id)
             RETURNING planting_id'
        );
        $statement->execute($data);
        return (int) $statement->fetchColumn();
    }

    public function update(int $plantingId, array $data): bool
    {
        $data = $this->validatedData($data);
        $statement = $this->pdo->prepare(
            'UPDATE planting SET farm_id = :farm_id, variety_id = :variety_id,
             planting_date = :planting_date, area_planted = :area_planted,
             seed_quantity = :seed_quantity, expected_harvest_date = :expected_harvest_date,
             status_id = :status_id, updated_at = now() WHERE planting_id = :id'
        );
        $statement->execute($data + ['id' => $plantingId]);
        return $statement->rowCount() > 0;
    }

    public function delete(int $plantingId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM planting WHERE planting_id = :id');
        $statement->execute(['id' => $plantingId]);
        return $statement->rowCount() > 0;
    }

    private function validatedData(array $data): array
    {
        foreach (self::COLUMNS as $column) {
            $data[$column] ??= null;
        }
        if (array_diff(array_keys($data), self::COLUMNS)) {
            throw new InvalidArgumentException('Unknown planting field.');
        }
        return $data;
    }
}
