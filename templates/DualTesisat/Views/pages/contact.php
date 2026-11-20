<?php $this->extend('layouts/app'); ?>

<?php
$success = $this->data('success');
$message = $this->data('message');
?>

<?php $this->section('title'); ?>İletişim - DUAL Su Tesisatçısı<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<section class="bg-slate-50 border-b border-slate-200 py-20">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl font-extrabold text-slate-900 mb-4 tracking-tight">Bize Ulaşın</h1>
        <p class="text-slate-600 text-lg max-w-2xl mx-auto">Sorularınız, ücretsiz keşif talepleriniz veya acil servis ihtiyaçlarınız için buradayız.</p>
    </div>
</section>

<section class="py-16">
    <div class="container mx-auto px-4">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12 max-w-6xl mx-auto">
            
            <!-- Contact Info -->
            <div class="lg:w-1/3 flex flex-col gap-6">
                <div class="bg-white p-6 md:p-8 rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-accent hover:shadow-md transition">
                    <div class="w-12 h-12 bg-slate-100 text-accent rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-slate-900">Telefon</h3>
                    <p class="text-slate-600 font-medium"><a href="tel:05458639660" class="hover:text-accent">0545 863 96 60</a></p>
                    <p class="text-slate-600 font-medium"><a href="tel:05357723743" class="hover:text-accent">0535 772 37 43</a></p>
                </div>
                
                <div class="bg-white p-6 md:p-8 rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-secondary hover:shadow-md transition">
                    <div class="w-12 h-12 bg-red-50 text-secondary rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-slate-900">Adres</h3>
                    <p class="text-slate-600 leading-relaxed">Akşemsettin Mahallesi 1080. Cadde No:23c Mamak / Ankara</p>
                </div>
                
                <div class="bg-white p-6 md:p-8 rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-primary hover:shadow-md transition">
                    <div class="w-12 h-12 bg-slate-100 text-primary rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-slate-900">Web & Sosyal Medya</h3>
                    <p class="text-slate-600"><a href="https://dualtesisat.com" target="_blank" class="font-medium hover:text-accent transition">dualtesisat.com</a></p>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="lg:w-2/3 bg-white p-8 lg:p-12 rounded-xl shadow-lg border border-gray-100">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Mesaj Gönderin</h2>
                
                <?php if ($message): ?>
                    <div class="p-4 mb-6 rounded-lg <?php echo $success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <?php echo $this->e($message); ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo $this->route('/iletisim'); ?>" method="post" class="space-y-6">
                    <!-- Honeypot -->
                    <div style="display:none;">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Adınız Soyadınız</label>
                            <input type="text" id="name" name="name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary outline-none transition">
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Telefon Numaranız</label>
                            <input type="tel" id="phone" name="phone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary outline-none transition">
                        </div>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">E-posta Adresiniz</label>
                        <input type="email" id="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary outline-none transition">
                    </div>
                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Konu</label>
                        <select id="subject" name="subject" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary outline-none transition bg-white">
                            <option value="Su Kaçağı">Su Kaçağı Tespiti</option>
                            <option value="Tıkanıklık">Tıkanıklık Açma</option>
                            <option value="Tadilat">Tadilat & Dekorasyon</option>
                            <option value="Diger">Diğer</option>
                        </select>
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Mesajınız</label>
                        <textarea id="message" name="message" rows="5" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary outline-none transition"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-primary text-white font-bold py-4 rounded-lg hover:bg-slate-800 transition shadow-sm text-lg">
                        Mesajı Gönder
                    </button>
                </form>
            </div>
            
        </div>
    </div>
</section>
<?php $this->endSection(); ?>
