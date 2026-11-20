<?php

declare(strict_types=1);

namespace Plugins\Content\Services;

use App\Core\Database\DynamicEntityRepository;
use App\Core\Database\QueryBuilder;
use App\Core\Exceptions\ValidationException;

final class PostService
{
    private DynamicEntityRepository $repository;

    private string $entitySlug = 'post';

    public function __construct(?DynamicEntityRepository $repository = null)
    {
        $this->repository = $repository ?? new DynamicEntityRepository();
    }

    public function slugify(string $text): string
    {
        $turkicMap = [
            'ç' => 'c', 'Ç' => 'c',
            'ğ' => 'g', 'Ğ' => 'g',
            'ı' => 'i', 'I' => 'i', 'İ' => 'i',
            'ö' => 'o', 'Ö' => 'o',
            'ş' => 's', 'Ş' => 's',
            'ü' => 'u', 'Ü' => 'u',
        ];

        $text = strtr($text, $turkicMap);
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', (string) $text);

        return trim((string) $text, '-');
    }

    public function getPublishedPosts(int $limit = 10, int $page = 1): array
    {
        $all = $this->repository->allRecords($this->entitySlug);

        $published = [];
        foreach ($all as $record) {
            $data = $record['data'] ?? [];
            if (($data['status'] ?? '') === 'published') {
                $published[] = $this->formatPost($record);
            }
        }

        usort($published, static function (array $a, array $b): int {
            $dateA = (string) ($a['published_at'] ?? '');
            $dateB = (string) ($b['published_at'] ?? '');
            if ($dateA === $dateB) {
                return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
            }
            return strcmp($dateB, $dateA);
        });

        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;

        return array_slice($published, $offset, $limit);
    }

    public function getBySlug(string $slug): ?array
    {
        $records = $this->repository->findRecordsByAttribute($this->entitySlug, 'slug', $slug);

        foreach ($records as $record) {
            $data = $record['data'] ?? [];
            if (($data['status'] ?? '') === 'published') {
                return $this->formatPost($record);
            }
        }

        return null;
    }

    public function getAllPosts(): array
    {
        $all = $this->repository->allRecords($this->entitySlug);
        $posts = [];
        foreach ($all as $record) {
            $posts[] = $this->formatPost($record);
        }

        return $posts;
    }

    public function getPostById(int $id): ?array
    {
        $record = $this->repository->findRecord($id);
        if ($record === null) {
            return null;
        }

        $entity = $this->repository->requireEntity($this->entitySlug);
        if ((int) $record['entity_id'] !== (int) $entity['id']) {
            return null;
        }

        return $this->formatPost($record);
    }

    public function createPost(array $data): int
    {
        $sanitized = $this->prepareData($data, true);

        if (empty($sanitized['title'])) {
            throw new ValidationException('Başlık (title) alanı zorunludur.');
        }

        if (empty($sanitized['slug'])) {
            $sanitized['slug'] = $this->slugify((string) $sanitized['title']);
        } else {
            $sanitized['slug'] = $this->slugify((string) $sanitized['slug']);
        }

        return $this->repository->createRecord($this->entitySlug, $sanitized);
    }

    public function updatePost(int $id, array $data): bool
    {
        $existing = $this->getPostById($id);
        if ($existing === null) {
            throw new ValidationException(sprintf('Post #%d bulunamadı.', $id));
        }

        $sanitized = $this->prepareData($data, false);

        if (array_key_exists('slug', $sanitized) && !empty($sanitized['slug'])) {
            $sanitized['slug'] = $this->slugify((string) $sanitized['slug']);
        } elseif (array_key_exists('title', $sanitized) && !empty($sanitized['title']) && empty($sanitized['slug'])) {
            $sanitized['slug'] = $this->slugify((string) $sanitized['title']);
        }

        return $this->repository->updateRecord($id, $sanitized);
    }

    public function deletePost(int $id): bool
    {
        $entity = $this->repository->requireEntity($this->entitySlug);

        $affected = QueryBuilder::table(DynamicEntityRepository::TABLE_RECORDS)
            ->where('id', '=', $id)
            ->where('entity_id', '=', (int) $entity['id'])
            ->delete();

        return $affected > 0;
    }

    private function prepareData(array $data, bool $isCreate = true): array
    {
        $allowed = ['title', 'slug', 'excerpt', 'banner_image', 'status', 'published_at', 'content_blocks'];
        $prepared = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $prepared[$field] = $data[$field];
            }
        }

        if ($isCreate) {
            $prepared['status'] = $prepared['status'] ?? 'draft';
            if ($prepared['status'] === 'published' && empty($prepared['published_at'])) {
                $prepared['published_at'] = date('Y-m-d');
            }
        } else {
            if (isset($prepared['status']) && $prepared['status'] === 'published' && empty($prepared['published_at'])) {
                $prepared['published_at'] = date('Y-m-d');
            }
        }

        if (array_key_exists('content_blocks', $prepared)) {
            $prepared['content_blocks'] = $this->validateAndFormatBlocks($prepared['content_blocks']);
        }

        return $prepared;
    }

    private function validateAndFormatBlocks($blocks): array
    {
        if (is_string($blocks)) {
            $trimmed = trim($blocks);
            if ($trimmed === '') {
                return [];
            }
            $decoded = json_decode($trimmed, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ValidationException('content_blocks geçerli bir JSON dizisi olmalıdır.');
            }
            $blocks = $decoded;
        }

        if (!is_array($blocks)) {
            throw new ValidationException('content_blocks bir dizi (array) olmalıdır.');
        }

        foreach ($blocks as $index => $block) {
            if (!is_array($block) || !isset($block['type'])) {
                throw new ValidationException(sprintf('content_blocks[%d] içerisinde "type" alanı zorunludur.', $index));
            }
        }

        return $blocks;
    }

    private function formatPost(array $record): array
    {
        $data = $record['data'] ?? [];

        return array_merge([
            'id' => (int) $record['id'],
            'title' => '',
            'slug' => '',
            'excerpt' => '',
            'banner_image' => '',
            'status' => 'draft',
            'published_at' => null,
            'content_blocks' => [],
            'created_at' => $record['created_at'] ?? null,
            'updated_at' => $record['updated_at'] ?? null,
        ], $data, ['id' => (int) $record['id']]);
    }
}
