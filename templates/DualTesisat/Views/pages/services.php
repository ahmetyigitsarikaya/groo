<?php $this->extend('layouts/app'); ?>

<?php $this->section('title'); ?>Hizmetlerimiz - DUAL Su Tesisatçısı<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<section class="bg-gray-100 py-16">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl font-extrabold text-primary mb-4">Profesyonel Hizmetlerimiz</h1>
        <p class="text-gray-600 text-lg max-w-2xl mx-auto">Su tesisatından ev dekorasyonuna, geniş hizmet yelpazemizle yanınızdayız.</p>
    </div>
</section>

<section class="py-24">
    <div class="container mx-auto px-4 max-w-6xl">
        
        <!-- Hizmet 1 -->
        <div class="flex flex-col lg:flex-row items-center gap-12 mb-24">
            <div class="lg:w-1/2 relative">
                <div class="absolute inset-0 bg-accent transform -translate-x-4 translate-y-4 rounded-2xl -z-10"></div>
                <img src="<?php echo $this->asset('images/dual/WhatsApp Image 2026-07-30 at 15.47.30.jpeg'); ?>" alt="Termal Kamera ile Su Kaçak Tespiti" class="rounded-2xl shadow-xl w-full h-80 object-cover border-4 border-white" loading="lazy" width="800" height="600">
            </div>
            <div class="lg:w-1/2">
                <div class="w-12 h-12 bg-slate-100 text-primary rounded-lg flex items-center justify-center mb-6 shadow-sm border border-slate-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                </div>
                <h2 class="text-3xl font-extrabold text-slate-900 mb-4 tracking-tight">Kırmadan Su Kaçak Tespiti</h2>
                <p class="text-slate-600 text-lg mb-8 leading-relaxed">
                    Evinizin veya iş yerinizin duvarlarını, fayanslarını kırmadan, son teknoloji termal kameralar ve akustik dinleme cihazları ile su kaçaklarını noktasal olarak tespit ediyoruz. Sadece sorunlu noktaya müdahale ederek onarım maliyetinizi düşürüyoruz.
                </p>
                <a href="tel:05458639660" class="inline-flex items-center gap-2 bg-primary text-white font-bold px-6 py-3 rounded-lg hover:bg-slate-800 transition shadow-sm">Detaylı Bilgi İçin Arayın &rarr;</a>
            </div>
        </div>

        <!-- Hizmet 2 -->
        <div class="flex flex-col lg:flex-row-reverse items-center gap-12 mb-24">
            <div class="lg:w-1/2 relative">
                <div class="absolute inset-0 bg-secondary transform translate-x-4 translate-y-4 rounded-2xl -z-10"></div>
                <img src="<?php echo $this->asset('images/dual/WhatsApp Image 2026-07-30 at 15.47.34.jpeg'); ?>" alt="Robot Cihazla Kanal Açma" class="rounded-2xl shadow-xl w-full h-80 object-cover border-4 border-white" loading="lazy" width="800" height="600">
            </div>
            <div class="lg:w-1/2">
                <div class="w-12 h-12 bg-red-50 text-secondary rounded-lg flex items-center justify-center mb-6 shadow-sm border border-red-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <h2 class="text-3xl font-extrabold text-slate-900 mb-4 tracking-tight">Robot Cihazla Kanal ve Pimaş Açma</h2>
                <p class="text-slate-600 text-lg mb-8 leading-relaxed">
                    Lavabo, tuvalet veya ana gider borularınızdaki tıkanıklıkları, etrafı kirletmeden robot cihazlarla açıyoruz. Kameralı sistemlerle tıkanıklığın sebebini tespit edip kalıcı çözümler üretiyoruz.
                </p>
                <a href="tel:05458639660" class="inline-flex items-center gap-2 bg-secondary text-white font-bold px-6 py-3 rounded-lg hover:bg-red-700 transition shadow-sm">Hemen Teknik Servis Çağırın &rarr;</a>
            </div>
        </div>

        <!-- Hizmet 3 -->
        <div class="flex flex-col lg:flex-row items-center gap-12">
            <div class="lg:w-1/2 relative">
                <div class="absolute inset-0 bg-accent transform -translate-x-4 translate-y-4 rounded-2xl -z-10"></div>
                <img src="<?php echo $this->asset('images/dual/WhatsApp Image 2026-07-30 at 15.47.31.jpeg'); ?>" alt="Ev Dekorasyon ve Tadilat" class="rounded-2xl shadow-xl w-full h-80 object-cover border-4 border-white" loading="lazy" width="800" height="600">
            </div>
            <div class="lg:w-1/2">
                <div class="w-12 h-12 bg-slate-100 text-primary rounded-lg flex items-center justify-center mb-6 shadow-sm border border-slate-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                </div>
                <h2 class="text-3xl font-extrabold text-slate-900 mb-4 tracking-tight">Anahtar Teslim Dekorasyon</h2>
                <p class="text-slate-600 text-lg mb-8 leading-relaxed">
                    Sadece tesisat değil, boya-badana, alçıpan, asma tavan, fayans ve komple mutfak/banyo yenileme işlerinizde uzman mimari ekibimizle hizmetinizdeyiz. Hayalinizdeki yaşam alanını birlikte tasarlayalım.
                </p>
                <a href="tel:05458639660" class="inline-flex items-center gap-2 bg-primary text-white font-bold px-6 py-3 rounded-lg hover:bg-slate-800 transition shadow-sm">Ücretsiz Keşif Talebi &rarr;</a>
            </div>
        </div>

    </div>
</section>
<?php $this->endSection(); ?>
