<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle  = 'Yeni Ürün';
$activePage = 'urunler';
$breadcrumb = [
    ['label' => 'Ürün Kataloğu', 'url' => BASE_URL . '/urunler/index.php'],
    ['label' => 'Yeni Ürün', 'url' => ''],
];

$kategoriler = $pdo->query("SELECT * FROM kategoriler ORDER BY ad")->fetchAll();
$currencies  = json_decode(CURRENCIES, true);
$kdvOranlari = json_decode(KDV_ORANLARI, true);
$hatalar     = [];
$form = [
    'birim'        => 'Adet',
    'para_birimi'  => ayar('varsayilan_para_birimi', 'TRY'),
    'kdv_orani'    => ayar('varsayilan_kdv', '18'),
    'stok_durumu'  => 'var',
    'durum'        => 'aktif',
    'min_miktar'   => '1',
    'birim_fiyat'  => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST;
    if (empty(trim($form['ad'] ?? ''))) $hatalar[] = 'Ürün adı zorunludur.';
    if (!is_numeric($form['birim_fiyat'] ?? '')) $hatalar[] = 'Geçerli bir fiyat giriniz.';

    if (empty($hatalar)) {
        $stmt = $pdo->prepare("INSERT INTO urunler
            (urun_kodu, kategori_id, ad, aciklama, birim, birim_fiyat, para_birimi, kdv_orani, min_miktar, stok_durumu, durum)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            trim($form['urun_kodu'] ?? '') ?: null,
            ($form['kategori_id'] ?? '') ?: null,
            trim($form['ad']),
            trim($form['aciklama'] ?? ''),
            trim($form['birim'] ?? 'Adet'),
            str_replace(',', '.', $form['birim_fiyat']),
            $form['para_birimi'] ?? 'TRY',
            str_replace(',', '.', $form['kdv_orani'] ?? '18'),
            str_replace(',', '.', $form['min_miktar'] ?? '1'),
            $form['stok_durumu'] ?? 'var',
            $form['durum'] ?? 'aktif',
        ]);
        flashMesaj('success', 'Ürün eklendi.');

        if (isset($_POST['ekle_devam'])) {
            header('Location: ekle.php');
        } else {
            header('Location: index.php');
        }
        exit;
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="bi bi-plus-circle me-2 text-gold"></i>Yeni Ürün</h1>
    <div class="page-subtitle">Kataloga yeni ürün veya hizmet ekle</div>
  </div>
  <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Geri</a>
</div>

<?php if (!empty($hatalar)): ?>
  <div class="alert alert-danger alert-permanent"><?= implode('<br>', array_map('htmlspecialchars', $hatalar)) ?></div>
<?php endif; ?>

<form method="POST">
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-box-seam me-2 text-gold"></i>Ürün Bilgileri</h6></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Ürün / Parça Kodu</label>
              <input type="text" name="urun_kodu" class="form-control" value="<?= e($form['urun_kodu'] ?? '') ?>" placeholder="HSG-001">
            </div>
            <div class="col-md-8">
              <label class="form-label">Ürün / Hizmet Adı <span class="text-danger">*</span></label>
              <input type="text" name="ad" class="form-control" value="<?= e($form['ad'] ?? '') ?>" required placeholder="Ürün veya hizmet adı">
            </div>
            <div class="col-md-6">
              <label class="form-label">Kategori</label>
              <select name="kategori_id" class="form-select">
                <option value="">— Kategori Seçin —</option>
                <?php foreach ($kategoriler as $k): ?>
                  <option value="<?= $k['id'] ?>" <?= ($form['kategori_id'] ?? '') == $k['id'] ? 'selected' : '' ?>>
                    <?= e($k['ad']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Birim</label>
              <select name="birim" class="form-select">
                <?php foreach (['Adet','Kg','Ton','Metre','Km','m²','m³','Litre','Saat','Gün','Ay','Yıl','Takım','Set','Paket','Kutu','Kg/m','Parça'] as $b): ?>
                  <option <?= ($form['birim'] ?? 'Adet') === $b ? 'selected' : '' ?>><?= $b ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Açıklama / Teknik Detay</label>
              <textarea name="aciklama" class="form-control" rows="3" placeholder="Ürün özellikleri, teknik bilgiler, notlar..."><?= e($form['aciklama'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-currency-dollar me-2 text-gold"></i>Fiyat & Vergi</h6></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Birim Fiyat <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text" id="sym-display">
                  <?= $currencies[$form['para_birimi']]['symbol'] ?? '₺' ?>
                </span>
                <input type="number" name="birim_fiyat" class="form-control" value="<?= e($form['birim_fiyat']) ?>"
                       step="0.01" min="0" required placeholder="0.00">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Para Birimi</label>
              <select name="para_birimi" id="para_birimi" class="form-select" onchange="updateSym()">
                <?php foreach ($currencies as $code => $c): ?>
                  <option value="<?= $code ?>" data-sym="<?= $c['symbol'] ?>" <?= ($form['para_birimi'] ?? 'TRY') === $code ? 'selected' : '' ?>>
                    <?= $code ?> — <?= $c['name'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">KDV Oranı (%)</label>
              <select name="kdv_orani" class="form-select">
                <?php foreach ($kdvOranlari as $oran): ?>
                  <option value="<?= $oran ?>" <?= (float)($form['kdv_orani'] ?? 18) === (float)$oran ? 'selected' : '' ?>>%<?= $oran ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Min. Sipariş Miktarı</label>
              <input type="number" name="min_miktar" class="form-control" value="<?= e($form['min_miktar'] ?? '1') ?>" min="0" step="0.01">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-header"><h6 class="card-title">Stok & Durum</h6></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Stok Durumu</label>
            <select name="stok_durumu" class="form-select">
              <option value="var"       <?= ($form['stok_durumu'] ?? 'var') === 'var' ? 'selected' : '' ?>>Stokta Var</option>
              <option value="yok"       <?= ($form['stok_durumu'] ?? '') === 'yok' ? 'selected' : '' ?>>Stok Yok</option>
              <option value="sorulacak" <?= ($form['stok_durumu'] ?? '') === 'sorulacak' ? 'selected' : '' ?>>Sorulacak</option>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label">Ürün Durumu</label>
            <div class="d-flex gap-3 mt-1">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="durum" value="aktif" <?= ($form['durum'] ?? 'aktif') === 'aktif' ? 'checked' : '' ?>>
                <label class="form-check-label text-success fw-semibold">Aktif</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="durum" value="pasif" <?= ($form['durum'] ?? '') === 'pasif' ? 'checked' : '' ?>>
                <label class="form-check-label text-secondary fw-semibold">Pasif</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body d-grid gap-2">
          <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-check2-circle me-2"></i>Ürünü Kaydet</button>
          <button type="submit" name="ekle_devam" value="1" class="btn btn-outline-primary">
            <i class="bi bi-plus-circle me-2"></i>Kaydet + Yeni Ekle
          </button>
          <a href="index.php" class="btn btn-outline-secondary">İptal</a>
        </div>
      </div>
    </div>
  </div>
</form>

<?php
$extraJs = '<script>
function updateSym() {
  const sel = document.getElementById("para_birimi");
  const opt = sel.options[sel.selectedIndex];
  document.getElementById("sym-display").textContent = opt.dataset.sym;
}
</script>';
include dirname(__DIR__) . '/includes/footer.php';
?>
