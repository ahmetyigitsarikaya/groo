<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->yieldSection('title', 'DUAL Su Tesisatçısı - Ankara Kırmadan Kaçak Tespiti & Dekorasyon'); ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo $this->yieldSection('meta_description', 'Ankara genelinde kırmadan su kaçak tespiti, tıkanıklık açma ve anahtar teslim dekorasyon hizmetleri. Garantili işçilik, 7/24 acil servis. Hemen arayın!'); ?>">
    <meta name="keywords" content="ankara su tesisatçısı, su kaçak tespiti, kırmadan kaçak bulma, tıkanıklık açma, tesisat tamiri, ankara dekorasyon, anahtar teslim tadilat">
    <meta name="author" content="DUAL Tesisat">
    <link rel="canonical" href="https://dualtesisat.com<?php echo parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>">

    <!-- Open Graph (Sosyal Medya) -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://dualtesisat.com<?php echo parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>">
    <meta property="og:title" content="<?php echo $this->yieldSection('title', 'DUAL Su Tesisatçısı'); ?>">
    <meta property="og:description" content="<?php echo $this->yieldSection('meta_description', 'Ankara kırmadan su kaçak tespiti ve anahtar teslim dekorasyon hizmetleri.'); ?>">
    <meta property="og:image" content="https://dualtesisat.com/public/assets/images/dual/WhatsApp%20Image%202026-07-30%20at%2015.47.30_c11a38.webp">
    
    <!-- Preconnect & DNS Prefetch (Performans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.tailwindcss.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#0f172a', // Slate 900 for a solid corporate feel
                        secondary: '#dc2626', // Red 600 for strong CTA
                        accent: '#2563eb', // Blue 600 for links and highlights
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans flex flex-col min-h-screen antialiased">

    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-slate-200 sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <!-- Logo -->
            <a href="<?php echo $this->route('/'); ?>" class="flex items-center space-x-2">
                <span class="text-2xl font-black text-primary tracking-tight uppercase">DUAL<span class="text-accent">Tesisat</span></span>
            </a>

            <!-- Navigation -->
            <nav class="hidden lg:flex space-x-8 items-center">
                <a href="<?php echo $this->route('/'); ?>" class="text-slate-600 hover:text-accent font-semibold transition">Ana Sayfa</a>
                <a href="<?php echo $this->route('/hizmetler'); ?>" class="text-slate-600 hover:text-accent font-semibold transition">Hizmetler</a>
                <a href="<?php echo $this->route('/blog'); ?>" class="text-slate-600 hover:text-accent font-semibold transition">Blog</a>
                <a href="<?php echo $this->route('/iletisim'); ?>" class="text-slate-600 hover:text-accent font-semibold transition">İletişim</a>
            </nav>

            <!-- CTA Buttons -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3">
                <a href="tel:05458639660" class="bg-secondary text-white px-5 py-2.5 rounded-lg font-bold text-sm hover:bg-red-700 transition shadow-sm flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                    </svg>
                    0545 863 96 60
                </a>
                <a href="tel:05357723743" class="bg-primary text-white px-5 py-2.5 rounded-lg font-bold text-sm hover:bg-slate-800 transition shadow-sm flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                    </svg>
                    0535 772 37 43
                </a>
            </div>

            <!-- Mobile Menu Button -->
            <button id="mobile-menu-button" type="button" class="lg:hidden text-slate-600 focus:outline-none" aria-label="Menüyü Aç/Kapat">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden lg:hidden bg-white border-t border-slate-100 shadow-md absolute w-full left-0">
            <nav class="container mx-auto px-4 py-4 flex flex-col">
                <a href="<?php echo $this->route('/'); ?>" class="block py-3 text-slate-700 hover:text-accent font-semibold border-b border-slate-50 transition">Ana Sayfa</a>
                <a href="<?php echo $this->route('/hizmetler'); ?>" class="block py-3 text-slate-700 hover:text-accent font-semibold border-b border-slate-50 transition">Hizmetler</a>
                <a href="<?php echo $this->route('/blog'); ?>" class="block py-3 text-slate-700 hover:text-accent font-semibold border-b border-slate-50 transition">Blog</a>
                <a href="<?php echo $this->route('/iletisim'); ?>" class="block py-3 text-slate-700 hover:text-accent font-semibold transition">İletişim</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow">
        <?php echo $this->yieldSection('content'); ?>
    </main>

    <!-- Footer -->
    <footer class="bg-primary text-white py-10 mt-auto">
        <div class="container mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-xl font-bold mb-4">DUAL Tesisat & Dekorasyon</h3>
                <p class="text-gray-300">Teknolojik çözümlerle yaşam alanlarınızda kalite ve güven. Kırmadan dökmeden hizmet.</p>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4">İletişim Bilgileri</h3>
                <ul class="text-gray-300 space-y-2">
                    <li>📍 <strong>Adres:</strong> Akşemsettin Mahallesi 1080. Cadde No:23c Mamak</li>
                    <li>📞 <strong>Telefon 1:</strong> 0545 863 96 60</li>
                    <li>📞 <strong>Telefon 2:</strong> 0535 772 37 43</li>
                    <li>🌐 <strong>Web:</strong> <a href="https://dualtesisat.com" target="_blank" class="text-secondary hover:underline">dualtesisat.com</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4">Hızlı Menü</h3>
                <ul class="text-gray-300 space-y-2">
                    <li><a href="<?php echo $this->route('/'); ?>" class="hover:text-secondary transition">Ana Sayfa</a></li>
                    <li><a href="<?php echo $this->route('/hizmetler'); ?>" class="hover:text-secondary transition">Hizmetlerimiz</a></li>
                    <li><a href="<?php echo $this->route('/blog'); ?>" class="hover:text-secondary transition">Blog</a></li>
                    <li><a href="<?php echo $this->route('/iletisim'); ?>" class="hover:text-secondary transition">İletişim</a></li>
                </ul>
            </div>
        </div>
        <div class="container mx-auto px-4 mt-8 pt-6 border-t border-slate-800 text-center text-slate-400 text-sm pb-24 md:pb-8">
            &copy; <?php echo date('Y'); ?> DUAL Tesisat. Tüm Hakları Saklıdır.
        </div>
    </footer>

    <!-- Floating WhatsApp Button -->
    <a href="https://wa.me/905458639660" target="_blank" class="fixed bottom-24 md:bottom-8 right-4 md:right-8 bg-green-500 text-white p-4 rounded-full shadow-2xl hover:bg-green-600 hover:-translate-y-1 transition-all z-40 flex items-center justify-center animate-bounce group" aria-label="WhatsApp">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
            <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
        </svg>
    </a>

    <!-- Mobile Bottom CTA Bar -->
    <div class="md:hidden fixed bottom-0 left-0 w-full bg-white border-t border-slate-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] z-50 flex">
        <a href="tel:05458639660" class="flex-1 flex flex-col items-center justify-center py-3 text-secondary font-bold text-sm bg-white hover:bg-slate-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
            Hemen Ara
        </a>
        <div class="w-[1px] bg-slate-200 my-2"></div>
        <a href="https://wa.me/905458639660" target="_blank" class="flex-1 flex flex-col items-center justify-center py-3 text-green-600 font-bold text-sm bg-white hover:bg-slate-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-1" fill="currentColor" viewBox="0 0 16 16">
                <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
            </svg>
            WhatsApp
        </a>
    </div>

    <script>
        document.getElementById('mobile-menu-button').addEventListener('click', function () {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
    <!-- Structured Data / Schema.org -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Plumber",
      "name": "DUAL Tesisat & Dekorasyon",
      "image": "https://dualtesisat.com/public/assets/images/dual/WhatsApp%20Image%202026-07-30%20at%2015.47.30_c11a38.webp",
      "@id": "https://dualtesisat.com",
      "url": "https://dualtesisat.com",
      "telephone": "+905458639660",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Akşemsettin Mahallesi 1080. Cadde No:23c Mamak",
        "addressLocality": "Ankara",
        "addressCountry": "TR"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": 39.92077,
        "longitude": 32.85411
      },
      "openingHoursSpecification": {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": [
          "Monday",
          "Tuesday",
          "Wednesday",
          "Thursday",
          "Friday",
          "Saturday",
          "Sunday"
        ],
        "opens": "00:00",
        "closes": "23:59"
      }
    }
    </script>
</body>
</html>
