<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle  = 'Yeni Şablon';
$activePage = 'sablonlar';
$breadcrumb = [
    ['label' => 'Şablonlar', 'url' => BASE_URL . '/sablonlar/index.php'],
    ['label' => 'Yeni Şablon', 'url' => ''],
];

$hatalar = [];
$form = [
    'ad'                   => '',
    'baslik'               => 'FİYAT TEKLİFİ',
    'renk_ana'             => '#0a1628',
    'renk_aksan'           => '#e8a000',
    'gecerlilik_gun'       => '30',
    'logo_goster'          => '1',
    'imza_alani'           => '1',
    'kdv_goster'           => '1',
    'iskonto_goster'       => '1',
    'kalem_aciklama_goster'=> '1',
    'varsayilan'           => '0',
    'on_yazi'              => "Sayın İlgili,\n\nAşağıda belirtilen ürün/hizmetlere ait fiyat teklifimizi bilgilerinize sunarız.",
    'son_yazi'             => "Teklifimizi değerlendirmenizi ve olumlu yanıtınızı bekliyoruz.\n\nSaygılarımızla,\n" . ayar('firma_adi', 'HSG Aviation'),
    'sartlar'              => "1. Bu teklif yukarıda belirtilen geçerlilik tarihine kadar geçerlidir.\n2. Fiyatlar KDV hariçtir, aksi belirtilmedikçe.\n3. Teslimat süresi sipariş onayından sonra bildirilecektir.\n4. Mücbir sebep hallerinde teslimat süresi uzayabilir.",
    'odeme_kosullari'      => 'Peşin veya karşılıklı mutabakat esasına göre.',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST;
    if (empty(trim($form['ad'] ?? ''))) $hatalar[] = 'Şablon adı zorunludur.';

    if (empty($hatalar)) {
        $stmt = $pdo->prepare("INSERT INTO sablonlar
            (ad, baslik, on_yazi, son_yazi, sartlar, odeme_kosullari,
             renk_ana, renk_aksan, logo_goster, imza_alani, kdv_goster,
             iskonto_goster, kalem_aciklama_goster, gecerlilik_gun, varsayilan)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        if ($form['varsayilan'] ?? 0) {
            $pdo->exec("UPDATE sablonlar SET varsayilan = 0");
        }

        $stmt->execute([
            trim($form['ad']),
            trim($form['baslik'] ?? ''),
            $form['on_yazi'] ?? '',
            $form['son_yazi'] ?? '',
            $form['sartlar'] ?? '',
            $form['odeme_kosullari'] ?? '',
            $form['renk_ana'] ?? '#0a1628',
            $form['renk_aksan'] ?? '#e8a000',
            isset($form['logo_goster']) ? 1 : 0,
            isset($form['imza_alani']) ? 1 : 0,
            isset($form['kdv_goster']) ? 1 : 0,
            isset($form['iskonto_goster']) ? 1 : 0,
            isset($form['kalem_aciklama_goster']) ? 1 : 0,
            (int)($form['gecerlilik_gun'] ?? 30),
            isset($form['varsayilan']) ? 1 : 0,
        ]);

        flashMesaj('success', 'Şablon oluşturuldu.');
        header('Location: index.php'); exit;
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="bi bi-plus-circle me-2 text-gold"></i>Yeni Şablon</h1>
    <div class="page-subtitle">Teklif şablonu oluşturun</div>
  </div>
  <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Geri</a>
</div>

<?php if (!empty($hatalar)): ?>
  <div class="alert alert-danger alert-permanent"><?= implode('<br>', array_map('htmlspecialchars', $hatalar)) ?></div>
<?php endif; ?>

<form method="POST">
  <div class="row g-3">
    <!-- Sol: İçerik -->
    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-layout-text-sidebar me-2 text-gold"></i>Şablon Bilgileri</h6></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Şablon Adı <span class="text-danger">*</span></label>
              <input type="text" name="ad" class="form-control" value="<?= e($form['ad']) ?>" required placeholder="Standart Teklif, Proje Teklifi...">
            </div>
            <div class="col-md-6">
              <label class="form-label">Teklif Başlığı (Belge üstü)</label>
              <input type="text" name="baslik" class="form-control" value="<?= e($form['baslik']) ?>" placeholder="FİYAT TEKLİFİ">
            </div>
            <div class="col-md-4">
              <label class="form-label">Geçerlilik Süresi (gün)</label>
              <input type="number" name="gecerlilik_gun" class="form-control" value="<?= e($form['gecerlilik_gun']) ?>" min="1" max="365">
            </div>
            <div class="col-md-4">
              <label class="form-label">Ana Renk</label>
              <div class="d-flex gap-2 align-items-center">
                <input type="color" name="renk_ana" id="renk_ana" class="form-control form-control-color" value="<?= e($form['renk_ana']) ?>">
                <input type="text" id="renk_ana_text" class="form-control form-control-sm" value="<?= e($form['renk_ana']) ?>" style="width:90px;"
                       oninput="document.getElementById('renk_ana').value=this.value">
              </div>
              <div class="form-text">Başlık, tablo üstü rengi</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Aksan Rengi</label>
              <div class="d-flex gap-2 align-items-center">
                <input type="color" name="renk_aksan" id="renk_aksan" class="form-control form-control-color" value="<?= e($form['renk_aksan']) ?>">
                <input type="text" id="renk_aksan_text" class="form-control form-control-sm" value="<?= e($form['renk_aksan']) ?>" style="width:90px;"
                       oninput="document.getElementById('renk_aksan').value=this.value">
              </div>
              <div class="form-text">Toplam, vurgu rengi</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Metinler -->
      <div class="card mb-3">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-file-text me-2 text-gold"></i>Şablon Metinleri</h6></div>
        <div class="card-body">
          <ul class="nav nav-pills mb-3">
            <li class="nav-item"><button class="nav-link active" type="button" data-bs-toggle="pill" data-bs-target="#t_on">Ön Yazı</button></li>
            <li class="nav-item"><button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#t_son">Son Yazı</button></li>
            <li class="nav-item"><button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#t_sart">Şartlar</button></li>
            <li class="nav-item"><button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#t_odeme">Ödeme</button></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="t_on">
              <label class="form-label small text-muted">Kalem tablosundan önce görünecek metin</label>
              <textarea name="on_yazi" class="form-control" rows="5"><?= e($form['on_yazi']) ?></textarea>
            </div>
            <div class="tab-pane fade" id="t_son">
              <label class="form-label small text-muted">Kalem tablosundan sonra görünecek metin</label>
              <textarea name="son_yazi" class="form-control" rows="5"><?= e($form['son_yazi']) ?></textarea>
            </div>
            <div class="tab-pane fade" id="t_sart">
              <label class="form-label small text-muted">Genel şartlar ve koşullar</label>
              <textarea name="sartlar" class="form-control" rows="6"><?= e($form['sartlar']) ?></textarea>
            </div>
            <div class="tab-pane fade" id="t_odeme">
              <label class="form-label small text-muted">Ödeme koşulları</label>
              <textarea name="odeme_kosullari" class="form-control" rows="4"><?= e($form['odeme_kosullari']) ?></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sağ: Görünüm seçenekleri -->
    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-eye me-2 text-gold"></i>Görünüm Seçenekleri</h6></div>
        <div class="card-body">
          <div class="list-group list-group-flush">
            <label class="list-group-item d-flex justify-content-between align-items-center px-0">
              <div>
                <div class="fw-semibold small">Logo Göster</div>
                <div class="text-muted" style="font-size:0.72rem;">Firma logosu teklif üstünde</div>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="logo_goster" <?= ($form['logo_goster'] ?? '') ? 'checked' : '' ?>>
              </div>
            </label>
            <label class="list-group-item d-flex justify-content-between align-items-center px-0">
              <div>
                <div class="fw-semibold small">KDV Göster</div>
                <div class="text-muted" style="font-size:0.72rem;">KDV oranı ve tutarı tabloda</div>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="kdv_goster" <?= ($form['kdv_goster'] ?? '') ? 'checked' : '' ?>>
              </div>
            </label>
            <label class="list-group-item d-flex justify-content-between align-items-center px-0">
              <div>
                <div class="fw-semibold small">İskonto Göster</div>
                <div class="text-muted" style="font-size:0.72rem;">Kalem iskonto sütunu</div>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="iskonto_goster" <?= ($form['iskonto_goster'] ?? '') ? 'checked' : '' ?>>
              </div>
            </label>
            <label class="list-group-item d-flex justify-content-between align-items-center px-0">
              <div>
                <div class="fw-semibold small">Kalem Detayı Göster</div>
                <div class="text-muted" style="font-size:0.72rem;">Alt açıklama satırı</div>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="kalem_aciklama_goster" <?= ($form['kalem_aciklama_goster'] ?? '') ? 'checked' : '' ?>>
              </div>
            </label>
            <label class="list-group-item d-flex justify-content-between align-items-center px-0">
              <div>
                <div class="fw-semibold small">İmza Alanı</div>
                <div class="text-muted" style="font-size:0.72rem;">İmza & kaşe alanı</div>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="imza_alani" <?= ($form['imza_alani'] ?? '') ? 'checked' : '' ?>>
              </div>
            </label>
          </div>
        </div>
      </div>

      <!-- Önizleme renk -->
      <div class="card mb-3">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-palette me-2 text-gold"></i>Renk Önizleme</h6></div>
        <div class="card-body p-3">
          <div id="renk_onizleme" style="border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <div id="prev_header" style="padding:12px 16px;background:#0a1628;color:#fff;">
              <div style="font-size:0.7rem;letter-spacing:0.5px;opacity:0.7;">FİRMA ADI</div>
              <div id="prev_title" style="font-weight:800;font-size:0.95rem;color:#e8a000;">FİYAT TEKLİFİ</div>
            </div>
            <div style="padding:8px 12px;background:#f8fafc;font-size:0.72rem;">
              <div style="display:flex;justify-content:space-between;border-bottom:1px solid #e2e8f0;padding:3px 0;">
                <span>Ürün 1</span><span>₺ 1.000,00</span>
              </div>
            </div>
            <div id="prev_footer" style="padding:8px 12px;background:#0a1628;color:#e8a000;font-size:0.72rem;font-weight:700;text-align:right;">
              TOPLAM: ₺ 1.000,00
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><h6 class="card-title">Varsayılan</h6></div>
        <div class="card-body">
          <label class="d-flex align-items-center gap-2 cursor-pointer">
            <input type="checkbox" name="varsayilan" value="1" <?= ($form['varsayilan'] ?? '') ? 'checked' : '' ?> class="form-check-input">
            <div>
              <div class="fw-semibold small">Bu şablonu varsayılan yap</div>
              <div class="text-muted" style="font-size:0.72rem;">Yeni tekliflerde otomatik seçilir</div>
            </div>
          </label>
        </div>
      </div>

      <div class="card">
        <div class="card-body d-grid gap-2">
          <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-check2-circle me-2"></i>Şablonu Kaydet</button>
          <a href="index.php" class="btn btn-outline-secondary">İptal</a>
        </div>
      </div>
    </div>
  </div>
</form>

<?php
$extraJs = '<script>
// Renk önizlemesi
function renkGuncelle() {
  const ana = document.getElementById("renk_ana").value;
  const aks = document.getElementById("renk_aksan").value;
  document.getElementById("prev_header").style.background = ana;
  document.getElementById("prev_footer").style.background = ana;
  document.getElementById("prev_title").style.color = aks;
  document.getElementById("prev_footer").style.color = aks;
  document.getElementById("renk_ana_text").value = ana;
  document.getElementById("renk_aksan_text").value = aks;
}
document.getElementById("renk_ana").addEventListener("input", renkGuncelle);
document.getElementById("renk_aksan").addEventListener("input", renkGuncelle);
</script>';
include dirname(__DIR__) . '/includes/footer.php';
?>
