<?php
declare(strict_types=1);

final class Install_003_content_tables
{
    public function up(PDO $pdo): void
    {
        $repo = new \App\Core\Database\DynamicEntityRepository($pdo);
        if ($repo->getEntity('post') === null) {
            $repo->createEntity('post', 'Blog Post', 'İçerik Yönetimi - Blog Yazısı');
            $repo->addAttribute('post', 'title', 'string', true);
            $repo->addAttribute('post', 'slug', 'string', true);
            $repo->addAttribute('post', 'excerpt', 'text', false);
            $repo->addAttribute('post', 'banner_image', 'string', false);
            $repo->addAttribute('post', 'status', 'string', false, ['draft', 'published']);
            $repo->addAttribute('post', 'published_at', 'date', false);
            $repo->addAttribute('post', 'content_blocks', 'json', false);
        }
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS posts'); // Legacy fallback
        
        try {
            $repo = new \App\Core\Database\DynamicEntityRepository($pdo);
            $entity = $repo->getEntity('post');
            if ($entity) {
                $pdo->exec("DELETE FROM entity_records WHERE entity_id = " . (int)$entity['id']);
                $pdo->exec("DELETE FROM attributes WHERE entity_id = " . (int)$entity['id']);
                $pdo->exec("DELETE FROM entities WHERE id = " . (int)$entity['id']);
            }
        } catch (\Throwable $e) {}
    }
}
