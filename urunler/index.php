<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle  = 'Ürün Kataloğu';
$activePage = 'urunler';
$breadcrumb = [
    ['label' => 'Ana Sayfa', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Ürün Kataloğu', 'url' => ''],
];

// Sil
if (isset($_GET['sil'])) {
    $pdo->prepare("UPDATE urunler SET durum='pasif' WHERE id=?")->execute([(int)$_GET['sil']]);
    flashMesaj('success', 'Ürün pasif yapıldı.');
    header('Location: index.php'); exit;
}

$ara      = trim($_GET['ara'] ?? '');
$kategori = (int)($_GET['kategori'] ?? 0);
$durum    = $_GET['durum'] ?? 'aktif';
$sayfa    = max(1, (int)($_GET['sayfa'] ?? 1));
$limit    = 20;
$offset   = ($sayfa - 1) * $limit;

$where  = ['1=1'];
$params = [];

if ($ara) { $where[] = "(u.ad LIKE ? OR u.urun_kodu LIKE ? OR u.aciklama LIKE ?)"; $ara2 = "%$ara%"; $params = array_merge($params, [$ara2, $ara2, $ara2]); }
if ($kategori) { $where[] = "u.kategori_id = ?"; $params[] = $kategori; }
if ($durum !== '') { $where[] = "u.durum = ?"; $params[] = $durum; }

$whereStr = implode(' AND ', $where);

$toplamStmt = $pdo->prepare("SELECT COUNT(*) FROM urunler u WHERE $whereStr");
$toplamStmt->execute($params);
$toplam = $toplamStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT u.*, k.ad as kategori_adi
    FROM urunler u
    LEFT JOIN kategoriler k ON u.kategori_id = k.id
    WHERE $whereStr ORDER BY u.ad LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$urunler = $stmt->fetchAll();

$kategoriler = $pdo->query("SELECT * FROM kategoriler ORDER BY ad")->fetchAll();
$currencies = json_decode(CURRENCIES, true);

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="bi bi-box-seam me-2 text-gold"></i>Ürün Kataloğu</h1>
    <div class="page-subtitle"><?= $toplam ?> ürün</div>
  </div>
  <a href="ekle.php" class="btn btn-warning fw-bold">
    <i class="bi bi-plus-circle me-2"></i>Yeni Ürün
  </a>
</div>

<!-- Filtreler -->
<div class="card mb-3">
  <div class="card-body py-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-4">
        <div class="search-box">
          <i class="bi bi-search"></i>
          <input type="text" name="ara" class="form-control" placeholder="Ürün adı, kodu..." value="<?= e($ara) ?>">
        </div>
      </div>
      <div class="col-md-3">
        <select name="kategori" class="form-select">
          <option value="">Tüm Kategoriler</option>
          <?php foreach ($kategoriler as $k): ?>
            <option value="<?= $k['id'] ?>" <?= $kategori === (int)$k['id'] ? 'selected' : '' ?>><?= e($k['ad']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="durum" class="form-select">
          <option value="aktif" <?= $durum === 'aktif' ? 'selected' : '' ?>>Aktif</option>
          <option value="pasif" <?= $durum === 'pasif' ? 'selected' : '' ?>>Pasif</option>
          <option value="" <?= $durum === '' ? 'selected' : '' ?>>Tümü</option>
        </select>
      </div>
      <div class="col-md-3">
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Filtrele</button>
          <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <?php if (empty($urunler)): ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-box-seam"></i></div>
        <h5>Ürün bulunamadı</h5>
        <a href="ekle.php" class="btn btn-warning mt-2"><i class="bi bi-plus me-2"></i>Ürün Ekle</a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr>
            <th width="100">Ürün Kodu</th>
            <th>Ürün Adı</th>
            <th class="d-none d-md-table-cell">Kategori</th>
            <th class="d-none d-lg-table-cell">Birim</th>
            <th>Fiyat</th>
            <th class="d-none d-md-table-cell">KDV %</th>
            <th class="d-none d-lg-table-cell">Stok</th>
            <th width="80">Durum</th>
            <th width="120">İşlemler</th>
          </tr></thead>
          <tbody>
            <?php foreach ($urunler as $u):
              $sym = $currencies[$u['para_birimi']]['symbol'] ?? $u['para_birimi'];
            ?>
            <tr>
              <td><code class="text-hsg"><?= e($u['urun_kodu'] ?: '-') ?></code></td>
              <td>
                <div class="fw-semibold"><?= e($u['ad']) ?></div>
                <?php if ($u['aciklama']): ?>
                  <div class="text-muted" style="font-size:0.72rem;"><?= e(mb_strimwidth($u['aciklama'], 0, 60, '...')) ?></div>
                <?php endif; ?>
              </td>
              <td class="d-none d-md-table-cell">
                <?php if ($u['kategori_adi']): ?>
                  <span class="badge bg-light text-dark border"><?= e($u['kategori_adi']) ?></span>
                <?php else: ?>-<?php endif; ?>
              </td>
              <td class="d-none d-lg-table-cell"><?= e($u['birim']) ?></td>
              <td class="fw-bold"><?= $sym ?> <?= number_format($u['birim_fiyat'], 2, ',', '.') ?></td>
              <td class="d-none d-md-table-cell">%<?= number_format($u['kdv_orani'], 0) ?></td>
              <td class="d-none d-lg-table-cell">
                <span class="badge bg-<?= $u['stok_durumu'] === 'var' ? 'success' : ($u['stok_durumu'] === 'yok' ? 'danger' : 'warning') ?>">
                  <?= ['var' => 'Var', 'yok' => 'Yok', 'sorulacak' => 'Sorulacak'][$u['stok_durumu']] ?>
                </span>
              </td>
              <td>
                <span class="badge bg-<?= $u['durum'] === 'aktif' ? 'success' : 'secondary' ?>">
                  <?= $u['durum'] === 'aktif' ? 'Aktif' : 'Pasif' ?>
                </span>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <a href="duzenle.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary action-btn" title="Düzenle"><i class="bi bi-pencil"></i></a>
                  <button onclick="onayIste('Ürünü pasif yapmak istediğinize emin misiniz?','?sil=<?= $u['id'] ?>')"
                          class="btn btn-sm btn-outline-danger action-btn" title="Pasif Yap"><i class="bi bi-archive"></i></button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($toplam > $limit): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
          <small class="text-muted"><?= $offset+1 ?>–<?= min($offset+$limit,$toplam) ?> / <?= $toplam ?></small>
          <?= sayfalama($toplam, $limit, $sayfa, '?ara='.urlencode($ara).'&kategori='.$kategori.'&durum='.urlencode($durum)) ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
