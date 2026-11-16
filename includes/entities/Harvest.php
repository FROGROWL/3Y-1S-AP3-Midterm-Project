<?php
namespace AgriInfo\Entities;

use InvalidArgumentException;
use PDO;

final class Harvest
{
    private const COLUMNS = ['planting_id', 'harvest_date', 'quantity_kg', 'selling_price_per_kg', 'quality_grade_id', 'remark_id'];

    public function __construct(private PDO $pdo) {}

    public function getAll(): array
    {
        return $this->pdo->query('SELECT * FROM harvest ORDER BY harvest_id')->fetchAll();
    }

    public function getById(int $harvestId): array|false
    {
        $statement = $this->pdo->prepare('SELECT * FROM harvest WHERE harvest_id = :id');
        $statement->execute(['id' => $harvestId]);
        return $statement->fetch();
    }

    public function create(array $data): int
    {
        $data = $this->validatedData($data);
        $statement = $this->pdo->prepare(
            'INSERT INTO harvest (planting_id, harvest_date, quantity_kg, selling_price_per_kg, quality_grade_id, remark_id)
             VALUES (:planting_id, :harvest_date, :quantity_kg, :selling_price_per_kg, :quality_grade_id, :remark_id)
             RETURNING harvest_id'
        );
        $statement->execute($data);
        return (int) $statement->fetchColumn();
    }

    public function update(int $harvestId, array $data): bool
    {
        $data = $this->validatedData($data);
        $statement = $this->pdo->prepare(
            'UPDATE harvest SET planting_id = :planting_id, harvest_date = :harvest_date,
             quantity_kg = :quantity_kg, selling_price_per_kg = :selling_price_per_kg,
             quality_grade_id = :quality_grade_id, remark_id = :remark_id,
             updated_at = now() WHERE harvest_id = :id'
        );
        $statement->execute($data + ['id' => $harvestId]);
        return $statement->rowCount() > 0;
    }

    public function delete(int $harvestId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM harvest WHERE harvest_id = :id');
        $statement->execute(['id' => $harvestId]);
        return $statement->rowCount() > 0;
    }

    private function validatedData(array $data): array
    {
        foreach (self::COLUMNS as $column) {
            $data[$column] ??= null;
        }
        if (array_diff(array_keys($data), self::COLUMNS)) {
            throw new InvalidArgumentException('Unknown harvest field.');
        }
        return $data;
    }
}
