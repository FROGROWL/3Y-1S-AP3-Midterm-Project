<?php
namespace AgriInfo\Entities;

use InvalidArgumentException;
use PDO;

final class CropVariety
{
    private const COLUMNS = ['crop_id', 'variety_name', 'growing_period_days', 'growing_season_id'];

    public function __construct(private PDO $pdo) {}

    public function getAll(): array
    {
        return $this->pdo->query('SELECT * FROM crop_variety ORDER BY variety_id')->fetchAll();
    }

    public function getById(int $varietyId): array|false
    {
        $statement = $this->pdo->prepare('SELECT * FROM crop_variety WHERE variety_id = :id');
        $statement->execute(['id' => $varietyId]);
        return $statement->fetch();
    }

    public function create(array $data): int
    {
        $data = $this->validatedData($data);
        $statement = $this->pdo->prepare(
            'INSERT INTO crop_variety (crop_id, variety_name, growing_period_days, growing_season_id)
             VALUES (:crop_id, :variety_name, :growing_period_days, :growing_season_id)
             RETURNING variety_id'
        );
        $statement->execute($data);
        return (int) $statement->fetchColumn();
    }

    public function update(int $varietyId, array $data): bool
    {
        $data = $this->validatedData($data);
        $statement = $this->pdo->prepare(
            'UPDATE crop_variety SET crop_id = :crop_id, variety_name = :variety_name,
             growing_period_days = :growing_period_days, growing_season_id = :growing_season_id,
             updated_at = now() WHERE variety_id = :id'
        );
        $statement->execute($data + ['id' => $varietyId]);
        return $statement->rowCount() > 0;
    }

    public function delete(int $varietyId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM crop_variety WHERE variety_id = :id');
        $statement->execute(['id' => $varietyId]);
        return $statement->rowCount() > 0;
    }

    private function validatedData(array $data): array
    {
        foreach (self::COLUMNS as $column) {
            $data[$column] ??= null;
        }
        if (array_diff(array_keys($data), self::COLUMNS)) {
            throw new InvalidArgumentException('Unknown crop variety field.');
        }
        return $data;
    }
}
