<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM sablonlar WHERE id = ?");
$stmt->execute([$id]);
$sablon = $stmt->fetch();
if (!$sablon) { flashMesaj('danger','Şablon bulunamadı.'); header('Location: index.php'); exit; }

$pageTitle  = 'Şablon Düzenle';
$activePage = 'sablonlar';
$breadcrumb = [
    ['label' => 'Şablonlar', 'url' => BASE_URL . '/sablonlar/index.php'],
    ['label' => 'Düzenle', 'url' => ''],
];

$hatalar = [];
$form = $sablon;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST;
    if (empty(trim($form['ad'] ?? ''))) $hatalar[] = 'Şablon adı zorunludur.';

    if (empty($hatalar)) {
        if ($form['varsayilan'] ?? 0) $pdo->exec("UPDATE sablonlar SET varsayilan = 0");

        $pdo->prepare("UPDATE sablonlar SET
            ad=?, baslik=?, on_yazi=?, son_yazi=?, sartlar=?, odeme_kosullari=?,
            renk_ana=?, renk_aksan=?, logo_goster=?, imza_alani=?, kdv_goster=?,
            iskonto_goster=?, kalem_aciklama_goster=?, gecerlilik_gun=?, varsayilan=?
            WHERE id=?")->execute([
            trim($form['ad']), trim($form['baslik'] ?? ''),
            $form['on_yazi'] ?? '', $form['son_yazi'] ?? '', $form['sartlar'] ?? '', $form['odeme_kosullari'] ?? '',
            $form['renk_ana'] ?? '#0a1628', $form['renk_aksan'] ?? '#e8a000',
            isset($form['logo_goster']) ? 1 : 0, isset($form['imza_alani']) ? 1 : 0,
            isset($form['kdv_goster']) ? 1 : 0, isset($form['iskonto_goster']) ? 1 : 0,
            isset($form['kalem_aciklama_goster']) ? 1 : 0,
            (int)($form['gecerlilik_gun'] ?? 30),
            isset($form['varsayilan']) ? 1 : 0,
            $id
        ]);

        flashMesaj('success', 'Şablon güncellendi.');
        header('Location: index.php'); exit;
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="bi bi-pencil-square me-2 text-gold"></i>Şablon Düzenle</h1>
    <div class="page-subtitle"><?= e($sablon['ad']) ?></div>
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
        <div class="card-header"><h6 class="card-title"><i class="bi bi-layout-text-sidebar me-2 text-gold"></i>Şablon Bilgileri</h6></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Şablon Adı <span class="text-danger">*</span></label>
              <input type="text" name="ad" class="form-control" value="<?= e($form['ad']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Belge Başlığı</label>
              <input type="text" name="baslik" class="form-control" value="<?= e($form['baslik']) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Geçerlilik (gün)</label>
              <input type="number" name="gecerlilik_gun" class="form-control" value="<?= e($form['gecerlilik_gun']) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Ana Renk</label>
              <div class="d-flex gap-2">
                <input type="color" name="renk_ana" id="renk_ana" class="form-control form-control-color" value="<?= e($form['renk_ana']) ?>">
                <input type="text" id="renk_ana_text" class="form-control form-control-sm" value="<?= e($form['renk_ana']) ?>" style="width:90px;" oninput="document.getElementById('renk_ana').value=this.value">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Aksan Rengi</label>
              <div class="d-flex gap-2">
                <input type="color" name="renk_aksan" id="renk_aksan" class="form-control form-control-color" value="<?= e($form['renk_aksan']) ?>">
                <input type="text" id="renk_aksan_text" class="form-control form-control-sm" value="<?= e($form['renk_aksan']) ?>" style="width:90px;" oninput="document.getElementById('renk_aksan').value=this.value">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-file-text me-2 text-gold"></i>Metinler</h6></div>
        <div class="card-body">
          <ul class="nav nav-pills mb-3">
            <li class="nav-item"><button class="nav-link active" type="button" data-bs-toggle="pill" data-bs-target="#t_on">Ön Yazı</button></li>
            <li class="nav-item"><button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#t_son">Son Yazı</button></li>
            <li class="nav-item"><button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#t_sart">Şartlar</button></li>
            <li class="nav-item"><button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#t_odeme">Ödeme</button></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="t_on"><textarea name="on_yazi" class="form-control" rows="5"><?= e($form['on_yazi']) ?></textarea></div>
            <div class="tab-pane fade" id="t_son"><textarea name="son_yazi" class="form-control" rows="5"><?= e($form['son_yazi']) ?></textarea></div>
            <div class="tab-pane fade" id="t_sart"><textarea name="sartlar" class="form-control" rows="6"><?= e($form['sartlar']) ?></textarea></div>
            <div class="tab-pane fade" id="t_odeme"><textarea name="odeme_kosullari" class="form-control" rows="4"><?= e($form['odeme_kosullari']) ?></textarea></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-eye me-2 text-gold"></i>Görünüm</h6></div>
        <div class="card-body">
          <div class="list-group list-group-flush">
            <?php
            $toggleler = [
              ['logo_goster', 'Logo Göster', 'Firma logosu'],
              ['kdv_goster', 'KDV Göster', 'KDV sütunu ve toplamı'],
              ['iskonto_goster', 'İskonto Göster', 'İskonto sütunu'],
              ['kalem_aciklama_goster', 'Kalem Detayı', 'Alt açıklama'],
              ['imza_alani', 'İmza Alanı', 'Kaşe/imza alanı'],
            ];
            foreach ($toggleler as [$name, $label, $sub]):
            ?>
            <label class="list-group-item d-flex justify-content-between align-items-center px-0">
              <div>
                <div class="fw-semibold small"><?= $label ?></div>
                <div class="text-muted" style="font-size:0.72rem;"><?= $sub ?></div>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="<?= $name ?>" <?= ($form[$name] ?? 0) ? 'checked' : '' ?>>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><h6 class="card-title">Varsayılan</h6></div>
        <div class="card-body">
          <label class="d-flex align-items-center gap-2">
            <input type="checkbox" name="varsayilan" value="1" <?= ($form['varsayilan'] ?? 0) ? 'checked' : '' ?> class="form-check-input">
            <div class="fw-semibold small">Varsayılan şablon</div>
          </label>
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
$extraJs = '<script>
document.getElementById("renk_ana").addEventListener("input",function(){document.getElementById("renk_ana_text").value=this.value;});
document.getElementById("renk_aksan").addEventListener("input",function(){document.getElementById("renk_aksan_text").value=this.value;});
</script>';
include dirname(__DIR__) . '/includes/footer.php';
?>
