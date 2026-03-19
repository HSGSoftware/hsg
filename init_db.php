<?php
/**
 * FinansAI - Veritabanı Başlatma / Kurulum Sayfası
 * İlk kurulumda bu sayfayı ziyaret ederek SQLite şemasını oluşturun.
 */

$durum  = 'bekliyor'; // bekliyor | basarili | hata
$mesaj  = '';
$detay  = [];

try {
    require_once __DIR__ . '/pwa_db.php';

    // getDB() çağrısı tabloları otomatik oluşturur
    $pdo = getDB();

    // Tabloların gerçekten oluştuğunu doğrula
    $tablolar = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll();
    $tabloAdlari = array_column($tablolar, 'name');

    $beklenenTablolar = ['settings', 'transactions'];
    foreach ($beklenenTablolar as $tablo) {
        $detay[] = [
            'ad'     => $tablo,
            'durum'  => in_array($tablo, $tabloAdlari),
        ];
    }

    $durum = 'basarili';
    $mesaj = 'Veritabanı başarıyla kuruldu. Uygulamayı kullanmaya başlayabilirsiniz.';

    // Yeni kurulumsa varsayılan model ayarlarını ekle
    if (getSetting('parser_model') === '') {
        setSetting('parser_model', 'openai/gpt-4o-mini');
        setSetting('optimizer_model', 'openai/gpt-4o-mini');
    }

} catch (Exception $e) {
    $durum = 'hata';
    $mesaj = 'Veritabanı oluşturulurken hata oluştu: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6366f1">
    <title>Kurulum | FinansAI</title>
    <link rel="manifest" href="manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        ::-webkit-scrollbar { display: none; }
        * { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md" x-data="{ gosterDetay: false }">

        <!-- Logo / Başlık -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-indigo-900/50">
                <span class="text-4xl font-bold text-white">₺</span>
            </div>
            <h1 class="text-2xl font-bold text-white">FinansAI</h1>
            <p class="text-slate-400 text-sm mt-1">Akıllı Bütçe Asistanı</p>
        </div>

        <!-- Durum Kartı -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden">

            <!-- Başlık Bandı -->
            <div class="px-6 py-4 border-b border-slate-800">
                <h2 class="text-lg font-semibold text-white">Veritabanı Kurulumu</h2>
                <p class="text-slate-400 text-sm mt-0.5">SQLite şeması oluşturuluyor</p>
            </div>

            <!-- İçerik -->
            <div class="p-6 space-y-4">

                <!-- Durum Mesajı -->
                <?php if ($durum === 'basarili'): ?>
                <div class="flex items-start gap-3 bg-emerald-950/50 border border-emerald-800/50 rounded-xl p-4">
                    <div class="w-8 h-8 bg-emerald-600 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-emerald-300 font-medium text-sm">Kurulum Tamamlandı</p>
                        <p class="text-emerald-400/70 text-xs mt-0.5"><?= htmlspecialchars($mesaj) ?></p>
                    </div>
                </div>

                <?php elseif ($durum === 'hata'): ?>
                <div class="flex items-start gap-3 bg-red-950/50 border border-red-800/50 rounded-xl p-4">
                    <div class="w-8 h-8 bg-red-600 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-red-300 font-medium text-sm">Hata Oluştu</p>
                        <p class="text-red-400/70 text-xs mt-0.5"><?= htmlspecialchars($mesaj) ?></p>
                        <p class="text-slate-500 text-xs mt-2">
                            Çözüm: PHP'nin <code class="bg-slate-800 px-1 rounded">database.sqlite</code> dosyasını yazma iznine sahip olduğundan emin olun.
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tablo Durumu -->
                <?php if (!empty($detay)): ?>
                <div>
                    <button
                        @click="gosterDetay = !gosterDetay"
                        class="flex items-center justify-between w-full text-left text-slate-400 text-sm hover:text-slate-200 transition-colors"
                    >
                        <span>Tablo Detayları</span>
                        <svg class="w-4 h-4 transition-transform" :class="gosterDetay ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="gosterDetay" x-transition class="mt-3 space-y-2">
                        <?php foreach ($detay as $tablo): ?>
                        <div class="flex items-center justify-between bg-slate-800/50 rounded-lg px-3 py-2">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M8 6h.01M8 18h.01"/>
                                </svg>
                                <code class="text-slate-300 text-xs"><?= htmlspecialchars($tablo['ad']) ?></code>
                            </div>
                            <?php if ($tablo['durum']): ?>
                            <span class="text-xs text-emerald-400 font-medium">✓ Hazır</span>
                            <?php else: ?>
                            <span class="text-xs text-red-400 font-medium">✗ Eksik</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Veritabanı Yolu -->
                <div class="bg-slate-800/50 rounded-xl px-4 py-3">
                    <p class="text-slate-500 text-xs">Veritabanı Konumu</p>
                    <code class="text-slate-300 text-xs break-all">database.sqlite</code>
                </div>

            </div>

            <!-- Butonlar -->
            <?php if ($durum === 'basarili'): ?>
            <div class="px-6 pb-6 flex flex-col gap-3">
                <a
                    href="settings.php"
                    class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-3 px-4 rounded-xl text-center transition-colors text-sm"
                >
                    AI Ayarlarını Yapılandır →
                </a>
                <a
                    href="dashboard.php"
                    class="w-full bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium py-3 px-4 rounded-xl text-center transition-colors text-sm"
                >
                    Ana Sayfaya Git
                </a>
            </div>
            <?php else: ?>
            <div class="px-6 pb-6">
                <a
                    href="init_db.php"
                    class="w-full block bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-3 px-4 rounded-xl text-center transition-colors text-sm"
                >
                    Tekrar Dene
                </a>
            </div>
            <?php endif; ?>

        </div>

        <!-- Sürüm Bilgisi -->
        <p class="text-center text-slate-600 text-xs mt-6">FinansAI v1.0 · Core PHP 8+ · SQLite</p>

    </div>

    <script>
        // PWA Service Worker kaydı
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('./sw.js').catch(() => {});
        }
    </script>
</body>
</html>
