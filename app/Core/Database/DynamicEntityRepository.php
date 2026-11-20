<?php

declare(strict_types=1);

namespace App\Core\Database;

use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\ValidationException;
use DateTime;
use PDO;

final class DynamicEntityRepository
{
    public const TABLE_ENTITIES = 'entities';

    public const TABLE_ATTRIBUTES = 'attributes';

    public const TABLE_RECORDS = 'entity_records';

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
    }

    public function createEntity(string $slug, string $name, string $description = ''): int
    {
        return QueryBuilder::table(self::TABLE_ENTITIES, $this->pdo)->insert([
            'slug' => strtolower($slug),
            'name' => $name,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getEntity(string $slug): ?array
    {
        return QueryBuilder::table(self::TABLE_ENTITIES, $this->pdo)
            ->where('slug', '=', strtolower($slug))
            ->first();
    }

    public function requireEntity(string $slug): array
    {
        $entity = $this->getEntity($slug);
        if ($entity === null) {
            throw new NotFoundException(sprintf('Entity "%s" does not exist.', $slug));
        }

        return $entity;
    }

    public function addAttribute(string $entitySlug, string $code, string $type, bool $required = false, array $options = []): int
    {
        $entity = $this->requireEntity($entitySlug);

        return QueryBuilder::table(self::TABLE_ATTRIBUTES, $this->pdo)->insert([
            'entity_id' => $entity['id'],
            'code' => $code,
            'type' => strtolower($type),
            'required' => $required ? 1 : 0,
            'options' => json_encode($options, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getAttributes(string $entitySlug): array
    {
        $entity = $this->requireEntity($entitySlug);

        $attributes = QueryBuilder::table(self::TABLE_ATTRIBUTES, $this->pdo)
            ->where('entity_id', '=', $entity['id'])
            ->orderBy('id')
            ->get();

        foreach ($attributes as &$attribute) {
            $attribute['options'] = json_decode((string) ($attribute['options'] ?? '[]'), true) ?: [];
            $attribute['required'] = (bool) $attribute['required'];
        }

        return $attributes;
    }

    public function createRecord(string $entitySlug, array $data): int
    {
        $entity = $this->requireEntity($entitySlug);
        $this->validateData($entitySlug, $data);

        return QueryBuilder::table(self::TABLE_RECORDS, $this->pdo)->insert([
            'entity_id' => $entity['id'],
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateRecord(int $recordId, array $data): bool
    {
        $record = $this->findRecord($recordId);
        if ($record === null) {
            throw new NotFoundException(sprintf('Record #%d does not exist.', $recordId));
        }

        $entity = QueryBuilder::table(self::TABLE_ENTITIES, $this->pdo)->find($record['entity_id']);
        $this->validateData((string) $entity['slug'], $data, false);

        $merged = array_merge($record['data'], $data);
        $this->validateData((string) $entity['slug'], $merged);

        $affected = QueryBuilder::table(self::TABLE_RECORDS, $this->pdo)
            ->where('id', '=', $recordId)
            ->update([
                'data' => json_encode($merged, JSON_UNESCAPED_UNICODE),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $affected > 0;
    }

    public function findRecord(int $recordId): ?array
    {
        $row = QueryBuilder::table(self::TABLE_RECORDS, $this->pdo)->find($recordId);
        if ($row === null) {
            return null;
        }

        $row['data'] = json_decode((string) $row['data'], true) ?: [];

        return $row;
    }

    public function allRecords(string $entitySlug): array
    {
        $entity = $this->requireEntity($entitySlug);

        $rows = QueryBuilder::table(self::TABLE_RECORDS, $this->pdo)
            ->where('entity_id', '=', $entity['id'])
            ->orderBy('id', 'DESC')
            ->get();

        return array_map(static function (array $row): array {
            $row['data'] = json_decode((string) $row['data'], true) ?: [];
            return $row;
        }, $rows);
    }

    public function findRecordsByAttribute(string $entitySlug, string $code, $value): array
    {
        $entity = $this->requireEntity($entitySlug);

        $sql = sprintf(
            'SELECT * FROM %s WHERE entity_id = ? AND JSON_UNQUOTE(JSON_EXTRACT(data, ?)) = ?',
            self::TABLE_RECORDS
        );

        $statement = $this->pdo->prepare($sql);
        $statement->execute([$entity['id'], '$.' . $code, (string) $value]);

        return array_map(static function (array $row): array {
            $row['data'] = json_decode((string) $row['data'], true) ?: [];
            return $row;
        }, $statement->fetchAll());
    }

    public function validateData(string $entitySlug, array $data, bool $requireAllRequired = true): void
    {
        $attributes = $this->getAttributes($entitySlug);
        $errors = [];

        $known = [];
        foreach ($attributes as $attribute) {
            $code = (string) $attribute['code'];
            $known[] = $code;

            if ($requireAllRequired && $attribute['required'] && !array_key_exists($code, $data)) {
                $errors[$code] = sprintf('Attribute "%s" is required.', $code);
                continue;
            }

            if (array_key_exists($code, $data) && !$this->matchesType((string) $attribute['type'], $data[$code])) {
                $errors[$code] = sprintf('Attribute "%s" must be of type "%s".', $code, $attribute['type']);
            }
        }

        foreach (array_keys($data) as $key) {
            if (!in_array((string) $key, $known, true)) {
                $errors[(string) $key] = sprintf('Unknown attribute "%s".', $key);
            }
        }

        if ($errors !== []) {
            throw new ValidationException('Invalid record data.', $errors);
        }
    }

    private function matchesType(string $type, $value): bool
    {
        if ($value === null) {
            return true;
        }

        switch ($type) {
            case 'string':
            case 'text':
                return is_string($value);
            case 'integer':
            case 'int':
                return is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1);
            case 'float':
            case 'number':
            case 'decimal':
                return is_int($value) || is_float($value) || (is_string($value) && is_numeric($value));
            case 'boolean':
            case 'bool':
                return is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true);
            case 'date':
                return is_string($value) && DateTime::createFromFormat('Y-m-d', $value) !== false;
            case 'datetime':
                return is_string($value) && strtotime($value) !== false;
            case 'json':
            case 'array':
            case 'object':
                return is_array($value);
            default:
                return true;
        }
    }
}
