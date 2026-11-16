<?php
declare(strict_types=1);

namespace AgriInfo\Entities;

use InvalidArgumentException;
use PDO;

abstract class Entity
{
    protected const TABLE = '';
    protected const PRIMARY_KEY = '';
    protected const COLUMNS = [];

    public function __construct(protected PDO $pdo)
    {
    }

    public function getAll(): array
    {
        return $this->pdo->query(
            'SELECT * FROM ' . $this->identifier(static::TABLE)
            . ' ORDER BY ' . $this->identifier(static::PRIMARY_KEY)
        )->fetchAll();
    }

    public function getById(int $id): array|false
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM ' . $this->identifier(static::TABLE)
            . ' WHERE ' . $this->identifier(static::PRIMARY_KEY) . ' = :id'
        );
        $statement->execute(['id' => $id]);
        return $statement->fetch();
    }

    public function create(array $data): int
    {
        $data = $this->validatedData($data);
        if (!$data) {
            throw new InvalidArgumentException('Cannot create a record without fields.');
        }

        $columns = array_keys($data);
        $sql = 'INSERT INTO ' . $this->identifier(static::TABLE)
            . ' (' . implode(', ', array_map($this->identifier(...), $columns)) . ')'
            . ' VALUES (' . implode(', ', array_map(static fn (string $column): string => ':' . $column, $columns)) . ')'
            . ' RETURNING ' . $this->identifier(static::PRIMARY_KEY);
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);
        return (int) $statement->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->validatedData($data);
        if (!$data) {
            throw new InvalidArgumentException('Cannot update a record without fields.');
        }

        $assignments = array_map(
            fn (string $column): string => $this->identifier($column) . ' = :' . $column,
            array_keys($data)
        );
        $data['entity_id'] = $id;
        $statement = $this->pdo->prepare(
            'UPDATE ' . $this->identifier(static::TABLE)
            . ' SET ' . implode(', ', $assignments) . ', updated_at = now()'
            . ' WHERE ' . $this->identifier(static::PRIMARY_KEY) . ' = :entity_id'
        );
        $statement->execute($data);
        return $statement->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM ' . $this->identifier(static::TABLE)
            . ' WHERE ' . $this->identifier(static::PRIMARY_KEY) . ' = :id'
        );
        $statement->execute(['id' => $id]);
        return $statement->rowCount() > 0;
    }

    private function validatedData(array $data): array
    {
        foreach ($data as $column => $_value) {
            if (!in_array($column, static::COLUMNS, true)) {
                throw new InvalidArgumentException('Unknown field for ' . static::TABLE . ': ' . $column);
            }
        }
        return $data;
    }

    private function identifier(string $name): string
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $name)) {
            throw new InvalidArgumentException('Invalid SQL identifier.');
        }
        return '"' . $name . '"';
    }
}
