<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle  = 'Sistem Ayarları';
$activePage = 'ayarlar';
$breadcrumb = [
    ['label' => 'Ana Sayfa', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Ayarlar', 'url' => ''],
];

$hatalar = [];
$aktifTab = $_GET['tab'] ?? 'firma';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = $_POST['tab'] ?? 'firma';

    if ($tab === 'firma') {
        $kaydet = [
            'firma_adi'           => trim($_POST['firma_adi'] ?? ''),
            'firma_unvan'         => trim($_POST['firma_unvan'] ?? ''),
            'firma_adres'         => trim($_POST['firma_adres'] ?? ''),
            'firma_sehir'         => trim($_POST['firma_sehir'] ?? ''),
            'firma_ulke'          => trim($_POST['firma_ulke'] ?? 'Türkiye'),
            'firma_telefon'       => trim($_POST['firma_telefon'] ?? ''),
            'firma_email'         => trim($_POST['firma_email'] ?? ''),
            'firma_website'       => trim($_POST['firma_website'] ?? ''),
            'firma_vergi_no'      => trim($_POST['firma_vergi_no'] ?? ''),
            'firma_vergi_dairesi' => trim($_POST['firma_vergi_dairesi'] ?? ''),
            'firma_iban'          => trim($_POST['firma_iban'] ?? ''),
            'firma_slogan'        => trim($_POST['firma_slogan'] ?? ''),
        ];

        if (empty($kaydet['firma_adi'])) $hatalar[] = 'Firma adı zorunludur.';

        if (empty($hatalar)) {
            foreach ($kaydet as $k => $v) ayarKaydet($k, $v);

            // Logo yükleme
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                try {
                    // Eski logoyu sil
                    $eskiLogo = ayar('firma_logo', '');
                    if ($eskiLogo && file_exists(BASE_PATH . '/uploads/logos/' . $eskiLogo)) {
                        @unlink(BASE_PATH . '/uploads/logos/' . $eskiLogo);
                    }
                    $logoAd = dosyaYukle($_FILES['logo'], 'uploads/logos');
                    ayarKaydet('firma_logo', $logoAd);
                } catch (Exception $ex) {
                    $hatalar[] = 'Logo yüklenemedi: ' . $ex->getMessage();
                }
            }

            if (empty($hatalar)) {
                flashMesaj('success', 'Firma bilgileri kaydedildi.');
                header('Location: index.php?tab=firma'); exit;
            }
        }
    } elseif ($tab === 'sistem') {
        $kaydet = [
            'varsayilan_para_birimi' => $_POST['varsayilan_para_birimi'] ?? 'TRY',
            'varsayilan_kdv'         => (string)(int)($_POST['varsayilan_kdv'] ?? '18'),
            'teklif_prefix'          => strtoupper(trim($_POST['teklif_prefix'] ?? 'TKL')),
            'gecerlilik_gun'         => (string)(int)($_POST['gecerlilik_gun'] ?? '30'),
        ];
        foreach ($kaydet as $k => $v) ayarKaydet($k, $v);
        flashMesaj('success', 'Sistem ayarları kaydedildi.');
        header('Location: index.php?tab=sistem'); exit;
    }
}

$ayarlar = tumAyarlar();
$currencies = json_decode(CURRENCIES, true);
$kdvOranlari = json_decode(KDV_ORANLARI, true);

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <h1><i class="bi bi-gear me-2 text-gold"></i>Sistem Ayarları</h1>
</div>

<?php if (!empty($hatalar)): ?>
  <div class="alert alert-danger alert-permanent"><?= implode('<br>', array_map('htmlspecialchars', $hatalar)) ?></div>
<?php endif; ?>

<div class="row g-3">
  <!-- Sol Menü -->
  <div class="col-lg-3">
    <div class="card">
      <div class="card-body p-2">
        <div class="list-group list-group-flush">
          <a href="?tab=firma" class="list-group-item list-group-item-action <?= $aktifTab === 'firma' ? 'active' : '' ?> rounded-3 mb-1">
            <i class="bi bi-building me-2"></i>Firma Bilgileri
          </a>
          <a href="?tab=sistem" class="list-group-item list-group-item-action <?= $aktifTab === 'sistem' ? 'active' : '' ?> rounded-3 mb-1">
            <i class="bi bi-sliders me-2"></i>Sistem Ayarları
          </a>
          <a href="?tab=veritabani" class="list-group-item list-group-item-action <?= $aktifTab === 'veritabani' ? 'active' : '' ?> rounded-3 mb-1">
            <i class="bi bi-database me-2"></i>Veritabanı
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- İçerik -->
  <div class="col-lg-9">
    <?php if ($aktifTab === 'firma'): ?>
    <!-- Firma Bilgileri -->
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="tab" value="firma">
      <div class="card mb-3">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-building me-2 text-gold"></i>Firma Bilgileri</h6></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Firma Adı <span class="text-danger">*</span></label>
              <input type="text" name="firma_adi" class="form-control" value="<?= e($ayarlar['firma_adi'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tüzel / Ticari Unvan</label>
              <input type="text" name="firma_unvan" class="form-control" value="<?= e($ayarlar['firma_unvan'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Slogan</label>
              <input type="text" name="firma_slogan" class="form-control" value="<?= e($ayarlar['firma_slogan'] ?? '') ?>" placeholder="Havacılık ve Savunma Sanayii Çözümleri">
            </div>
            <div class="col-md-6">
              <label class="form-label">Web Sitesi</label>
              <input type="text" name="firma_website" class="form-control" value="<?= e($ayarlar['firma_website'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Adres</label>
              <textarea name="firma_adres" class="form-control" rows="2"><?= e($ayarlar['firma_adres'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Şehir</label>
              <input type="text" name="firma_sehir" class="form-control" value="<?= e($ayarlar['firma_sehir'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Ülke</label>
              <input type="text" name="firma_ulke" class="form-control" value="<?= e($ayarlar['firma_ulke'] ?? 'Türkiye') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefon</label>
              <input type="text" name="firma_telefon" class="form-control" value="<?= e($ayarlar['firma_telefon'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">E-posta</label>
              <input type="email" name="firma_email" class="form-control" value="<?= e($ayarlar['firma_email'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Vergi No</label>
              <input type="text" name="firma_vergi_no" class="form-control" value="<?= e($ayarlar['firma_vergi_no'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Vergi Dairesi</label>
              <input type="text" name="firma_vergi_dairesi" class="form-control" value="<?= e($ayarlar['firma_vergi_dairesi'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">IBAN</label>
              <input type="text" name="firma_iban" class="form-control" value="<?= e($ayarlar['firma_iban'] ?? '') ?>" placeholder="TR...">
            </div>
          </div>
        </div>
      </div>

      <!-- Logo -->
      <div class="card mb-3">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-image me-2 text-gold"></i>Firma Logosu</h6></div>
        <div class="card-body">
          <div class="row align-items-center g-3">
            <div class="col-md-4">
              <?php $logo = $ayarlar['firma_logo'] ?? ''; if ($logo && file_exists(BASE_PATH . '/uploads/logos/' . $logo)): ?>
                <img src="<?= BASE_URL ?>/uploads/logos/<?= e($logo) ?>" style="max-height:80px;max-width:200px;object-fit:contain;border-radius:8px;border:1px solid #e2e8f0;padding:8px;" alt="Logo">
              <?php else: ?>
                <div class="text-muted small p-3 bg-light rounded text-center">
                  <i class="bi bi-image fs-2 d-block mb-1"></i>Logo yüklenmemiş
                </div>
              <?php endif; ?>
            </div>
            <div class="col-md-8">
              <label class="form-label">Yeni Logo Yükle</label>
              <input type="file" name="logo" class="form-control" accept="image/*">
              <div class="form-text">PNG, JPG veya WEBP. Max 5MB. Transparan arka planlı PNG önerilir.</div>
              <?php if ($logo): ?>
                <div class="mt-2">
                  <a href="?sil_logo=1" class="btn btn-sm btn-outline-danger" onclick="return confirm('Logoyu silmek istiyor musunuz?')">
                    <i class="bi bi-trash me-1"></i>Mevcut Logoyu Kaldır
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-footer">
          <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-check2-circle me-2"></i>Firma Bilgilerini Kaydet</button>
        </div>
      </div>
    </form>

    <?php elseif ($aktifTab === 'sistem'): ?>
    <!-- Sistem Ayarları -->
    <form method="POST">
      <input type="hidden" name="tab" value="sistem">
      <div class="card mb-3">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-sliders me-2 text-gold"></i>Teklif Ayarları</h6></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Varsayılan Para Birimi</label>
              <select name="varsayilan_para_birimi" class="form-select">
                <?php foreach ($currencies as $code => $c): ?>
                  <option value="<?= $code ?>" <?= ($ayarlar['varsayilan_para_birimi'] ?? 'TRY') === $code ? 'selected' : '' ?>>
                    <?= $code ?> — <?= $c['name'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Varsayılan KDV Oranı (%)</label>
              <select name="varsayilan_kdv" class="form-select">
                <?php foreach ($kdvOranlari as $oran): ?>
                  <option value="<?= $oran ?>" <?= (int)($ayarlar['varsayilan_kdv'] ?? 18) === $oran ? 'selected' : '' ?>>%<?= $oran ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Teklif Geçerlilik Süresi (gün)</label>
              <input type="number" name="gecerlilik_gun" class="form-control" value="<?= e($ayarlar['gecerlilik_gun'] ?? '30') ?>" min="1" max="365">
            </div>
            <div class="col-md-6">
              <label class="form-label">Teklif Numara Ön Eki</label>
              <div class="input-group">
                <input type="text" name="teklif_prefix" class="form-control" value="<?= e($ayarlar['teklif_prefix'] ?? 'TKL') ?>" maxlength="5">
                <span class="input-group-text">-<?= date('Y') ?>-0001</span>
              </div>
              <div class="form-text">Örnek: TKL-<?= date('Y') ?>-0001</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-footer">
          <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-check2-circle me-2"></i>Sistem Ayarlarını Kaydet</button>
        </div>
      </div>
    </form>

    <?php elseif ($aktifTab === 'veritabani'): ?>
    <!-- Veritabanı Bilgileri -->
    <div class="card">
      <div class="card-header"><h6 class="card-title"><i class="bi bi-database me-2 text-gold"></i>Veritabanı Bilgileri</h6></div>
      <div class="card-body">
        <?php
        try {
            $stmt = $pdo->query("SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH
                                FROM information_schema.TABLES
                                WHERE TABLE_SCHEMA = '" . DB_NAME . "'
                                ORDER BY TABLE_NAME");
            $tables = $stmt->fetchAll();
        ?>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr>
              <th>Tablo</th>
              <th class="text-end">Kayıt Sayısı</th>
              <th class="text-end">Boyut</th>
            </tr></thead>
            <tbody>
              <?php foreach ($tables as $t): ?>
              <tr>
                <td><code><?= e($t['TABLE_NAME']) ?></code></td>
                <td class="text-end"><?= number_format($t['TABLE_ROWS']) ?></td>
                <td class="text-end"><?= round(($t['DATA_LENGTH'] + $t['INDEX_LENGTH']) / 1024, 2) ?> KB</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-3 p-3 bg-light rounded">
          <div class="fw-semibold mb-1 small">Bağlantı Bilgileri</div>
          <table class="table table-sm table-borderless mb-0 small">
            <tr><td class="text-muted" width="150">Sunucu</td><td><?= e(DB_HOST) ?></td></tr>
            <tr><td class="text-muted">Veritabanı</td><td><?= e(DB_NAME) ?></td></tr>
            <tr><td class="text-muted">Charset</td><td><?= e(DB_CHARSET) ?></td></tr>
            <tr><td class="text-muted">Uygulama</td><td><?= APP_NAME ?> v<?= APP_VERSION ?></td></tr>
          </table>
        </div>
        <?php } catch (Exception $e) { ?>
          <div class="alert alert-danger"><?= e($e->getMessage()) ?></div>
        <?php } ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php
// Logo silme
if (isset($_GET['sil_logo'])) {
    $eskiLogo = ayar('firma_logo', '');
    if ($eskiLogo && file_exists(BASE_PATH . '/uploads/logos/' . $eskiLogo)) {
        @unlink(BASE_PATH . '/uploads/logos/' . $eskiLogo);
    }
    ayarKaydet('firma_logo', '');
    flashMesaj('success', 'Logo kaldırıldı.');
    header('Location: index.php?tab=firma'); exit;
}

include dirname(__DIR__) . '/includes/footer.php';
?>
