<!DOCTYPE html>
<?php
/**
 * FinansAI - Ana Sayfa İskeleti (Layout)
 *
 * Kullanım: Her sayfada şu pattern uygulanır:
 *   $title      = 'Sayfa Başlığı';
 *   $activePage = 'settings'; // dashboard|transactions|analysis|investments|settings
 *   ob_start();
 *   // ... sayfa içeriği ...
 *   $content = ob_get_clean();
 *   require __DIR__ . '/layout.php';
 */

$title      = $title      ?? 'FinansAI';
$activePage = $activePage ?? 'dashboard';
$content    = $content    ?? '';

// Her menü öğesi için yapılandırma
$navOgeleri = [
    'dashboard' => [
        'href'  => 'dashboard.php',
        'etiket' => 'Ana Sayfa',
        'ikon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
    ],
    'transactions' => [
        'href'  => 'transactions.php',
        'etiket' => 'Harcamalar',
        'ikon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>',
    ],
    'investments' => [
        'href'  => 'investments.php',
        'etiket' => 'Yatırımlar',
        'ikon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>',
    ],
    'settings' => [
        'href'  => 'settings.php',
        'etiket' => 'Ayarlar',
        'ikon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
    ],
];
?>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="FinansAI">
    <meta name="mobile-web-app-capable" content="yes">

    <title><?= htmlspecialchars($title) ?> | FinansAI</title>

    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon.svg">
    <link rel="icon" type="image/svg+xml" href="assets/icons/icon.svg">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Gizli scrollbar — tüm tarayıcılar */
        ::-webkit-scrollbar { display: none; }
        * { -ms-overflow-style: none; scrollbar-width: none; }

        /* PWA safe-area desteği (iPhone notch/home bar) */
        body {
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }

        /* İçerik alanı: bottom nav kadar boşluk bırak */
        .sayfa-icerigi {
            min-height: calc(100vh - 4rem);
            padding-bottom: 5.5rem;
        }

        /* Bottom nav safe-area */
        .alt-nav {
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }

        /* Aktif nav ikonu hafif parlaklık efekti */
        .nav-aktif svg {
            filter: drop-shadow(0 0 6px rgba(99, 102, 241, 0.6));
        }

        /* Sayfa geçiş animasyonu */
        .sayfa-giris {
            animation: sayfaGir 0.2s ease-out;
        }
        @keyframes sayfaGir {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Tailwind extend: indigo glow */
        .glow-indigo {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.35);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">

    <!-- Sayfa İçeriği -->
    <main class="sayfa-icerigi max-w-lg mx-auto sayfa-giris">
        <?= $content ?>
    </main>

    <!-- ─── Bottom Navigation Bar ─── -->
    <nav class="alt-nav fixed bottom-0 left-0 right-0 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 z-50">
        <div class="max-w-lg mx-auto grid grid-cols-5 items-center h-16">

            <?php
            $solOgeler  = ['dashboard', 'transactions'];
            $sagOgeler  = ['investments', 'settings'];
            $merkez     = 'analysis';

            // Sol iki öğe
            foreach ($solOgeler as $sayfa):
                $oge    = $navOgeleri[$sayfa];
                $aktif  = ($activePage === $sayfa);
            ?>
            <a href="<?= $oge['href'] ?>"
               class="flex flex-col items-center gap-0.5 py-2 transition-colors <?= $aktif ? 'text-indigo-400 nav-aktif' : 'text-slate-500 hover:text-slate-300' ?>">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <?= $oge['ikon'] ?>
                </svg>
                <span class="text-[10px] font-medium leading-none"><?= $oge['etiket'] ?></span>
            </a>
            <?php endforeach; ?>

            <!-- Merkez: AI Analiz (yükseltilmiş yuvarlak buton) -->
            <a href="ai_optimization.php"
               class="flex flex-col items-center gap-1 -mt-5 transition-all">
                <div class="w-14 h-14 rounded-full flex items-center justify-center glow-indigo transition-all
                    <?= $activePage === 'analysis' ? 'bg-indigo-500 scale-105' : 'bg-indigo-600 hover:bg-indigo-500' ?>">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-medium text-slate-500 leading-none">AI Analiz</span>
            </a>

            <!-- Sağ iki öğe -->
            <?php foreach ($sagOgeler as $sayfa):
                $oge   = $navOgeleri[$sayfa];
                $aktif = ($activePage === $sayfa);
            ?>
            <a href="<?= $oge['href'] ?>"
               class="flex flex-col items-center gap-0.5 py-2 transition-colors <?= $aktif ? 'text-indigo-400 nav-aktif' : 'text-slate-500 hover:text-slate-300' ?>">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <?= $oge['ikon'] ?>
                </svg>
                <span class="text-[10px] font-medium leading-none"><?= $oge['etiket'] ?></span>
            </a>
            <?php endforeach; ?>

        </div>
    </nav>

    <!-- PWA Service Worker kaydı -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('./sw.js').catch(() => {});
            });
        }
    </script>

</body>
</html>
