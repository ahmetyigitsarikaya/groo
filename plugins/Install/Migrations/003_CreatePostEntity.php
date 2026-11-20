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

        if ($repository->getEntity('post') !== null) {
            return;
        }

        $repository->createEntity('post', 'Post', 'Blog / icerik yazilari (Content plugin).');
        $repository->addAttribute('post', 'title', 'string', true);
        $repository->addAttribute('post', 'slug', 'string', true);
        $repository->addAttribute('post', 'excerpt', 'string', false);
        $repository->addAttribute('post', 'banner_image', 'string', false);
        $repository->addAttribute('post', 'status', 'string', true, ['choices' => ['draft', 'published']]);
        $repository->addAttribute('post', 'published_at', 'date', false);
        $repository->addAttribute('post', 'content_blocks', 'json', false);

        $hasRecords = QueryBuilder::table(DynamicEntityRepository::TABLE_RECORDS, $pdo)
            ->where('entity_id', '=', (int) $repository->getEntity('post')['id'])
            ->count();

        if ($hasRecords === 0) {
            $repository->createRecord('post', [
                'title' => 'Groo ile Headless Icerik Yonetimi',
                'slug' => 'groo-ile-headless-icerik-yonetimi',
                'excerpt' => 'Groo Content eklentisi, icerikleri JSON bloklar halinde saklar ve REST API uzerinden sunar.',
                'banner_image' => '/assets/content/img/headless.svg',
                'status' => 'published',
                'published_at' => date('Y-m-d'),
                'content_blocks' => [
                    ['type' => 'text', 'content' => 'Groo uzerindeki icerikler dinamik entity altyapisi ile JSON olarak saklanir.'],
                    ['type' => 'image', 'url' => '/assets/content/img/headless.svg'],
                    ['type' => 'text', 'content' => 'Sablonlar ContentHelper veya records("post") helperi ile verilere erisebilir.'],
                ],
            ]);

            $repository->createRecord('post', [
                'title' => 'Eklenti Mimarisine Giris',
                'slug' => 'eklenti-mimarisine-giris',
                'excerpt' => 'Groo eklentileri register/boot yasam dongusu ve event tabanlı haberlesme ile calisir.',
                'banner_image' => '',
                'status' => 'published',
                'published_at' => date('Y-m-d'),
                'content_blocks' => [
                    ['type' => 'text', 'content' => 'Her eklenti kendi rotalarini, migration dosyalarini ve gorunumlerini tasiyabilir.'],
                ],
            ]);

            $repository->createRecord('post', [
                'title' => 'Taslak Yazi Ornegi',
                'slug' => 'taslak-yazi-ornegi',
                'excerpt' => 'Bu yazi taslak durumundadir ve API listesinde gorunmez.',
                'banner_image' => '',
                'status' => 'draft',
                'published_at' => null,
                'content_blocks' => [],
            ]);
        }
    }

    public function down(): void
    {
        $pdo = Connection::getInstance();
        QueryBuilder::table(DynamicEntityRepository::TABLE_ENTITIES, $pdo)
            ->where('slug', '=', 'post')
            ->delete();
    }
}
;