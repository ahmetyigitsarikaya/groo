<?php $this->extend('layouts/app'); ?>

<?php
$posts = $this->data('posts', []);
?>

<?php $this->section('title'); ?>Blog & Haberler - DUAL Su Tesisatçısı<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<section class="bg-gray-100 py-16">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl font-extrabold text-primary mb-4">Blog & Haberler</h1>
        <p class="text-gray-600 text-lg max-w-2xl mx-auto">Tesisat, dekorasyon ve güncel teknolojiler hakkında faydalı bilgiler, ipuçları ve haberler.</p>
    </div>
</section>

<section class="py-16">
    <div class="container mx-auto px-4">
        <?php if (empty($posts)): ?>
            <div class="text-center py-20 bg-white rounded-xl shadow-sm border border-gray-100">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5L18.5 7H20"></path></svg>
                <h3 class="text-xl font-bold text-gray-500">Henüz hiç yazı yayınlanmamış.</h3>
                <p class="text-gray-400 mt-2">Daha sonra tekrar kontrol ediniz.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($posts as $post): ?>
                    <article class="bg-white rounded-xl shadow-md overflow-hidden flex flex-col hover:shadow-xl transition duration-300 border border-gray-100">
                        <?php if (!empty($post['banner_image'])): ?>
                            <a href="<?php echo $this->route('/blog/' . $post['slug']); ?>" class="block overflow-hidden h-48">
                                <img src="<?php echo $this->e($post['banner_image']); ?>" alt="<?php echo $this->e($post['title']); ?>" class="w-full h-full object-cover hover:scale-105 transition duration-500">
                            </a>
                        <?php else: ?>
                            <div class="h-48 bg-gray-200 flex items-center justify-center">
                                <span class="text-gray-400">Resim Yok</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-6 flex flex-col flex-grow">
                            <div class="text-sm text-gray-500 mb-2 font-semibold">
                                <?php echo date('d.m.Y', strtotime($post['published_at'] ?? $post['created_at'])); ?>
                            </div>
                            <h2 class="text-xl font-bold mb-3 line-clamp-2 text-primary hover:text-secondary transition">
                                <a href="<?php echo $this->route('/blog/' . $post['slug']); ?>"><?php echo $this->e($post['title']); ?></a>
                            </h2>
                            <p class="text-gray-600 mb-4 line-clamp-3 flex-grow">
                                <?php echo $this->e($post['excerpt'] ?? strip_tags($post['content'] ?? '')); ?>
                            </p>
                            <a href="<?php echo $this->route('/blog/' . $post['slug']); ?>" class="text-secondary font-bold hover:text-orange-700 transition flex items-center gap-1 mt-auto">
                                Okumaya Devam Et
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php $this->endSection(); ?>
