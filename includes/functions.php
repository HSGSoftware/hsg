<?php
/**
 * HSG Aviation - Yardımcı Fonksiyonlar
 */

/**
 * Ayar değeri getir
 */
function ayar($key, $default = '') {
    global $pdo;
    static $cache = [];
    if (!isset($cache[$key])) {
        $stmt = $pdo->prepare("SELECT deger FROM ayarlar WHERE anahtar = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $cache[$key] = $row ? $row['deger'] : $default;
    }
    return $cache[$key];
}

/**
 * Tüm ayarları getir
 */
function tumAyarlar() {
    global $pdo;
    $stmt = $pdo->query("SELECT anahtar, deger FROM ayarlar");
    $ayarlar = [];
    while ($row = $stmt->fetch()) {
        $ayarlar[$row['anahtar']] = $row['deger'];
    }
    return $ayarlar;
}

/**
 * Ayar kaydet
 */
function ayarKaydet($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO ayarlar (anahtar, deger) VALUES (?, ?)
                           ON DUPLICATE KEY UPDATE deger = VALUES(deger)");
    return $stmt->execute([$key, $value]);
}

/**
 * Para formatı
 */
function paraFormat($tutar, $birim = null) {
    if ($birim === null) {
        $birim = ayar('varsayilan_para_birimi', 'TRY');
    }
    $currencies = json_decode(CURRENCIES, true);
    $sembol = $currencies[$birim]['symbol'] ?? $birim;
    return $sembol . ' ' . number_format((float)$tutar, 2, ',', '.');
}

/**
 * Tarih formatı (Türkçe)
 */
function tarihFormat($tarih, $format = 'd.m.Y') {
    if (empty($tarih) || $tarih === '0000-00-00') return '-';
    return date($format, strtotime($tarih));
}

/**
 * Teklif numarası oluştur
 */
function teklifNoOlustur() {
    global $pdo;
    $prefix = ayar('teklif_prefix', 'TKL');
    $yil = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) as sayi FROM teklifler WHERE YEAR(olusturma_tarihi) = ?");
    $stmt->execute([$yil]);
    $row = $stmt->fetch();
    $sira = ($row['sayi'] ?? 0) + 1;
    return $prefix . '-' . $yil . '-' . str_pad($sira, 4, '0', STR_PAD_LEFT);
}

/**
 * Müşteri numarası oluştur
 */
function musteriNoOlustur() {
    global $pdo;
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(musteri_no, 4) AS UNSIGNED)) as max_no FROM musteriler WHERE musteri_no LIKE 'MST%'");
    $row = $stmt->fetch();
    $sira = ($row['max_no'] ?? 0) + 1;
    return 'MST' . str_pad($sira, 5, '0', STR_PAD_LEFT);
}

/**
 * Flash mesaj ekle
 */
function flashMesaj($tip, $mesaj) {
    if (!isset($_SESSION['flash'])) $_SESSION['flash'] = [];
    $_SESSION['flash'][] = ['tip' => $tip, 'mesaj' => $mesaj];
}

/**
 * Flash mesajları göster
 */
function flashGoster() {
    if (empty($_SESSION['flash'])) return '';
    $html = '';
    foreach ($_SESSION['flash'] as $flash) {
        $icon = match($flash['tip']) {
            'success' => 'check-circle',
            'danger'  => 'x-circle',
            'warning' => 'exclamation-triangle',
            default   => 'info-circle'
        };
        $html .= '<div class="alert alert-' . htmlspecialchars($flash['tip']) . ' alert-dismissible fade show" role="alert">
            <i class="bi bi-' . $icon . ' me-2"></i>' . htmlspecialchars($flash['mesaj']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

/**
 * Güvenli HTML çıktı
 */
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * Teklif durum badge HTML
 */
function durumBadge($durum) {
    $durumlar = [
        'taslak'       => ['class' => 'secondary', 'icon' => 'pencil',         'label' => 'Taslak'],
        'gonderildi'   => ['class' => 'primary',   'icon' => 'send',           'label' => 'Gönderildi'],
        'kabul'        => ['class' => 'success',   'icon' => 'check-circle',   'label' => 'Kabul Edildi'],
        'red'          => ['class' => 'danger',    'icon' => 'x-circle',       'label' => 'Reddedildi'],
        'suresi_doldu' => ['class' => 'warning',   'icon' => 'clock-history',  'label' => 'Süresi Doldu'],
    ];
    $d = $durumlar[$durum] ?? ['class' => 'secondary', 'icon' => 'question-circle', 'label' => $durum];
    return '<span class="badge bg-' . $d['class'] . '"><i class="bi bi-' . $d['icon'] . ' me-1"></i>' . $d['label'] . '</span>';
}

/**
 * Teklif durum listesi
 */
function teklifDurumlari() {
    return [
        'taslak'       => 'Taslak',
        'gonderildi'   => 'Gönderildi',
        'kabul'        => 'Kabul Edildi',
        'red'          => 'Reddedildi',
        'suresi_doldu' => 'Süresi Doldu',
    ];
}

/**
 * Logo URL
 */
function logoUrl() {
    $logo = ayar('firma_logo', '');
    if ($logo && file_exists(BASE_PATH . '/uploads/logos/' . $logo)) {
        return BASE_URL . '/uploads/logos/' . $logo;
    }
    return BASE_URL . '/assets/img/logo-default.png';
}

/**
 * Dosya yükle
 */
function dosyaYukle($file, $hedefKlasor, $izinliTipler = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Dosya yükleme hatası: ' . $file['error']);
    }
    if (!in_array($file['type'], $izinliTipler)) {
        throw new Exception('Geçersiz dosya türü.');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Dosya boyutu 5MB\'ı aşamaz.');
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $yeniAd = uniqid('file_') . '.' . $ext;
    $hedef = BASE_PATH . '/' . $hedefKlasor . '/' . $yeniAd;
    if (!move_uploaded_file($file['tmp_name'], $hedef)) {
        throw new Exception('Dosya taşıma hatası.');
    }
    return $yeniAd;
}

/**
 * Sayfalama
 */
function sayfalama($toplamKayit, $sayfaBasi, $mevcutSayfa, $url) {
    $toplamSayfa = ceil($toplamKayit / $sayfaBasi);
    if ($toplamSayfa <= 1) return '';

    $html = '<nav><ul class="pagination pagination-sm mb-0">';

    // Önceki
    $html .= '<li class="page-item ' . ($mevcutSayfa <= 1 ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $url . '&sayfa=' . ($mevcutSayfa - 1) . '"><i class="bi bi-chevron-left"></i></a></li>';

    // Sayfalar
    for ($i = max(1, $mevcutSayfa - 2); $i <= min($toplamSayfa, $mevcutSayfa + 2); $i++) {
        $html .= '<li class="page-item ' . ($i === $mevcutSayfa ? 'active' : '') . '">';
        $html .= '<a class="page-link" href="' . $url . '&sayfa=' . $i . '">' . $i . '</a></li>';
    }

    // Sonraki
    $html .= '<li class="page-item ' . ($mevcutSayfa >= $toplamSayfa ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $url . '&sayfa=' . ($mevcutSayfa + 1) . '"><i class="bi bi-chevron-right"></i></a></li>';

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Süresi dolmuş teklifleri güncelle
 */
function suresiDolanlariGuncelle() {
    global $pdo;
    $pdo->exec("UPDATE teklifler SET durum = 'suresi_doldu'
                WHERE gecerlilik_tarihi < CURDATE()
                AND durum = 'gonderildi'");
}

/**
 * Dashboard istatistikleri
 */
function dashboardIstatistikleri() {
    global $pdo;
    suresiDolanlariGuncelle();

    $stats = [];

    // Teklif sayıları
    $stmt = $pdo->query("SELECT durum, COUNT(*) as sayi, SUM(genel_toplam) as toplam
                         FROM teklifler GROUP BY durum");
    $teklifler = $stmt->fetchAll();
    foreach ($teklifler as $t) {
        $stats['teklifler'][$t['durum']] = ['sayi' => $t['sayi'], 'toplam' => $t['toplam']];
    }

    // Bu ay teklifler
    $stmt = $pdo->query("SELECT COUNT(*) as sayi, SUM(genel_toplam) as toplam
                         FROM teklifler WHERE MONTH(olusturma_tarihi) = MONTH(NOW())
                         AND YEAR(olusturma_tarihi) = YEAR(NOW())");
    $stats['bu_ay'] = $stmt->fetch();

    // Müşteri sayısı
    $stmt = $pdo->query("SELECT COUNT(*) as sayi FROM musteriler WHERE durum = 'aktif'");
    $stats['musteri_sayisi'] = $stmt->fetch()['sayi'];

    // Ürün sayısı
    $stmt = $pdo->query("SELECT COUNT(*) as sayi FROM urunler WHERE durum = 'aktif'");
    $stats['urun_sayisi'] = $stmt->fetch()['sayi'];

    // Son 6 ay grafiği
    $stmt = $pdo->query("SELECT DATE_FORMAT(olusturma_tarihi, '%Y-%m') as ay,
                         COUNT(*) as sayi, SUM(genel_toplam) as toplam
                         FROM teklifler
                         WHERE olusturma_tarihi >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                         GROUP BY DATE_FORMAT(olusturma_tarihi, '%Y-%m')
                         ORDER BY ay");
    $stats['aylik'] = $stmt->fetchAll();

    // Son teklifler
    $stmt = $pdo->query("SELECT t.*, m.firma_adi as musteri_adi
                         FROM teklifler t
                         LEFT JOIN musteriler m ON t.musteri_id = m.id
                         ORDER BY t.olusturma_tarihi DESC LIMIT 10");
    $stats['son_teklifler'] = $stmt->fetchAll();

    // Başarı oranı
    $stmt = $pdo->query("SELECT
                         SUM(CASE WHEN durum = 'kabul' THEN 1 ELSE 0 END) as kabul,
                         COUNT(*) as toplam
                         FROM teklifler WHERE durum != 'taslak'");
    $oran = $stmt->fetch();
    $stats['basari_orani'] = $oran['toplam'] > 0 ? round(($oran['kabul'] / $oran['toplam']) * 100) : 0;

    return $stats;
}
