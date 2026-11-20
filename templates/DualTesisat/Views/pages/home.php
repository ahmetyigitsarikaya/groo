<?php $this->extend('layouts/app'); ?>

<?php $this->section('title'); ?>DUAL Su Tesisatçısı - Ankara Kırmadan Kaçak Tespiti & Dekorasyon<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<!-- Hero Section -->
<section class="relative bg-primary text-white py-16 lg:py-32 border-b-4 border-secondary overflow-hidden">
    <div class="absolute inset-0 bg-[url('<?php echo $this->asset('images/dual/screencapture-sanrotech-plumbing-de-2026-08-11-22_27_06.png'); ?>')] bg-cover bg-center opacity-10"></div>
    <div class="container mx-auto px-4 relative z-10 text-center lg:text-left flex flex-col lg:flex-row items-center gap-12">
        <div class="lg:w-3/5 w-full">
            <span class="inline-block py-1 px-3 rounded bg-accent/20 text-blue-200 font-semibold tracking-wider text-xs md:text-sm mb-4 uppercase">Profesyonel Tesisat Çözümleri</span>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold mb-4 lg:mb-6 leading-tight tracking-tight text-white">Teknolojik Çözümlerle<br>Yaşam Alanlarınızda <span class="text-secondary">Kalite ve Güven</span></h1>
            <p class="text-base md:text-lg lg:text-xl text-slate-300 mb-6 lg:mb-8 max-w-2xl mx-auto lg:mx-0 font-medium leading-relaxed">Ankara genelinde kırmadan, dökmeden su kaçak tespiti ve anahtar teslim profesyonel dekorasyon hizmetleri.</p>
            
            <!-- Trust Badges -->
            <div class="flex flex-wrap justify-center lg:justify-start gap-4 mb-8">
                <div class="flex items-center gap-2 bg-slate-800/50 px-3 py-1.5 rounded-md border border-slate-700/50">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="text-sm font-semibold text-slate-200">Kırmadan Tespit</span>
                </div>
                <div class="flex items-center gap-2 bg-slate-800/50 px-3 py-1.5 rounded-md border border-slate-700/50">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="text-sm font-semibold text-slate-200">Garantili İşçilik</span>
                </div>
                <div class="flex items-center gap-2 bg-slate-800/50 px-3 py-1.5 rounded-md border border-slate-700/50">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="text-sm font-semibold text-slate-200">7/24 Acil Servis</span>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-center lg:justify-start gap-3 w-full">
                <a href="<?php echo $this->route('/hizmetler'); ?>" class="bg-white text-primary px-8 py-3.5 md:py-4 rounded-lg font-bold text-base md:text-lg hover:bg-slate-100 transition shadow text-center w-full sm:w-auto">Tüm Hizmetlerimiz</a>
                <a href="tel:05458639660" class="bg-secondary text-white px-8 py-3.5 md:py-4 rounded-lg font-bold text-base md:text-lg hover:bg-red-700 transition shadow text-center flex items-center justify-center gap-2 w-full sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 md:h-6 md:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Acil Destek Hattı
                </a>
            </div>
        </div>
        <div class="hidden lg:block lg:w-2/5">
            <div class="bg-white p-3 rounded-xl shadow-2xl relative">
                <div class="absolute -top-4 -right-4 w-24 h-24 bg-accent rounded-full -z-10 opacity-50"></div>
                <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-secondary rounded-lg -z-10 opacity-20"></div>
                <img src="<?php echo $this->asset('images/dual/WhatsApp Image 2026-07-30 at 15.47.30_c11a38.webp'); ?>" alt="Tesisat Çalışması" class="rounded-lg w-full object-cover h-[350px]" fetchpriority="high" width="600" height="350">
            </div>
        </div>
    </div>
</section>

<!-- Services Grid -->
<section class="py-24 bg-slate-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-3xl lg:text-4xl font-extrabold text-slate-900 mb-4 tracking-tight">Profesyonel Hizmetlerimiz</h2>
            <div class="w-24 h-1.5 bg-secondary mx-auto rounded-full"></div>
            <p class="text-slate-600 mt-6 max-w-2xl mx-auto text-lg">Yılların getirdiği tecrübe ile tesisat ve dekorasyon ihtiyaçlarınıza kalıcı çözümler sunuyoruz.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            
            <!-- Service 1 -->
            <div class="bg-white rounded-xl shadow-sm hover:shadow-xl p-8 transition duration-300 border border-slate-100 relative overflow-hidden group">
                <div class="absolute top-0 left-0 w-full h-1 bg-primary transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                <div class="w-14 h-14 bg-slate-100 text-primary rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                </div>
                <h3 class="text-xl font-bold mb-3 text-slate-900">Su Kaçak Tespiti</h3>
                <p class="text-slate-600 leading-relaxed">Termal kamera ve akustik dinleme cihazları ile kırmadan noktasal tespit hizmeti.</p>
            </div>

            <!-- Service 2 -->
            <div class="bg-white rounded-xl shadow-sm hover:shadow-xl p-8 transition duration-300 border border-slate-100 relative overflow-hidden group">
                <div class="absolute top-0 left-0 w-full h-1 bg-secondary transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                <div class="w-14 h-14 bg-red-50 text-secondary rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <h3 class="text-xl font-bold mb-3 text-slate-900">Sıhhi Tesisat Tamiratı</h3>
                <p class="text-slate-600 leading-relaxed">Boru, batarya, rezervuar gibi her türlü arızalarda garantili ve kalıcı tamirat.</p>
            </div>

            <!-- Service 3 -->
            <div class="bg-white rounded-xl shadow-sm hover:shadow-xl p-8 transition duration-300 border border-slate-100 relative overflow-hidden group">
                <div class="absolute top-0 left-0 w-full h-1 bg-primary transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                <div class="w-14 h-14 bg-slate-100 text-primary rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <h3 class="text-xl font-bold mb-3 text-slate-900">Kanal & Tıkanıklık Açma</h3>
                <p class="text-slate-600 leading-relaxed">Tıkalı lavabo, tuvalet ve pimaş hatlarının robotik cihazlarla kırmadan açılması.</p>
            </div>

            <!-- Service 4 -->
            <div class="bg-white rounded-xl shadow-sm hover:shadow-xl p-8 transition duration-300 border border-slate-100 relative overflow-hidden group">
                <div class="absolute top-0 left-0 w-full h-1 bg-secondary transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                <div class="w-14 h-14 bg-red-50 text-secondary rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                </div>
                <h3 class="text-xl font-bold mb-3 text-slate-900">Dekorasyon & Tadilat</h3>
                <p class="text-slate-600 leading-relaxed">Boya, fayans, alçıpan ve anahtar teslim her bütçeye uygun yenileme projeleri.</p>
            </div>

        </div>
    </div>
</section>

<!-- Process Section -->
<section class="py-24 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-20">
            <h2 class="text-3xl lg:text-4xl font-extrabold text-slate-900 mb-4 tracking-tight">Nasıl Çalışıyoruz?</h2>
            <div class="w-24 h-1.5 bg-primary mx-auto rounded-full"></div>
        </div>
        
        <div class="flex flex-col md:flex-row justify-between items-start relative max-w-5xl mx-auto">
            <div class="hidden md:block absolute top-8 left-0 w-full h-0.5 bg-slate-200 -z-10"></div>
            
            <div class="flex flex-col items-center mb-12 md:mb-0 bg-white px-6">
                <div class="w-16 h-16 bg-slate-100 text-slate-700 rounded-2xl flex items-center justify-center text-xl font-bold mb-6 shadow-sm border border-slate-200">1</div>
                <h4 class="font-bold text-lg text-slate-800 text-center">Bize Ulaşın</h4>
                <p class="text-slate-500 text-sm text-center mt-2 max-w-[150px]">Sorununuzu iletin</p>
            </div>
            
            <div class="flex flex-col items-center mb-12 md:mb-0 bg-white px-6">
                <div class="w-16 h-16 bg-slate-100 text-slate-700 rounded-2xl flex items-center justify-center text-xl font-bold mb-6 shadow-sm border border-slate-200">2</div>
                <h4 class="font-bold text-lg text-slate-800 text-center">Ücretsiz Keşif</h4>
                <p class="text-slate-500 text-sm text-center mt-2 max-w-[150px]">Adresinize gelelim</p>
            </div>
            
            <div class="flex flex-col items-center mb-12 md:mb-0 bg-white px-6">
                <div class="w-16 h-16 bg-accent text-white rounded-2xl flex items-center justify-center text-xl font-bold mb-6 shadow-lg border border-accent transform scale-110">3</div>
                <h4 class="font-bold text-lg text-accent text-center">Teknolojik Tespit</h4>
                <p class="text-slate-500 text-sm text-center mt-2 max-w-[150px]">Kırmadan çözelim</p>
            </div>
            
            <div class="flex flex-col items-center mb-12 md:mb-0 bg-white px-6">
                <div class="w-16 h-16 bg-slate-100 text-slate-700 rounded-2xl flex items-center justify-center text-xl font-bold mb-6 shadow-sm border border-slate-200">4</div>
                <h4 class="font-bold text-lg text-slate-800 text-center">Hızlı Onarım</h4>
                <p class="text-slate-500 text-sm text-center mt-2 max-w-[150px]">Garantili tamirat</p>
            </div>
            
            <div class="flex flex-col items-center bg-white px-6">
                <div class="w-16 h-16 bg-slate-100 text-slate-700 rounded-2xl flex items-center justify-center text-xl font-bold mb-6 shadow-sm border border-slate-200">5</div>
                <h4 class="font-bold text-lg text-slate-800 text-center">Memnuniyet</h4>
                <p class="text-slate-500 text-sm text-center mt-2 max-w-[150px]">İş teslimi</p>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-24 bg-primary text-white border-t border-slate-800">
    <div class="container mx-auto px-4 flex flex-col lg:flex-row items-center gap-16">
        <div class="lg:w-1/2">
            <h2 class="text-3xl lg:text-4xl font-extrabold mb-6 tracking-tight">Neden Bizi Seçmelisiniz?</h2>
            <div class="w-24 h-1.5 bg-secondary mb-10 rounded-full"></div>
            <ul class="space-y-8">
                <li class="flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center mt-1 mr-5">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold mb-2 text-slate-100">Dürüst ve Şeffaf Çalışma</h4>
                        <p class="text-slate-400 leading-relaxed">Yapılan her işlemi size detaylıca anlatır, iş bitiminde sürpriz masraflar çıkarmayız.</p>
                    </div>
                </li>
                <li class="flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center mt-1 mr-5">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold mb-2 text-slate-100">Uzman ve Deneyimli Ekip</h4>
                        <p class="text-slate-400 leading-relaxed">Yılların getirdiği tecrübe ile en zorlu arızaları bile kolayca çözeriz.</p>
                    </div>
                </li>
                <li class="flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center mt-1 mr-5">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold mb-2 text-slate-100">Teknolojik Cihazlar</h4>
                        <p class="text-slate-400 leading-relaxed">Termal kamera ve akustik dinleme ile hasarı minimuma indiririz.</p>
                    </div>
                </li>
            </ul>
        </div>
        <div class="lg:w-1/2">
             <div class="relative">
                 <div class="absolute inset-0 bg-accent transform translate-x-4 translate-y-4 rounded-2xl -z-10"></div>
                 <img src="<?php echo $this->asset('images/dual/WhatsApp Image 2026-07-30 at 15.47.33.jpeg'); ?>" alt="Ekibimiz İş Başında" class="rounded-2xl shadow-xl w-full object-cover" loading="lazy" width="800" height="600">
             </div>
        </div>
    </div>
</section>
<?php $this->endSection(); ?>
