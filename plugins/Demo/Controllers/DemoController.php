<?php

declare(strict_types=1);

namespace Plugins\Demo\Controllers;

use App\Core\Database\DynamicEntityRepository;
use App\Core\Database\QueryBuilder;
use App\Core\Events\EventDispatcher;
use App\Core\Http\ApiResponse;

final class DemoController
{
    public function hello(array $params): void
    {
        $name = $params['name'] ?? ($_GET['name'] ?? null);
        $greeting = EventDispatcher::instance()->dispatch('demo.greeting', $name);

        ApiResponse::success(['greeting' => $greeting], 'OK')->send();
    }

    public function entities(array $params): void
    {
        $entities = QueryBuilder::table(DynamicEntityRepository::TABLE_ENTITIES)
            ->orderBy('id')
            ->get();

        ApiResponse::success(['entities' => $entities])->send();
    }

    public function records(array $params): void
    {
        $repository = new DynamicEntityRepository();

        ApiResponse::success([
            'entity' => $params['slug'],
            'records' => $repository->allRecords((string) $params['slug']),
        ])->send();
    }

    public function createRecord(array $params): void
    {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw !== false ? $raw : '', true);
        if (!is_array($body)) {
            $body = $_POST;
        }

        $repository = new DynamicEntityRepository();
        $id = $repository->createRecord((string) $params['slug'], $body);

        ApiResponse::success(['id' => $id], 'Record created.', 201)->send();
    }

    public function searchByAttribute(array $params): void
    {
        $repository = new DynamicEntityRepository();
        $matches = $repository->findRecordsByAttribute(
            (string) $params['slug'],
            (string) $params['attribute'],
            $_GET['value'] ?? ''
        );

        ApiResponse::success(['matches' => $matches])->send();
    }
}
