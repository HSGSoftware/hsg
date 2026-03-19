<?php
/**
 * FinansAI - Gelişmiş AI Ayarları
 * API anahtarları ve model seçimleri yönetimi
 */

require_once __DIR__ . '/pwa_db.php';

// Veritabanı hazır değilse kuruluma yönlendir
try {
    getDB();
} catch (Exception $e) {
    header('Location: init_db.php');
    exit;
}

$bildirim = ['tur' => '', 'mesaj' => ''];

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_kaydet'])) {
    $kaydedilecek = [
        'openai_key'      => trim($_POST['openai_key']      ?? ''),
        'anthropic_key'   => trim($_POST['anthropic_key']   ?? ''),
        'groq_key'        => trim($_POST['groq_key']        ?? ''),
        'parser_model'    => trim($_POST['parser_model']    ?? ''),
        'optimizer_model' => trim($_POST['optimizer_model'] ?? ''),
    ];

    // Geçerli model değerlerini doğrula
    $gecerliModeller = [
        'openai/gpt-4o',
        'openai/gpt-4o-mini',
        'anthropic/claude-3-5-sonnet-latest',
        'anthropic/claude-3-haiku-20240307',
        'groq/llama3-8b-8192',
        'groq/llama3-70b-8192',
        'groq/mixtral-8x7b-32768',
    ];

    if (!in_array($kaydedilecek['parser_model'], $gecerliModeller, true)) {
        $kaydedilecek['parser_model'] = 'openai/gpt-4o-mini';
    }
    if (!in_array($kaydedilecek['optimizer_model'], $gecerliModeller, true)) {
        $kaydedilecek['optimizer_model'] = 'openai/gpt-4o-mini';
    }

    try {
        setSettingToplu($kaydedilecek);
        $bildirim = ['tur' => 'basarili', 'mesaj' => 'Ayarlar kaydedildi.'];
    } catch (Exception $e) {
        $bildirim = ['tur' => 'hata', 'mesaj' => 'Kaydetme hatası: ' . $e->getMessage()];
    }
}

// Mevcut ayarları yükle
$mevcut = [
    'openai_key'      => getSetting('openai_key'),
    'anthropic_key'   => getSetting('anthropic_key'),
    'groq_key'        => getSetting('groq_key'),
    'parser_model'    => getSetting('parser_model',    'openai/gpt-4o-mini'),
    'optimizer_model' => getSetting('optimizer_model', 'openai/gpt-4o-mini'),
];

// Kullanılabilir AI modelleri (provider/model => Görüntü Adı)
$modelSecenekleri = [
    'openai/gpt-4o'                      => 'OpenAI — gpt-4o',
    'openai/gpt-4o-mini'                 => 'OpenAI — gpt-4o-mini',
    'anthropic/claude-3-5-sonnet-latest' => 'Anthropic — claude-3-5-sonnet-latest',
    'anthropic/claude-3-haiku-20240307'  => 'Anthropic — claude-3-haiku-20240307',
    'groq/llama3-8b-8192'                => 'Groq — llama3-8b-8192',
    'groq/llama3-70b-8192'               => 'Groq — llama3-70b-8192',
    'groq/mixtral-8x7b-32768'            => 'Groq — mixtral-8x7b-32768',
];

// Provider renkleri (badge için)
$providerRenkleri = [
    'openai'    => ['bg' => 'bg-emerald-950/60', 'border' => 'border-emerald-800/50', 'text' => 'text-emerald-400', 'dot' => 'bg-emerald-500'],
    'anthropic' => ['bg' => 'bg-orange-950/60',  'border' => 'border-orange-800/50',  'text' => 'text-orange-400',  'dot' => 'bg-orange-500'],
    'groq'      => ['bg' => 'bg-purple-950/60',  'border' => 'border-purple-800/50',  'text' => 'text-purple-400',  'dot' => 'bg-purple-500'],
];

$title      = 'AI Ayarları';
$activePage = 'settings';
ob_start();
?>

<!-- ─── Sayfa Başlığı ─── -->
<div class="px-4 pt-6 pb-2">
    <h1 class="text-xl font-bold text-white">AI Ayarları</h1>
    <p class="text-slate-400 text-sm mt-0.5">API anahtarları ve model seçimleri</p>
</div>

<!-- ─── Bildirim ─── -->
<?php if ($bildirim['mesaj']): ?>
<div x-data="{ goster: true }" x-show="goster" x-init="setTimeout(() => goster = false, 4000)"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-2"
     class="mx-4 mb-2 flex items-center gap-3 rounded-xl px-4 py-3 border text-sm
        <?= $bildirim['tur'] === 'basarili'
            ? 'bg-emerald-950/50 border-emerald-800/50 text-emerald-300'
            : 'bg-red-950/50 border-red-800/50 text-red-300' ?>">
    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <?php if ($bildirim['tur'] === 'basarili'): ?>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        <?php else: ?>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        <?php endif; ?>
    </svg>
    <?= htmlspecialchars($bildirim['mesaj']) ?>
</div>
<?php endif; ?>

<form method="POST" action="settings.php" class="px-4 pb-6 space-y-4">
    <input type="hidden" name="_kaydet" value="1">

    <!-- ─── API Anahtarları ─── -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden">

        <div class="px-5 py-4 border-b border-slate-800 flex items-center gap-2.5">
            <div class="w-8 h-8 bg-slate-800 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-white">API Anahtarları</h2>
                <p class="text-xs text-slate-500">Kullanmak istediğiniz sağlayıcıların anahtarını girin</p>
            </div>
        </div>

        <div class="p-5 space-y-4">

            <!-- OpenAI -->
            <div x-data="{ goster: false }">
                <label class="flex items-center gap-2 text-xs font-medium text-slate-400 mb-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                    OpenAI API Anahtarı
                </label>
                <div class="relative">
                    <input
                        :type="goster ? 'text' : 'password'"
                        name="openai_key"
                        value="<?= htmlspecialchars($mevcut['openai_key']) ?>"
                        placeholder="sk-..."
                        autocomplete="off"
                        spellcheck="false"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 pr-10 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 transition-colors font-mono"
                    >
                    <button type="button" @click="goster = !goster"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors">
                        <svg x-show="!goster" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="goster" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Anthropic -->
            <div x-data="{ goster: false }">
                <label class="flex items-center gap-2 text-xs font-medium text-slate-400 mb-1.5">
                    <span class="w-2 h-2 rounded-full bg-orange-500 inline-block"></span>
                    Anthropic API Anahtarı
                </label>
                <div class="relative">
                    <input
                        :type="goster ? 'text' : 'password'"
                        name="anthropic_key"
                        value="<?= htmlspecialchars($mevcut['anthropic_key']) ?>"
                        placeholder="sk-ant-..."
                        autocomplete="off"
                        spellcheck="false"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 pr-10 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 transition-colors font-mono"
                    >
                    <button type="button" @click="goster = !goster"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors">
                        <svg x-show="!goster" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="goster" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Groq -->
            <div x-data="{ goster: false }">
                <label class="flex items-center gap-2 text-xs font-medium text-slate-400 mb-1.5">
                    <span class="w-2 h-2 rounded-full bg-purple-500 inline-block"></span>
                    Groq API Anahtarı
                </label>
                <div class="relative">
                    <input
                        :type="goster ? 'text' : 'password'"
                        name="groq_key"
                        value="<?= htmlspecialchars($mevcut['groq_key']) ?>"
                        placeholder="gsk_..."
                        autocomplete="off"
                        spellcheck="false"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 pr-10 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 transition-colors font-mono"
                    >
                    <button type="button" @click="goster = !goster"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors">
                        <svg x-show="!goster" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="goster" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- ─── Model Seçimleri ─── -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden">

        <div class="px-5 py-4 border-b border-slate-800 flex items-center gap-2.5">
            <div class="w-8 h-8 bg-slate-800 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-white">Model Seçimi</h2>
                <p class="text-xs text-slate-500">Her görev için farklı model atayın</p>
            </div>
        </div>

        <div class="p-5 space-y-5">

            <!-- PDF Ayıklama Modeli -->
            <div x-data="{ secilenModel: '<?= htmlspecialchars($mevcut['parser_model']) ?>' }">
                <label class="block text-xs font-medium text-slate-400 mb-1.5">
                    PDF Ayıklama (Parser) Modeli
                </label>
                <p class="text-xs text-slate-600 mb-3">
                    Banka ekstresindeki işlemleri JSON'a dönüştürür
                </p>

                <!-- Seçili model badge -->
                <?php
                $parserProvider = explode('/', $mevcut['parser_model'])[0] ?? 'openai';
                $parserRenk = $providerRenkleri[$parserProvider] ?? $providerRenkleri['openai'];
                ?>
                <div class="flex items-center gap-2 mb-3 px-3 py-2 rounded-lg border <?= $parserRenk['bg'] ?> <?= $parserRenk['border'] ?>">
                    <span class="w-2 h-2 rounded-full <?= $parserRenk['dot'] ?>"></span>
                    <span class="text-xs <?= $parserRenk['text'] ?> font-medium"
                          x-text="secilenModel">
                        <?= htmlspecialchars($mevcut['parser_model']) ?>
                    </span>
                </div>

                <div class="relative">
                    <select
                        name="parser_model"
                        x-model="secilenModel"
                        class="w-full appearance-none bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 pr-10 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 transition-colors cursor-pointer"
                    >
                        <?php foreach ($modelSecenekleri as $deger => $goruntu): ?>
                        <option value="<?= htmlspecialchars($deger) ?>"
                            <?= $mevcut['parser_model'] === $deger ? 'selected' : '' ?>>
                            <?= htmlspecialchars($goruntu) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Ayırıcı -->
            <div class="border-t border-slate-800"></div>

            <!-- Bütçe Analizi Modeli -->
            <div x-data="{ secilenModel: '<?= htmlspecialchars($mevcut['optimizer_model']) ?>' }">
                <label class="block text-xs font-medium text-slate-400 mb-1.5">
                    Bütçe Analizi (Optimizer) Modeli
                </label>
                <p class="text-xs text-slate-600 mb-3">
                    Harcama verilerini analiz eder ve tasarruf tavsiyeleri üretir
                </p>

                <!-- Seçili model badge -->
                <?php
                $optimizerProvider = explode('/', $mevcut['optimizer_model'])[0] ?? 'openai';
                $optimizerRenk = $providerRenkleri[$optimizerProvider] ?? $providerRenkleri['openai'];
                ?>
                <div class="flex items-center gap-2 mb-3 px-3 py-2 rounded-lg border <?= $optimizerRenk['bg'] ?> <?= $optimizerRenk['border'] ?>">
                    <span class="w-2 h-2 rounded-full <?= $optimizerRenk['dot'] ?>"></span>
                    <span class="text-xs <?= $optimizerRenk['text'] ?> font-medium"
                          x-text="secilenModel">
                        <?= htmlspecialchars($mevcut['optimizer_model']) ?>
                    </span>
                </div>

                <div class="relative">
                    <select
                        name="optimizer_model"
                        x-model="secilenModel"
                        class="w-full appearance-none bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 pr-10 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 transition-colors cursor-pointer"
                    >
                        <?php foreach ($modelSecenekleri as $deger => $goruntu): ?>
                        <option value="<?= htmlspecialchars($deger) ?>"
                            <?= $mevcut['optimizer_model'] === $deger ? 'selected' : '' ?>>
                            <?= htmlspecialchars($goruntu) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ─── Hızlı Rehber ─── -->
    <div class="bg-slate-900/50 rounded-2xl border border-slate-800/50 p-5 space-y-3">
        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Model Seçim Rehberi</h3>
        <div class="space-y-2">
            <?php
            $rehber = [
                ['renk' => 'bg-emerald-500', 'baslik' => 'gpt-4o', 'aciklama' => 'En yüksek doğruluk, karmaşık ekstreler için'],
                ['renk' => 'bg-emerald-400', 'baslik' => 'gpt-4o-mini', 'aciklama' => 'Hız/maliyet dengesi, günlük kullanım için'],
                ['renk' => 'bg-orange-500',  'baslik' => 'claude-3-5-sonnet', 'aciklama' => 'Uzun metin analizi ve detaylı tavsiyeler'],
                ['renk' => 'bg-purple-500',  'baslik' => 'Groq modeller', 'aciklama' => 'Ücretsiz, hızlı, basit işlemler için'],
            ];
            foreach ($rehber as $r):
            ?>
            <div class="flex items-start gap-2.5">
                <span class="w-2 h-2 rounded-full <?= $r['renk'] ?> mt-1.5 flex-shrink-0"></span>
                <div>
                    <span class="text-xs font-medium text-slate-300"><?= $r['baslik'] ?>:</span>
                    <span class="text-xs text-slate-500 ml-1"><?= $r['aciklama'] ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ─── Kaydet Butonu ─── -->
    <button
        type="submit"
        class="w-full bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-semibold py-3.5 px-4 rounded-2xl transition-colors text-sm flex items-center justify-center gap-2 shadow-lg shadow-indigo-900/40"
    >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        Ayarları Kaydet
    </button>

    <!-- Veritabanını Sıfırla linki -->
    <div class="text-center pb-2">
        <a href="init_db.php" class="text-xs text-slate-600 hover:text-slate-400 transition-colors">
            Veritabanı durumunu görüntüle
        </a>
    </div>

</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
