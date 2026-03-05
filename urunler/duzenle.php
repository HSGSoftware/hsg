<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM urunler WHERE id = ?");
$stmt->execute([$id]);
$urun = $stmt->fetch();
if (!$urun) { flashMesaj('danger','Ürün bulunamadı.'); header('Location: index.php'); exit; }

$pageTitle  = 'Ürün Düzenle';
$activePage = 'urunler';
$breadcrumb = [
    ['label' => 'Ürün Kataloğu', 'url' => BASE_URL . '/urunler/index.php'],
    ['label' => 'Düzenle', 'url' => ''],
];

$kategoriler = $pdo->query("SELECT * FROM kategoriler ORDER BY ad")->fetchAll();
$currencies  = json_decode(CURRENCIES, true);
$kdvOranlari = json_decode(KDV_ORANLARI, true);
$hatalar     = [];
$form        = $urun;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST;
    if (empty(trim($form['ad'] ?? ''))) $hatalar[] = 'Ürün adı zorunludur.';

    if (empty($hatalar)) {
        $stmt = $pdo->prepare("UPDATE urunler SET
            urun_kodu=?, kategori_id=?, ad=?, aciklama=?, birim=?, birim_fiyat=?,
            para_birimi=?, kdv_orani=?, min_miktar=?, stok_durumu=?, durum=?
            WHERE id=?");
        $stmt->execute([
            trim($form['urun_kodu'] ?? '') ?: null,
            ($form['kategori_id'] ?? '') ?: null,
            trim($form['ad']),
            trim($form['aciklama'] ?? ''),
            trim($form['birim'] ?? 'Adet'),
            str_replace(',', '.', $form['birim_fiyat'] ?? '0'),
            $form['para_birimi'] ?? 'TRY',
            str_replace(',', '.', $form['kdv_orani'] ?? '18'),
            str_replace(',', '.', $form['min_miktar'] ?? '1'),
            $form['stok_durumu'] ?? 'var',
            $form['durum'] ?? 'aktif',
            $id
        ]);
        flashMesaj('success', 'Ürün güncellendi.');
        header('Location: index.php'); exit;
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="bi bi-pencil me-2 text-gold"></i>Ürün Düzenle</h1>
    <div class="page-subtitle"><?= e($urun['ad']) ?></div>
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
              <label class="form-label">Ürün Kodu</label>
              <input type="text" name="urun_kodu" class="form-control" value="<?= e($form['urun_kodu'] ?? '') ?>">
            </div>
            <div class="col-md-8">
              <label class="form-label">Ürün Adı <span class="text-danger">*</span></label>
              <input type="text" name="ad" class="form-control" value="<?= e($form['ad'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Kategori</label>
              <select name="kategori_id" class="form-select">
                <option value="">— Seçin —</option>
                <?php foreach ($kategoriler as $k): ?>
                  <option value="<?= $k['id'] ?>" <?= ($form['kategori_id'] ?? '') == $k['id'] ? 'selected' : '' ?>><?= e($k['ad']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Birim</label>
              <select name="birim" class="form-select">
                <?php foreach (['Adet','Kg','Ton','Metre','Km','m²','m³','Litre','Saat','Gün','Ay','Yıl','Takım','Set','Paket','Kutu','Parça'] as $b): ?>
                  <option <?= ($form['birim'] ?? 'Adet') === $b ? 'selected' : '' ?>><?= $b ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Açıklama</label>
              <textarea name="aciklama" class="form-control" rows="3"><?= e($form['aciklama'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-currency-dollar me-2 text-gold"></i>Fiyat & Vergi</h6></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Birim Fiyat</label>
              <div class="input-group">
                <span class="input-group-text" id="sym-display"><?= $currencies[$form['para_birimi'] ?? 'TRY']['symbol'] ?? '₺' ?></span>
                <input type="number" name="birim_fiyat" class="form-control" value="<?= e($form['birim_fiyat']) ?>" step="0.01" min="0">
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
              <input type="number" name="min_miktar" class="form-control" value="<?= e($form['min_miktar'] ?? '1') ?>" step="0.01" min="0">
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
              <option value="var" <?= ($form['stok_durumu'] ?? '') === 'var' ? 'selected' : '' ?>>Stokta Var</option>
              <option value="yok" <?= ($form['stok_durumu'] ?? '') === 'yok' ? 'selected' : '' ?>>Stok Yok</option>
              <option value="sorulacak" <?= ($form['stok_durumu'] ?? '') === 'sorulacak' ? 'selected' : '' ?>>Sorulacak</option>
            </select>
          </div>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="durum" value="aktif" <?= ($form['durum'] ?? '') === 'aktif' ? 'checked' : '' ?>>
              <label class="form-check-label text-success fw-semibold">Aktif</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="durum" value="pasif" <?= ($form['durum'] ?? '') === 'pasif' ? 'checked' : '' ?>>
              <label class="form-check-label text-secondary fw-semibold">Pasif</label>
            </div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body d-grid gap-2">
          <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-check2 me-2"></i>Güncelle</button>
          <a href="index.php" class="btn btn-outline-secondary">İptal</a>
        </div>
      </div>
    </div>
  </div>
</form>

<?php
$extraJs = '<script>function updateSym(){const sel=document.getElementById("para_birimi");document.getElementById("sym-display").textContent=sel.options[sel.selectedIndex].dataset.sym;}</script>';
include dirname(__DIR__) . '/includes/footer.php';
?>
