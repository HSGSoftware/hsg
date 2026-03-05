<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM musteriler WHERE id = ?");
$stmt->execute([$id]);
$musteri = $stmt->fetch();
if (!$musteri) { flashMesaj('danger','Müşteri bulunamadı.'); header('Location: index.php'); exit; }

// Müşterinin teklifleri
$stmt = $pdo->prepare("SELECT * FROM teklifler WHERE musteri_id = ? ORDER BY olusturma_tarihi DESC");
$stmt->execute([$id]);
$teklifler = $stmt->fetchAll();

$pageTitle  = e($musteri['firma_adi']);
$activePage = 'musteriler';
$breadcrumb = [
    ['label' => 'Ana Sayfa',  'url' => BASE_URL . '/index.php'],
    ['label' => 'Müşteriler', 'url' => BASE_URL . '/musteriler/index.php'],
    ['label' => $musteri['firma_adi'], 'url' => ''],
];

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <div class="d-flex align-items-center gap-3">
    <div class="avatar lg"><?= strtoupper(substr($musteri['firma_adi'], 0, 2)) ?></div>
    <div>
      <h1 class="mb-0"><?= e($musteri['firma_adi']) ?></h1>
      <div class="page-subtitle">
        <?= e($musteri['musteri_no']) ?> &nbsp;
        <span class="badge bg-<?= $musteri['durum'] === 'aktif' ? 'success' : 'secondary' ?>"><?= ucfirst($musteri['durum']) ?></span>
      </div>
    </div>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= BASE_URL ?>/teklifler/olustur.php?musteri_id=<?= $id ?>" class="btn btn-warning fw-bold">
      <i class="bi bi-plus me-1"></i>Teklif Oluştur
    </a>
    <a href="duzenle.php?id=<?= $id ?>" class="btn btn-outline-primary">
      <i class="bi bi-pencil me-1"></i>Düzenle
    </a>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Geri</a>
  </div>
</div>

<div class="row g-3">
  <!-- Bilgiler -->
  <div class="col-lg-4">
    <!-- İletişim -->
    <div class="card mb-3">
      <div class="card-header"><h6 class="card-title"><i class="bi bi-person-lines-fill me-2 text-gold"></i>İletişim</h6></div>
      <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
          <tr><td class="text-muted small" width="120">Yetkili</td><td class="fw-semibold"><?= e($musteri['yetkili_kisi'] ?: '-') ?><?= $musteri['unvan'] ? ' <span class="text-muted">(' . e($musteri['unvan']) . ')</span>' : '' ?></td></tr>
          <tr><td class="text-muted small">E-posta</td><td><?= $musteri['email'] ? '<a href="mailto:' . e($musteri['email']) . '">' . e($musteri['email']) . '</a>' : '-' ?></td></tr>
          <tr><td class="text-muted small">Telefon</td><td><?= $musteri['telefon'] ? '<a href="tel:' . e($musteri['telefon']) . '">' . e($musteri['telefon']) . '</a>' : '-' ?></td></tr>
          <?php if ($musteri['telefon2']): ?>
          <tr><td class="text-muted small">Tel 2</td><td><?= e($musteri['telefon2']) ?></td></tr>
          <?php endif; ?>
          <?php if ($musteri['fax']): ?>
          <tr><td class="text-muted small">Faks</td><td><?= e($musteri['fax']) ?></td></tr>
          <?php endif; ?>
          <?php if ($musteri['website']): ?>
          <tr><td class="text-muted small">Web</td><td><a href="<?= e($musteri['website']) ?>" target="_blank"><?= e($musteri['website']) ?></a></td></tr>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- Adres -->
    <div class="card mb-3">
      <div class="card-header"><h6 class="card-title"><i class="bi bi-geo-alt me-2 text-gold"></i>Adres</h6></div>
      <div class="card-body">
        <?php if ($musteri['adres'] || $musteri['sehir']): ?>
          <address class="mb-0 small">
            <?= e($musteri['adres'] ?? '') ?><br>
            <?php if ($musteri['ilce']): ?><?= e($musteri['ilce']) ?>, <?php endif; ?>
            <?= e($musteri['sehir'] ?? '') ?>
            <?php if ($musteri['posta_kodu']): ?> <?= e($musteri['posta_kodu']) ?><?php endif; ?><br>
            <?= e($musteri['ulke'] ?? '') ?>
          </address>
        <?php else: ?>
          <span class="text-muted small">Adres girilmemiş</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Vergi & Banka -->
    <div class="card">
      <div class="card-header"><h6 class="card-title"><i class="bi bi-bank me-2 text-gold"></i>Vergi & Banka</h6></div>
      <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
          <tr><td class="text-muted small">Vergi No</td><td class="fw-semibold"><?= e($musteri['vergi_no'] ?: '-') ?></td></tr>
          <tr><td class="text-muted small">Vergi Dairesi</td><td><?= e($musteri['vergi_dairesi'] ?: '-') ?></td></tr>
          <tr><td class="text-muted small">Banka</td><td><?= e($musteri['banka_adi'] ?: '-') ?></td></tr>
          <?php if ($musteri['iban']): ?>
          <tr><td class="text-muted small">IBAN</td><td><code><?= e($musteri['iban']) ?></code></td></tr>
          <?php endif; ?>
        </table>
      </div>
    </div>
  </div>

  <!-- Teklifler -->
  <div class="col-lg-8">
    <!-- Özet istatistik -->
    <?php
    $toplamTeklif = count($teklifler);
    $kabulSayisi  = count(array_filter($teklifler, fn($t) => $t['durum'] === 'kabul'));
    $kabulToplam  = array_sum(array_column(array_filter($teklifler, fn($t) => $t['durum'] === 'kabul'), 'genel_toplam'));
    ?>
    <div class="row g-3 mb-3">
      <div class="col-4">
        <div class="stat-card"><div class="stat-icon gold"><i class="bi bi-file-text"></i></div>
          <div><div class="stat-value"><?= $toplamTeklif ?></div><div class="stat-label">Toplam Teklif</div></div></div>
      </div>
      <div class="col-4">
        <div class="stat-card"><div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
          <div><div class="stat-value"><?= $kabulSayisi ?></div><div class="stat-label">Kabul Edilen</div></div></div>
      </div>
      <div class="col-4">
        <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-cash-stack"></i></div>
          <div><div class="stat-value" style="font-size:1.2rem;"><?= paraFormat($kabulToplam) ?></div><div class="stat-label">Toplam Ciro</div></div></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0"><i class="bi bi-file-earmark-text me-2 text-gold"></i>Teklifler</h6>
        <a href="<?= BASE_URL ?>/teklifler/olustur.php?musteri_id=<?= $id ?>" class="btn btn-sm btn-warning">
          <i class="bi bi-plus me-1"></i>Yeni Teklif
        </a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($teklifler)): ?>
          <div class="empty-state py-5">
            <div class="empty-icon"><i class="bi bi-file-earmark-text"></i></div>
            <h5>Henüz teklif yok</h5>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead><tr>
                <th>Teklif No</th>
                <th>Başlık</th>
                <th>Tarih</th>
                <th>Geçerlilik</th>
                <th>Tutar</th>
                <th>Durum</th>
                <th>İşlem</th>
              </tr></thead>
              <tbody>
                <?php foreach ($teklifler as $t): ?>
                <tr>
                  <td><code><?= e($t['teklif_no']) ?></code></td>
                  <td><?= e($t['baslik'] ?: 'Fiyat Teklifi') ?></td>
                  <td><?= tarihFormat($t['tarih']) ?></td>
                  <td><?= tarihFormat($t['gecerlilik_tarihi']) ?></td>
                  <td class="fw-semibold"><?= paraFormat($t['genel_toplam'], $t['para_birimi']) ?></td>
                  <td><?= durumBadge($t['durum']) ?></td>
                  <td>
                    <a href="<?= BASE_URL ?>/teklifler/goruntule.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-secondary action-btn">
                      <i class="bi bi-eye"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Notlar -->
    <?php if ($musteri['notlar']): ?>
    <div class="card mt-3">
      <div class="card-header"><h6 class="card-title"><i class="bi bi-sticky me-2 text-gold"></i>Notlar</h6></div>
      <div class="card-body"><p class="mb-0"><?= nl2br(e($musteri['notlar'])) ?></p></div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
