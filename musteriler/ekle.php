<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle  = 'Yeni Müşteri';
$activePage = 'musteriler';
$breadcrumb = [
    ['label' => 'Ana Sayfa',   'url' => BASE_URL . '/index.php'],
    ['label' => 'Müşteriler',  'url' => BASE_URL . '/musteriler/index.php'],
    ['label' => 'Yeni Müşteri','url' => ''],
];

$hatalar = [];
$form    = ['ulke' => 'Türkiye', 'durum' => 'aktif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST;

    // Validasyon
    if (empty(trim($form['firma_adi'] ?? ''))) $hatalar[] = 'Firma adı zorunludur.';

    if (empty($hatalar)) {
        $musteri_no = musteriNoOlustur();
        $stmt = $pdo->prepare("INSERT INTO musteriler
            (musteri_no, firma_adi, yetkili_kisi, unvan, email, telefon, telefon2, fax,
             website, adres, ilce, sehir, il, posta_kodu, ulke,
             vergi_no, vergi_dairesi, banka_adi, iban, notlar, durum)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $stmt->execute([
            $musteri_no,
            trim($form['firma_adi']),
            trim($form['yetkili_kisi'] ?? ''),
            trim($form['unvan'] ?? ''),
            trim($form['email'] ?? ''),
            trim($form['telefon'] ?? ''),
            trim($form['telefon2'] ?? ''),
            trim($form['fax'] ?? ''),
            trim($form['website'] ?? ''),
            trim($form['adres'] ?? ''),
            trim($form['ilce'] ?? ''),
            trim($form['sehir'] ?? ''),
            trim($form['il'] ?? ''),
            trim($form['posta_kodu'] ?? ''),
            trim($form['ulke'] ?? 'Türkiye'),
            trim($form['vergi_no'] ?? ''),
            trim($form['vergi_dairesi'] ?? ''),
            trim($form['banka_adi'] ?? ''),
            trim($form['iban'] ?? ''),
            trim($form['notlar'] ?? ''),
            $form['durum'] ?? 'aktif',
        ]);

        $yeniId = $pdo->lastInsertId();
        flashMesaj('success', "Müşteri oluşturuldu. Müşteri No: $musteri_no");

        if (isset($_POST['ve_teklif'])) {
            header("Location: " . BASE_URL . "/teklifler/olustur.php?musteri_id=$yeniId");
        } else {
            header("Location: goruntule.php?id=$yeniId");
        }
        exit;
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="bi bi-person-plus me-2 text-gold"></i>Yeni Müşteri</h1>
    <div class="page-subtitle">Müşteri bilgilerini doldurun</div>
  </div>
  <a href="index.php" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-2"></i>Geri
  </a>
</div>

<?php if (!empty($hatalar)): ?>
  <div class="alert alert-danger alert-permanent">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <?php foreach ($hatalar as $h): ?><div><?= e($h) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<form method="POST">
  <div class="row g-3">

    <!-- Sol: Firma Bilgileri -->
    <div class="col-lg-8">
      <!-- Temel Bilgiler -->
      <div class="card mb-3">
        <div class="card-header">
          <h6 class="card-title"><i class="bi bi-building me-2 text-gold"></i>Firma Bilgileri</h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Firma Adı <span class="text-danger">*</span></label>
              <input type="text" name="firma_adi" class="form-control" value="<?= e($form['firma_adi'] ?? '') ?>" required placeholder="Şirket / Kişi adı">
            </div>
            <div class="col-md-6">
              <label class="form-label">Yetkili Kişi</label>
              <input type="text" name="yetkili_kisi" class="form-control" value="<?= e($form['yetkili_kisi'] ?? '') ?>" placeholder="Ad Soyad">
            </div>
            <div class="col-md-6">
              <label class="form-label">Unvan</label>
              <input type="text" name="unvan" class="form-control" value="<?= e($form['unvan'] ?? '') ?>" placeholder="Müdür, Satın Alma Uzmanı...">
            </div>
            <div class="col-md-6">
              <label class="form-label">E-posta</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" value="<?= e($form['email'] ?? '') ?>" placeholder="firma@email.com">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Web Sitesi</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-globe"></i></span>
                <input type="text" name="website" class="form-control" value="<?= e($form['website'] ?? '') ?>" placeholder="https://www.firma.com">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Telefon</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                <input type="tel" name="telefon" class="form-control" value="<?= e($form['telefon'] ?? '') ?>" placeholder="+90 ...">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Telefon 2</label>
              <input type="tel" name="telefon2" class="form-control" value="<?= e($form['telefon2'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Faks</label>
              <input type="tel" name="fax" class="form-control" value="<?= e($form['fax'] ?? '') ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Adres -->
      <div class="card mb-3">
        <div class="card-header">
          <h6 class="card-title"><i class="bi bi-geo-alt me-2 text-gold"></i>Adres Bilgileri</h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Adres</label>
              <textarea name="adres" class="form-control" rows="2" placeholder="Sokak, mahalle, bina no..."><?= e($form['adres'] ?? '') ?></textarea>
            </div>
            <div class="col-md-3">
              <label class="form-label">İlçe</label>
              <input type="text" name="ilce" class="form-control" value="<?= e($form['ilce'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Şehir</label>
              <input type="text" name="sehir" class="form-control" value="<?= e($form['sehir'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">İl / Eyalet</label>
              <input type="text" name="il" class="form-control" value="<?= e($form['il'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Posta Kodu</label>
              <input type="text" name="posta_kodu" class="form-control" value="<?= e($form['posta_kodu'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Ülke</label>
              <input type="text" name="ulke" class="form-control" value="<?= e($form['ulke'] ?? 'Türkiye') ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Vergi & Banka -->
      <div class="card">
        <div class="card-header">
          <h6 class="card-title"><i class="bi bi-bank me-2 text-gold"></i>Vergi & Banka Bilgileri</h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Vergi / TC Kimlik No</label>
              <input type="text" name="vergi_no" class="form-control" value="<?= e($form['vergi_no'] ?? '') ?>" maxlength="11">
            </div>
            <div class="col-md-6">
              <label class="form-label">Vergi Dairesi</label>
              <input type="text" name="vergi_dairesi" class="form-control" value="<?= e($form['vergi_dairesi'] ?? '') ?>">
            </div>
            <div class="col-md-5">
              <label class="form-label">Banka Adı</label>
              <input type="text" name="banka_adi" class="form-control" value="<?= e($form['banka_adi'] ?? '') ?>">
            </div>
            <div class="col-md-7">
              <label class="form-label">IBAN</label>
              <input type="text" name="iban" class="form-control" value="<?= e($form['iban'] ?? '') ?>" placeholder="TR...">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sağ: Durum & Notlar -->
    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-header">
          <h6 class="card-title"><i class="bi bi-sliders me-2 text-gold"></i>Durum</h6>
        </div>
        <div class="card-body">
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="durum" id="aktif" value="aktif" <?= ($form['durum'] ?? 'aktif') === 'aktif' ? 'checked' : '' ?>>
              <label class="form-check-label text-success fw-semibold" for="aktif">Aktif</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="durum" id="pasif" value="pasif" <?= ($form['durum'] ?? '') === 'pasif' ? 'checked' : '' ?>>
              <label class="form-check-label text-secondary fw-semibold" for="pasif">Pasif</label>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header">
          <h6 class="card-title"><i class="bi bi-sticky me-2 text-gold"></i>Notlar</h6>
        </div>
        <div class="card-body">
          <textarea name="notlar" class="form-control" rows="6" placeholder="Müşteri hakkında özel notlar..."><?= e($form['notlar'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- Kaydet -->
      <div class="card">
        <div class="card-body">
          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-warning fw-bold">
              <i class="bi bi-check2-circle me-2"></i>Müşteriyi Kaydet
            </button>
            <button type="submit" name="ve_teklif" value="1" class="btn btn-primary">
              <i class="bi bi-file-earmark-plus me-2"></i>Kaydet ve Teklif Oluştur
            </button>
            <a href="index.php" class="btn btn-outline-secondary">İptal</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
