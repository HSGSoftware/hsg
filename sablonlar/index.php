<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle  = 'Teklif Şablonları';
$activePage = 'sablonlar';
$breadcrumb = [
    ['label' => 'Ana Sayfa', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Şablonlar', 'url' => ''],
];

// Sil
if (isset($_GET['sil'])) {
    $id = (int)$_GET['sil'];
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM teklifler WHERE sablon_id = ?");
    $cnt->execute([$id]);
    if ($cnt->fetchColumn() > 0) {
        flashMesaj('warning', 'Bu şablon kullanıldığı için silinemez.');
    } else {
        $pdo->prepare("DELETE FROM sablonlar WHERE id = ?")->execute([$id]);
        flashMesaj('success', 'Şablon silindi.');
    }
    header('Location: index.php'); exit;
}

// Varsayılan yap
if (isset($_GET['varsayilan'])) {
    $pdo->exec("UPDATE sablonlar SET varsayilan = 0");
    $pdo->prepare("UPDATE sablonlar SET varsayilan = 1 WHERE id = ?")->execute([(int)$_GET['varsayilan']]);
    flashMesaj('success', 'Varsayılan şablon güncellendi.');
    header('Location: index.php'); exit;
}

$stmt = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM teklifler t WHERE t.sablon_id = s.id) as kullanim
                     FROM sablonlar s ORDER BY s.varsayilan DESC, s.ad");
$sablonlar = $stmt->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="bi bi-layout-text-sidebar me-2 text-gold"></i>Teklif Şablonları</h1>
    <div class="page-subtitle">Teklif formatlarınızı yönetin</div>
  </div>
  <a href="ekle.php" class="btn btn-warning fw-bold">
    <i class="bi bi-plus-circle me-2"></i>Yeni Şablon
  </a>
</div>

<?php if (empty($sablonlar)): ?>
  <div class="card">
    <div class="empty-state py-5">
      <div class="empty-icon"><i class="bi bi-layout-text-sidebar"></i></div>
      <h5>Henüz şablon yok</h5>
      <p class="text-muted">Teklif şablonları oluşturarak standart metin ve görünüm ayarlayabilirsiniz.</p>
      <a href="ekle.php" class="btn btn-warning mt-2"><i class="bi bi-plus me-2"></i>Şablon Oluştur</a>
    </div>
  </div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($sablonlar as $s): ?>
      <div class="col-md-6 col-xl-4">
        <div class="card h-100" style="border-top: 4px solid <?= e($s['renk_ana']) ?>;">
          <div class="card-body">
            <div class="d-flex align-items-start justify-content-between mb-3">
              <div>
                <h5 class="mb-1 fw-bold"><?= e($s['ad']) ?></h5>
                <?php if ($s['varsayilan']): ?>
                  <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i>Varsayılan</span>
                <?php endif; ?>
              </div>
              <div style="width:36px;height:36px;border-radius:8px;background:<?= e($s['renk_ana']) ?>;display:flex;align-items:center;justify-content:center;">
                <div style="width:14px;height:14px;border-radius:3px;background:<?= e($s['renk_aksan']) ?>;"></div>
              </div>
            </div>

            <?php if ($s['baslik']): ?>
              <div class="text-muted small mb-2"><i class="bi bi-text-left me-1"></i><?= e($s['baslik']) ?></div>
            <?php endif; ?>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <div class="small text-muted">Geçerlilik</div>
                <div class="fw-semibold small"><?= $s['gecerlilik_gun'] ?> gün</div>
              </div>
              <div class="col-6">
                <div class="small text-muted">Kullanım</div>
                <div class="fw-semibold small"><?= $s['kullanim'] ?> teklif</div>
              </div>
            </div>

            <!-- Özellikler -->
            <div class="d-flex flex-wrap gap-1 mb-3">
              <?php if ($s['logo_goster']): ?>
                <span class="badge bg-light text-dark border" style="font-size:0.68rem;"><i class="bi bi-image me-1"></i>Logo</span>
              <?php endif; ?>
              <?php if ($s['kdv_goster']): ?>
                <span class="badge bg-light text-dark border" style="font-size:0.68rem;"><i class="bi bi-percent me-1"></i>KDV</span>
              <?php endif; ?>
              <?php if ($s['imza_alani']): ?>
                <span class="badge bg-light text-dark border" style="font-size:0.68rem;"><i class="bi bi-pen me-1"></i>İmza</span>
              <?php endif; ?>
              <?php if ($s['iskonto_goster']): ?>
                <span class="badge bg-light text-dark border" style="font-size:0.68rem;"><i class="bi bi-tag me-1"></i>İskonto</span>
              <?php endif; ?>
            </div>

            <?php if ($s['on_yazi']): ?>
              <div class="text-muted" style="font-size:0.75rem;border-left:2px solid #e2e8f0;padding-left:8px;">
                <?= e(mb_strimwidth($s['on_yazi'], 0, 100, '...')) ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="card-footer bg-white border-top-0 pt-0">
            <div class="d-flex gap-2">
              <a href="duzenle.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary flex-fill">
                <i class="bi bi-pencil me-1"></i>Düzenle
              </a>
              <?php if (!$s['varsayilan']): ?>
                <a href="?varsayilan=<?= $s['id'] ?>" class="btn btn-sm btn-outline-warning flex-fill">
                  <i class="bi bi-star me-1"></i>Varsayılan Yap
                </a>
              <?php endif; ?>
              <button onclick="onayIste('Şablonu silmek istediğinize emin misiniz?','?sil=<?= $s['id'] ?>')"
                      class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
