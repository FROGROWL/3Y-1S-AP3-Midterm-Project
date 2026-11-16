<?php
namespace AgriInfo\Entities;

use InvalidArgumentException;
use PDO;

final class Crop
{
    private const COLUMNS = ['crop_name', 'crop_category_id'];

    public function __construct(private PDO $pdo) {}

    public function getAll(): array
    {
        return $this->pdo->query('SELECT * FROM crop ORDER BY crop_id')->fetchAll();
    }

    public function getById(int $cropId): array|false
    {
        $statement = $this->pdo->prepare('SELECT * FROM crop WHERE crop_id = :id');
        $statement->execute(['id' => $cropId]);
        return $statement->fetch();
    }

    public function create(array $data): int
    {
        $data = $this->validatedData($data);
        $statement = $this->pdo->prepare(
            'INSERT INTO crop (crop_name, crop_category_id)
             VALUES (:crop_name, :crop_category_id) RETURNING crop_id'
        );
        $statement->execute($data);
        return (int) $statement->fetchColumn();
    }

    public function update(int $cropId, array $data): bool
    {
        $data = $this->validatedData($data);
        $statement = $this->pdo->prepare(
            'UPDATE crop SET crop_name = :crop_name, crop_category_id = :crop_category_id,
             updated_at = now() WHERE crop_id = :id'
        );
        $statement->execute($data + ['id' => $cropId]);
        return $statement->rowCount() > 0;
    }

    public function delete(int $cropId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM crop WHERE crop_id = :id');
        $statement->execute(['id' => $cropId]);
        return $statement->rowCount() > 0;
    }

    private function validatedData(array $data): array
    {
        foreach (self::COLUMNS as $column) {
            $data[$column] ??= null;
        }
        if (array_diff(array_keys($data), self::COLUMNS)) {
            throw new InvalidArgumentException('Unknown crop field.');
        }
        return $data;
    }
}
