<?php
namespace AgriInfo\Entities;

use InvalidArgumentException;
use PDO;

final class Farmer
{
    private const COLUMNS = ['first_name', 'last_name', 'contact_number', 'address', 'date_registered'];

    public function __construct(private PDO $pdo) {}

    public function getAll(): array
    {
        return $this->pdo->query('SELECT * FROM farmer ORDER BY farmer_id')->fetchAll();
    }

    public function getById(int $farmerId): array|false
    {
        $statement = $this->pdo->prepare('SELECT * FROM farmer WHERE farmer_id = :id');
        $statement->execute(['id' => $farmerId]);
        return $statement->fetch();
    }

    public function create(array $data): int
    {
        $data = $this->validatedData($data);
        $statement = $this->pdo->prepare(
            'INSERT INTO farmer (first_name, last_name, contact_number, address, date_registered)
             VALUES (:first_name, :last_name, :contact_number, :address, :date_registered)
             RETURNING farmer_id'
        );
        $statement->execute($data);
        return (int) $statement->fetchColumn();
    }

    public function update(int $farmerId, array $data): bool
    {
        $data = $this->validatedData($data);
        $statement = $this->pdo->prepare(
            'UPDATE farmer SET first_name = :first_name, last_name = :last_name,
             contact_number = :contact_number, address = :address,
             date_registered = :date_registered, updated_at = now()
             WHERE farmer_id = :id'
        );
        $statement->execute($data + ['id' => $farmerId]);
        return $statement->rowCount() > 0;
    }

    public function delete(int $farmerId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM farmer WHERE farmer_id = :id');
        $statement->execute(['id' => $farmerId]);
        return $statement->rowCount() > 0;
    }

    private function validatedData(array $data): array
    {
        foreach (self::COLUMNS as $column) {
            $data[$column] ??= null;
        }
        if (array_diff(array_keys($data), self::COLUMNS)) {
            throw new InvalidArgumentException('Unknown farmer field.');
        }
        return $data;
    }
}
