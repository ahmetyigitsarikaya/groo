<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;
use PDOStatement;

final class QueryBuilder
{
    private ?PDO $pdo;

    private string $table = '';

    private array $columns = ['*'];

    private array $wheres = [];

    private array $bindings = [];

    private array $orders = [];

    private ?int $limit = null;

    private ?int $offset = null;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    public static function table(string $table, ?PDO $pdo = null): self
    {
        $instance = new self($pdo);
        $instance->table = $table;

        return $instance;
    }

    public function select(string ...$columns): self
    {
        $this->columns = $columns !== [] ? $columns : ['*'];

        return $this;
    }

    public function where(string $column, $operator = null, $value = null): self
    {
        if ($value === null && func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = ['AND', sprintf('%s %s ?', $column, $operator)];
        $this->bindings[] = $value;

        return $this;
    }

    public function orWhere(string $column, $operator = null, $value = null): self
    {
        if ($value === null && func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = ['OR', sprintf('%s %s ?', $column, $operator)];
        $this->bindings[] = $value;

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if ($values === []) {
            $this->wheres[] = ['AND', '1 = 0'];

            return $this;
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = ['AND', sprintf('%s IN (%s)', $column, $placeholders)];
        foreach ($values as $value) {
            $this->bindings[] = $value;
        }

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = sprintf('%s %s', $column, $direction);

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function get(): array
    {
        $statement = $this->connection()->prepare($this->toSql());
        $statement->execute($this->bindings);

        return $statement->fetchAll();
    }

    public function first(): ?array
    {
        $this->limit(1);
        $rows = $this->get();

        return $rows[0] ?? null;
    }

    public function find($id, string $key = 'id'): ?array
    {
        return $this->where($key, '=', $id)->first();
    }

    public function count(): int
    {
        $this->columns = ['COUNT(*) AS aggregate'];
        $row = $this->first();

        return (int) ($row['aggregate'] ?? 0);
    }

    public function value(string $column)
    {
        $this->columns = [$column . ' AS value'];
        $row = $this->first();

        return $row['value'] ?? null;
    }

    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', array_fill(0, count($columns), '?'))
        );

        $statement = $this->connection()->prepare($sql);
        $statement->execute(array_values($data));

        return (int) $this->connection()->lastInsertId();
    }

    public function update(array $data): int
    {
        $sets = implode(', ', array_map(static fn (string $column): string => $column . ' = ?', array_keys($data)));
        $sql = sprintf('UPDATE %s SET %s', $this->table, $sets) . $this->compileWheres();

        $statement = $this->connection()->prepare($sql);
        $statement->execute(array_merge(array_values($data), $this->bindings));

        return $statement->rowCount();
    }

    public function delete(): int
    {
        $sql = sprintf('DELETE FROM %s', $this->table) . $this->compileWheres();

        $statement = $this->connection()->prepare($sql);
        $statement->execute($this->bindings);

        return $statement->rowCount();
    }

    public function toSql(): string
    {
        $sql = sprintf('SELECT %s FROM %s', implode(', ', $this->columns), $this->table);
        $sql .= $this->compileWheres();

        if ($this->orders !== []) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    private function compileWheres(): string
    {
        if ($this->wheres === []) {
            return '';
        }

        $sql = '';
        foreach ($this->wheres as $i => [$boolean, $clause]) {
            $sql .= $i === 0 ? ' WHERE ' . $clause : ' ' . $boolean . ' ' . $clause;
        }

        return $sql;
    }

    private function connection(): PDO
    {
        return $this->pdo ?? Connection::getInstance();
    }
}
