<?php
/**
 * HSG Aviation - Ürün Arama AJAX
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$search = '%' . $q . '%';
$currencies = json_decode(CURRENCIES, true);

$stmt = $pdo->prepare("SELECT u.*, k.ad as kategori_adi
    FROM urunler u
    LEFT JOIN kategoriler k ON u.kategori_id = k.id
    WHERE u.durum = 'aktif'
    AND (u.ad LIKE ? OR u.urun_kodu LIKE ? OR u.aciklama LIKE ? OR k.ad LIKE ?)
    ORDER BY u.ad
    LIMIT 20");

$stmt->execute([$search, $search, $search, $search]);
$urunler = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sonuclar = array_map(function($u) use ($currencies) {
    $sym = $currencies[$u['para_birimi']]['symbol'] ?? $u['para_birimi'];
    return [
        'id'          => (int)$u['id'],
        'urun_kodu'   => $u['urun_kodu'],
        'ad'          => $u['ad'],
        'aciklama'    => $u['aciklama'],
        'birim'       => $u['birim'],
        'birim_fiyat' => (float)$u['birim_fiyat'],
        'para_birimi' => $u['para_birimi'],
        'kdv_orani'   => (float)$u['kdv_orani'],
        'min_miktar'  => (float)($u['min_miktar'] ?: 1),
        'stok_durumu' => $u['stok_durumu'],
        'kategori'    => $u['kategori_adi'],
        'sym'         => $sym,
    ];
}, $urunler);

echo json_encode($sonuclar, JSON_UNESCAPED_UNICODE);
