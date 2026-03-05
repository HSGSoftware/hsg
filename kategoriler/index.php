<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle  = 'Kategoriler';
$activePage = 'kategoriler';
$breadcrumb = [
    ['label' => 'Ana Sayfa', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Kategoriler','url' => ''],
];

// Sil
if (isset($_GET['sil'])) {
    $id = (int)$_GET['sil'];
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM urunler WHERE kategori_id = ?");
    $cnt->execute([$id]);
    if ($cnt->fetchColumn() > 0) {
        flashMesaj('warning', 'Bu kategoride ürün bulunduğu için silinemez.');
    } else {
        $pdo->prepare("DELETE FROM kategoriler WHERE id = ?")->execute([$id]);
        flashMesaj('success', 'Kategori silindi.');
    }
    header('Location: index.php'); exit;
}

// Ekle / Düzenle
$hatalar = [];
$formKategori = ['ad' => '', 'aciklama' => ''];
$duzenlemeId = (int)($_GET['duzenle'] ?? 0);

if ($duzenlemeId) {
    $stmt = $pdo->prepare("SELECT * FROM kategoriler WHERE id = ?");
    $stmt->execute([$duzenlemeId]);
    $formKategori = $stmt->fetch() ?: $formKategori;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad = trim($_POST['ad'] ?? '');
    $aciklama = trim($_POST['aciklama'] ?? '');
    $editId = (int)($_POST['edit_id'] ?? 0);

    if (empty($ad)) $hatalar[] = 'Kategori adı zorunludur.';

    if (empty($hatalar)) {
        if ($editId > 0) {
            $pdo->prepare("UPDATE kategoriler SET ad=?, aciklama=? WHERE id=?")->execute([$ad, $aciklama, $editId]);
            flashMesaj('success', 'Kategori güncellendi.');
        } else {
            $pdo->prepare("INSERT INTO kategoriler (ad, aciklama) VALUES (?,?)")->execute([$ad, $aciklama]);
            flashMesaj('success', 'Kategori eklendi.');
        }
        header('Location: index.php'); exit;
    } else {
        $formKategori = ['ad' => $ad, 'aciklama' => $aciklama];
        $duzenlemeId = $editId;
    }
}

$stmt = $pdo->query("SELECT k.*, COUNT(u.id) as urun_sayisi
                     FROM kategoriler k
                     LEFT JOIN urunler u ON u.kategori_id = k.id
                     GROUP BY k.id ORDER BY k.ad");
$kategoriler = $stmt->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <h1><i class="bi bi-tags me-2 text-gold"></i>Kategoriler</h1>
</div>

<div class="row g-3">
  <!-- Form -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">
        <h6 class="card-title"><?= $duzenlemeId ? '<i class="bi bi-pencil me-2"></i>Kategori Düzenle' : '<i class="bi bi-plus-circle me-2 text-gold"></i>Yeni Kategori' ?></h6>
      </div>
      <div class="card-body">
        <?php if (!empty($hatalar)): ?>
          <div class="alert alert-danger"><div><?= implode('</div><div>', array_map('htmlspecialchars', $hatalar)) ?></div></div>
        <?php endif; ?>
        <form method="POST">
          <input type="hidden" name="edit_id" value="<?= $duzenlemeId ?>">
          <div class="mb-3">
            <label class="form-label">Kategori Adı <span class="text-danger">*</span></label>
            <input type="text" name="ad" class="form-control" value="<?= e($formKategori['ad']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Açıklama</label>
            <textarea name="aciklama" class="form-control" rows="3"><?= e($formKategori['aciklama']) ?></textarea>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning fw-bold flex-fill">
              <i class="bi bi-check2 me-1"></i><?= $duzenlemeId ? 'Güncelle' : 'Ekle' ?>
            </button>
            <?php if ($duzenlemeId): ?>
              <a href="index.php" class="btn btn-outline-secondary">İptal</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Liste -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><h6 class="card-title"><i class="bi bi-list me-2 text-gold"></i>Kategori Listesi (<?= count($kategoriler) ?>)</h6></div>
      <div class="card-body p-0">
        <?php if (empty($kategoriler)): ?>
          <div class="empty-state py-5">
            <div class="empty-icon"><i class="bi bi-tags"></i></div>
            <h5>Henüz kategori yok</h5>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead><tr>
                <th>Kategori Adı</th>
                <th>Açıklama</th>
                <th class="text-center" width="80">Ürün Sayısı</th>
                <th width="100">İşlemler</th>
              </tr></thead>
              <tbody>
                <?php foreach ($kategoriler as $k): ?>
                <tr>
                  <td class="fw-semibold"><?= e($k['ad']) ?></td>
                  <td class="text-muted small"><?= e($k['aciklama'] ?: '-') ?></td>
                  <td class="text-center"><span class="badge bg-light text-dark border"><?= $k['urun_sayisi'] ?></span></td>
                  <td>
                    <div class="d-flex gap-1">
                      <a href="?duzenle=<?= $k['id'] ?>" class="btn btn-sm btn-outline-primary action-btn"><i class="bi bi-pencil"></i></a>
                      <button onclick="onayIste('Kategoriyi silmek istediğinize emin misiniz?','?sil=<?= $k['id'] ?>')"
                              class="btn btn-sm btn-outline-danger action-btn"><i class="bi bi-trash"></i></button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
