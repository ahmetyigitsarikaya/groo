<?php

declare(strict_types=1);

use App\Core\Database\DynamicEntityRepository;
use App\Core\Database\QueryBuilder;

use App\Core\Database\Connection;

return new class {
    public function up(): void
    {
        $pdo = Connection::getInstance();
        $repository = new DynamicEntityRepository($pdo);

        $existing = QueryBuilder::table(DynamicEntityRepository::TABLE_ENTITIES)
            ->where('slug', '=', 'contact')
            ->first();

        if ($existing !== null) {
            return;
        }

        $repository->createEntity('contact', 'Contact', 'Demo dynamic entity created by the Demo plugin.');
        $repository->addAttribute('contact', 'name', 'string', true);
        $repository->addAttribute('contact', 'email', 'string', true);
        $repository->addAttribute('contact', 'age', 'integer', false);
        $repository->addAttribute('contact', 'tags', 'json', false);
    }

    public function down(): void
    {
        $pdo = Connection::getInstance();
        QueryBuilder::table(DynamicEntityRepository::TABLE_ENTITIES)
            ->where('slug', '=', 'contact')
            ->delete();
    }
}

;