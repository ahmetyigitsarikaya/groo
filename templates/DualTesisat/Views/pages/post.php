<?php $this->extend('layouts/app'); ?>

<?php
$post = $this->data('post');
if (!$post) {
    echo 'Yazı bulunamadı.';
    return;
}
?>

<?php $this->section('title'); ?><?php echo $this->e($post['title']); ?> - DUAL Su Tesisatçısı<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<section class="bg-gray-100 py-16">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-4xl font-extrabold text-primary mb-6"><?php echo $this->e($post['title']); ?></h1>
            <div class="flex items-center justify-center space-x-4 text-gray-500 font-medium">
                <span>📅 <?php echo date('d.m.Y', strtotime($post['published_at'] ?? $post['created_at'])); ?></span>
            </div>
        </div>
    </div>
</section>

<section class="py-16">
    <div class="container mx-auto px-4">
        <article class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
            
            <?php if (!empty($post['banner_image'])): ?>
                <div class="w-full h-64 md:h-96">
                    <img src="<?php echo $this->e($post['banner_image']); ?>" alt="<?php echo $this->e($post['title']); ?>" class="w-full h-full object-cover">
                </div>
            <?php endif; ?>

            <div class="p-8 md:p-12 prose prose-lg prose-blue max-w-none">
                <?php 
                if (isset($post['content_blocks']) && is_array($post['content_blocks']) && count($post['content_blocks']) > 0) {
                    echo \Plugins\Content\Support\ContentHelper::renderBlocks($post['content_blocks']);
                } else {
                    echo $post['content'] ?? '';
                }
                ?>
            </div>
            
            <div class="p-8 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                <a href="<?php echo $this->route('/blog'); ?>" class="text-primary font-bold hover:text-secondary transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Blog'a Dön
                </a>
                
                <div class="flex gap-4">
                    <span class="text-gray-500 font-medium">Paylaş:</span>
                    <!-- Simplified sharing links -->
                    <a href="#" class="text-gray-400 hover:text-primary transition">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                    </a>
                </div>
            </div>
        </article>
    </div>
</section>
<?php $this->endSection(); ?>
