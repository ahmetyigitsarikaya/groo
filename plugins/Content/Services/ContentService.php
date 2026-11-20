<?php

declare(strict_types=1);

namespace Plugins\Content\Services;

use App\Core\Database\DynamicEntityRepository;
use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\ValidationException;
use Throwable;

final class ContentService
{
    public const ENTITY = 'post';

    private DynamicEntityRepository $repository;

    public function __construct(?DynamicEntityRepository $repository = null)
    {
        $this->repository = $repository ?? new DynamicEntityRepository();
    }

    public function getRepository(): DynamicEntityRepository
    {
        return $this->repository;
    }

    public function getPublishedPosts(): array
    {
        try {
            $records = $this->repository->allRecords(self::ENTITY);
        } catch (Throwable $e) {
            return [];
        }

        $published = [];
        foreach ($records as $record) {
            $data = $record['data'] ?? [];
            if (($data['status'] ?? '') === 'published') {
                $published[] = $record;
            }
        }

        usort($published, static function (array $a, array $b): int {
            $dateA = (string) ($a['data']['published_at'] ?? '');
            $dateB = (string) ($b['data']['published_at'] ?? '');

            return strcmp($dateB, $dateA);
        });

        return $published;
    }

    public function getPublishedPostBySlug(string $slug): array
    {
        $matches = $this->repository->findRecordsByAttribute(self::ENTITY, 'slug', $slug);

        foreach ($matches as $record) {
            if ((($record['data'] ?? [])['status'] ?? '') === 'published') {
                return $record;
            }
        }

        throw new NotFoundException(sprintf('Published post with slug "%s" was not found.', $slug));
    }

    public function getRecord(int $id): array
    {
        $record = $this->repository->findRecord($id);

        if ($record === null) {
            throw new NotFoundException(sprintf('Post #%d was not found.', $id));
        }

        return $record;
    }

    public function allPosts(): array
    {
        try {
            return $this->repository->allRecords(self::ENTITY);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function generateSlug(string $title, ?string $existingSlug = null): string
    {
        if ($existingSlug !== null && trim($existingSlug) !== '') {
            return $this->slugify($existingSlug);
        }

        return $this->slugify($title);
    }

    public function createPost(array $input): int
    {
        $data = $this->normalize($input);
        $data['slug'] = $this->generateSlug((string) $data['title'], $input['slug'] ?? null);

        return $this->repository->createRecord(self::ENTITY, $data);
    }

    public function updatePost(int $id, array $input): void
    {
        $record = $this->getRecord($id);
        $existing = $record['data'] ?? [];

        $data = $this->normalize($input, $existing);
        $data['slug'] = $this->generateSlug((string) $data['title'], $input['slug'] ?? ($existing['slug'] ?? null));

        $this->repository->updateRecord($id, $data);
    }

    private function normalize(array $input, array $existing = []): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') {
            throw new ValidationException('Baslik alani zorunludur.');
        }

        $status = (string) ($input['status'] ?? ($existing['status'] ?? 'draft'));
        if (!in_array($status, ['draft', 'published'], true)) {
            $status = 'draft';
        }

        $blocks = $input['content_blocks'] ?? ($existing['content_blocks'] ?? []);
        if (is_string($blocks)) {
            $decoded = json_decode($blocks, true);
            $blocks = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($blocks)) {
            $blocks = [];
        }

        $publishedAt = $input['published_at'] ?? ($existing['published_at'] ?? null);
        if ($publishedAt !== null) {
            $publishedAt = trim((string) $publishedAt);
            if ($publishedAt === '') {
                $publishedAt = null;
            }
        }

        return [
            'title' => $title,
            'slug' => '',
            'excerpt' => trim((string) ($input['excerpt'] ?? ($existing['excerpt'] ?? ''))),
            'banner_image' => trim((string) ($input['banner_image'] ?? ($existing['banner_image'] ?? ''))),
            'status' => $status,
            'published_at' => $publishedAt,
            'content_blocks' => array_values($blocks),
        ];
    }

    private function slugify(string $value): string
    {
        $map = [
            'ç' => 'c', 'Ç' => 'c',
            'ğ' => 'g', 'Ğ' => 'g',
            'ı' => 'i', 'I' => 'i', 'İ' => 'i',
            'ö' => 'o', 'Ö' => 'o',
            'ş' => 's', 'Ş' => 's',
            'ü' => 'u', 'Ü' => 'u',
        ];

        $value = strtr($value, $map);
        $value = mb_strtolower($value, 'UTF-8');
        $value = (string) preg_replace('/[^a-z0-9]+/u', '-', $value);
        $value = trim($value, '-');

        return $value !== '' ? $value : 'post';
    }
}
