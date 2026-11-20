<?php

declare(strict_types=1);

namespace Plugins\Admin\Controllers;

use App\Core\Database\DynamicEntityRepository;
use App\Core\Database\QueryBuilder;
use App\Core\Exceptions\ValidationException;
use Throwable;

final class EntitiesController extends BaseController
{
    public function list(array $params): void
    {
        $repository = new DynamicEntityRepository();
        $entity = $repository->requireEntity((string) $params['entity_type']);
        $attributes = $repository->getAttributes((string) $params['entity_type']);
        $records = $repository->allRecords((string) $params['entity_type']);

        $this->render('pages/admin/entities-list', [
            'entity' => $entity,
            'attributes' => $attributes,
            'records' => $records,
        ]);
    }

    public function create(array $params): void
    {
        $repository = new DynamicEntityRepository();
        $entity = $repository->requireEntity((string) $params['entity_type']);
        $attributes = $repository->getAttributes((string) $params['entity_type']);

        $this->render('pages/admin/entity-form', [
            'entity' => $entity,
            'attributes' => $attributes,
            'record' => null,
            'values' => [],
        ]);
    }

    public function edit(array $params): void
    {
        $repository = new DynamicEntityRepository();
        $entity = $repository->requireEntity((string) $params['entity_type']);
        $attributes = $repository->getAttributes((string) $params['entity_type']);
        $record = $repository->findRecord((int) $params['id']);

        if ($record === null || (int) $record['entity_id'] !== (int) $entity['id']) {
            throw new ValidationException('Kayit bulunamadi.');
        }

        $this->render('pages/admin/entity-form', [
            'entity' => $entity,
            'attributes' => $attributes,
            'record' => $record,
            'values' => $record['data'] ?? [],
        ]);
    }

    public function store(array $params): void
    {
        $this->requireCsrf();

        $repository = new DynamicEntityRepository();
        $repository->requireEntity((string) $params['entity_type']);
        $data = $this->collectFormData((string) $params['entity_type']);

        try {
            $repository->createRecord((string) $params['entity_type'], $data);
            $this->flashAndRedirect((string) $params['entity_type'], 'Kayit basariyla olusturuldu.');
        } catch (ValidationException $e) {
            $this->flashAndRedirect((string) $params['entity_type'], 'Dogrulama hatasi: ' . $e->getMessage());
        }
    }

    public function update(array $params): void
    {
        $this->requireCsrf();

        $repository = new DynamicEntityRepository();
        $entity = $repository->requireEntity((string) $params['entity_type']);
        $record = $repository->findRecord((int) $params['id']);

        if ($record === null || (int) $record['entity_id'] !== (int) $entity['id']) {
            throw new ValidationException('Kayit bulunamadi.');
        }

        $data = $this->collectFormData((string) $params['entity_type']);

        try {
            $repository->updateRecord((int) $params['id'], $data);
            $this->flashAndRedirect((string) $params['entity_type'], 'Kayit basariyla guncellendi.');
        } catch (ValidationException $e) {
            $this->flashAndRedirect((string) $params['entity_type'], 'Dogrulama hatasi: ' . $e->getMessage());
        }
    }

    public function delete(array $params): void
    {
        $this->requireCsrf();

        $repository = new DynamicEntityRepository();
        $entity = $repository->requireEntity((string) $params['entity_type']);

        $affected = QueryBuilder::table(DynamicEntityRepository::TABLE_RECORDS)
            ->where('id', '=', (int) $params['id'])
            ->where('entity_id', '=', (int) $entity['id'])
            ->delete();

        $message = $affected > 0 ? 'Kayit silindi.' : 'Kayit silinemedi.';
        $this->flashAndRedirect((string) $params['entity_type'], $message);
    }

    private function collectFormData(string $entityType): array
    {
        $repository = new DynamicEntityRepository();
        $attributes = $repository->getAttributes($entityType);
        $input = $_POST['fields'] ?? [];
        $data = [];

        foreach ($attributes as $attribute) {
            $code = (string) $attribute['code'];
            $raw = is_array($input) && array_key_exists($code, $input) ? $input[$code] : null;

            if (!is_string($raw)) {
                $raw = $raw === null ? null : (string) $raw;
            }

            switch ((string) $attribute['type']) {
                case 'boolean':
                case 'bool':
                    $data[$code] = in_array($raw, ['1', 'on', 'true', true], true);
                    break;
                case 'json':
                case 'array':
                case 'object':
                    if ($raw === null || trim($raw) === '') {
                        $data[$code] = null;
                        break;
                    }
                    $decoded = json_decode($raw, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new ValidationException(sprintf('"%s" alani gecerli bir JSON icermiyor.', $code));
                    }
                    $data[$code] = $decoded;
                    break;
                default:
                    $data[$code] = ($raw === null || $raw === '') ? null : $raw;
            }
        }

        return $data;
    }

    private function flashAndRedirect(string $entityType, string $message): void
    {
        \Plugins\Admin\Support\Flash::set('success', $message);
        $this->redirect('/admin/entities/' . rawurlencode($entityType));
    }
}
