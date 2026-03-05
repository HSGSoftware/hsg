<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Müşteriler';
$activePage = 'musteriler';
$breadcrumb = [
    ['label' => 'Ana Sayfa',  'url' => BASE_URL . '/index.php'],
    ['label' => 'Müşteriler', 'url' => BASE_URL . '/musteriler/index.php'],
];

// Arama & Filtre
$ara    = trim($_GET['ara'] ?? '');
$durum  = $_GET['durum'] ?? '';
$sayfa  = max(1, (int)($_GET['sayfa'] ?? 1));
$limit  = 20;
$offset = ($sayfa - 1) * $limit;

// Sil
if (isset($_GET['sil']) && is_numeric($_GET['sil'])) {
    $id = (int)$_GET['sil'];
    // Teklif var mı kontrol
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM teklifler WHERE musteri_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        flashMesaj('warning', 'Bu müşteriye ait teklifler bulunduğu için silinemez. Pasif yapabilirsiniz.');
    } else {
        $pdo->prepare("DELETE FROM musteriler WHERE id = ?")->execute([$id]);
        flashMesaj('success', 'Müşteri silindi.');
    }
    header('Location: index.php');
    exit;
}

// Durum değiştir
if (isset($_GET['durum_degistir']) && isset($_GET['id'])) {
    $id   = (int)$_GET['id'];
    $yeni = $_GET['durum_degistir'] === 'aktif' ? 'pasif' : 'aktif';
    $pdo->prepare("UPDATE musteriler SET durum = ? WHERE id = ?")->execute([$yeni, $id]);
    flashMesaj('success', 'Müşteri durumu güncellendi.');
    header('Location: index.php');
    exit;
}

// Sorgu
$where  = ['1=1'];
$params = [];

if ($ara !== '') {
    $where[]  = "(m.firma_adi LIKE ? OR m.musteri_no LIKE ? OR m.yetkili_kisi LIKE ? OR m.email LIKE ? OR m.telefon LIKE ?)";
    $arama    = '%' . $ara . '%';
    $params   = array_merge($params, [$arama, $arama, $arama, $arama, $arama]);
}
if ($durum !== '') {
    $where[]  = "m.durum = ?";
    $params[] = $durum;
}

$whereStr = implode(' AND ', $where);

$toplamStmt = $pdo->prepare("SELECT COUNT(*) FROM musteriler m WHERE $whereStr");
$toplamStmt->execute($params);
$toplam = $toplamStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT m.*,
    (SELECT COUNT(*) FROM teklifler t WHERE t.musteri_id = m.id) as teklif_sayisi,
    (SELECT SUM(t.genel_toplam) FROM teklifler t WHERE t.musteri_id = m.id AND t.durum = 'kabul') as kabul_toplam
    FROM musteriler m
    WHERE $whereStr
    ORDER BY m.firma_adi ASC
    LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$musteriler = $stmt->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="bi bi-people me-2 text-gold"></i>Müşteriler</h1>
    <div class="page-subtitle"><?= $toplam ?> kayıtlı müşteri</div>
  </div>
  <a href="<?= BASE_URL ?>/musteriler/ekle.php" class="btn btn-warning fw-bold">
    <i class="bi bi-person-plus me-2"></i>Yeni Müşteri
  </a>
</div>

<!-- Filtreler -->
<div class="card mb-3">
  <div class="card-body py-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-6 col-lg-7">
        <div class="search-box">
          <i class="bi bi-search"></i>
          <input type="text" name="ara" class="form-control" placeholder="Firma adı, müşteri no, kişi, e-posta..." value="<?= e($ara) ?>">
        </div>
      </div>
      <div class="col-md-3 col-lg-2">
        <select name="durum" class="form-select">
          <option value="">Tüm Durumlar</option>
          <option value="aktif"  <?= $durum === 'aktif'  ? 'selected' : '' ?>>Aktif</option>
          <option value="pasif"  <?= $durum === 'pasif'  ? 'selected' : '' ?>>Pasif</option>
        </select>
      </div>
      <div class="col-md-3 col-lg-3">
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Ara</button>
          <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Tablo -->
<div class="card">
  <div class="card-body p-0">
    <?php if (empty($musteriler)): ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-people"></i></div>
        <h5>Müşteri bulunamadı</h5>
        <p class="text-muted">Yeni müşteri eklemek için "Yeni Müşteri" butonunu kullanın.</p>
        <a href="ekle.php" class="btn btn-warning mt-2"><i class="bi bi-plus me-2"></i>Müşteri Ekle</a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th width="100">Müşteri No</th>
              <th>Firma Adı</th>
              <th class="d-none d-md-table-cell">Yetkili</th>
              <th class="d-none d-lg-table-cell">İletişim</th>
              <th class="d-none d-md-table-cell" width="80">Teklif</th>
              <th class="d-none d-lg-table-cell">Kabul Tutarı</th>
              <th width="80">Durum</th>
              <th width="120">İşlemler</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($musteriler as $m): ?>
            <tr>
              <td><code class="text-hsg fs-small"><?= e($m['musteri_no']) ?></code></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar"><?= strtoupper(substr($m['firma_adi'], 0, 2)) ?></div>
                  <div>
                    <a href="goruntule.php?id=<?= $m['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                      <?= e($m['firma_adi']) ?>
                    </a>
                    <?php if ($m['vergi_no']): ?>
                      <div class="text-muted" style="font-size:0.72rem;">VKN: <?= e($m['vergi_no']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td class="d-none d-md-table-cell">
                <?= e($m['yetkili_kisi'] ?? '-') ?>
                <?php if ($m['unvan']): ?>
                  <div class="text-muted" style="font-size:0.72rem;"><?= e($m['unvan']) ?></div>
                <?php endif; ?>
              </td>
              <td class="d-none d-lg-table-cell">
                <?php if ($m['email']): ?>
                  <a href="mailto:<?= e($m['email']) ?>" class="text-decoration-none text-muted small">
                    <i class="bi bi-envelope me-1"></i><?= e($m['email']) ?>
                  </a><br>
                <?php endif; ?>
                <?php if ($m['telefon']): ?>
                  <a href="tel:<?= e($m['telefon']) ?>" class="text-decoration-none text-muted small">
                    <i class="bi bi-telephone me-1"></i><?= e($m['telefon']) ?>
                  </a>
                <?php endif; ?>
              </td>
              <td class="d-none d-md-table-cell text-center">
                <span class="badge bg-light text-dark border"><?= $m['teklif_sayisi'] ?></span>
              </td>
              <td class="d-none d-lg-table-cell">
                <span class="text-success fw-semibold"><?= paraFormat($m['kabul_toplam'] ?? 0) ?></span>
              </td>
              <td>
                <span class="badge bg-<?= $m['durum'] === 'aktif' ? 'success' : 'secondary' ?>">
                  <?= ucfirst($m['durum']) ?>
                </span>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <a href="goruntule.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-secondary action-btn" data-bs-toggle="tooltip" title="Görüntüle">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="duzenle.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary action-btn" data-bs-toggle="tooltip" title="Düzenle">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <button onclick="onayIste('Bu müşteriyi silmek istediğinize emin misiniz?', 'index.php?sil=<?= $m['id'] ?>')"
                          class="btn btn-sm btn-outline-danger action-btn" data-bs-toggle="tooltip" title="Sil">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Sayfalama -->
      <?php if ($toplam > $limit): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
          <small class="text-muted"><?= $offset + 1 ?>–<?= min($offset + $limit, $toplam) ?> / <?= $toplam ?> kayıt</small>
          <?= sayfalama($toplam, $limit, $sayfa, '?ara=' . urlencode($ara) . '&durum=' . urlencode($durum)) ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
