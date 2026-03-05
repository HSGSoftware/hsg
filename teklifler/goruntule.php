<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT t.*, m.firma_adi, m.yetkili_kisi, m.unvan, m.email as musteri_email,
    m.telefon as musteri_telefon, m.adres as musteri_adres, m.sehir, m.ulke,
    m.vergi_no, m.vergi_dairesi, m.iban, m.banka_adi
    FROM teklifler t
    LEFT JOIN musteriler m ON t.musteri_id = m.id
    WHERE t.id = ?");
$stmt->execute([$id]);
$teklif = $stmt->fetch();
if (!$teklif) { flashMesaj('danger','Teklif bulunamadı.'); header('Location: index.php'); exit; }

// Kalemler
$stmt = $pdo->prepare("SELECT * FROM teklif_kalemleri WHERE teklif_id = ? ORDER BY sira, id");
$stmt->execute([$id]);
$kalemler = $stmt->fetchAll();

// Geçmiş
$stmt = $pdo->prepare("SELECT * FROM teklif_gecmisi WHERE teklif_id = ? ORDER BY tarih DESC");
$stmt->execute([$id]);
$gecmis = $stmt->fetchAll();

$pageTitle  = $teklif['teklif_no'];
$activePage = 'teklifler';
$breadcrumb = [
    ['label' => 'Teklifler', 'url' => BASE_URL . '/teklifler/index.php'],
    ['label' => $teklif['teklif_no'], 'url' => ''],
];

$currencies = json_decode(CURRENCIES, true);
$sym = $currencies[$teklif['para_birimi']]['symbol'] ?? $teklif['para_birimi'];

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="bi bi-file-earmark-text me-2 text-gold"></i><?= e($teklif['teklif_no']) ?></h1>
    <div class="page-subtitle">
      <?= e($teklif['baslik'] ?: 'Fiyat Teklifi') ?> &nbsp;&nbsp; <?= durumBadge($teklif['durum']) ?>
    </div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="yazdir.php?id=<?= $id ?>" target="_blank" class="btn btn-success fw-bold">
      <i class="bi bi-printer me-1"></i>Yazdır / PDF
    </a>
    <a href="duzenle.php?id=<?= $id ?>" class="btn btn-outline-primary">
      <i class="bi bi-pencil me-1"></i>Düzenle
    </a>
    <a href="olustur.php?kopyala=<?= $id ?>" class="btn btn-outline-secondary">
      <i class="bi bi-copy me-1"></i>Kopyala
    </a>

    <!-- Durum Değiştir -->
    <div class="dropdown">
      <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
        <i class="bi bi-arrow-repeat me-1"></i>Durum
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow">
        <?php foreach (teklifDurumlari() as $dk => $dv): ?>
          <?php if ($dk !== $teklif['durum']): ?>
            <li>
              <a class="dropdown-item" href="index.php?durum_guncelle=<?= $dk ?>&id=<?= $id ?>">
                <?= $dv ?>
              </a>
            </li>
          <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    </div>

    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
  </div>
</div>

<div class="row g-3">
  <!-- Ana içerik -->
  <div class="col-lg-8">

    <!-- Teklif Önizleme Kutusu -->
    <div class="card mb-3" style="border: 2px solid var(--hsg-dark);">
      <!-- Teklif Başlık Alanı -->
      <div style="background: linear-gradient(135deg, var(--hsg-dark), var(--hsg-navy)); padding: 24px 28px; color: #fff; border-radius: 10px 10px 0 0;">
        <div class="row align-items-start">
          <div class="col-md-6">
            <?php $logo = ayar('firma_logo', ''); if ($logo && file_exists(BASE_PATH . '/uploads/logos/' . $logo)): ?>
              <img src="<?= BASE_URL ?>/uploads/logos/<?= e($logo) ?>" style="max-height:60px;max-width:180px;object-fit:contain;" alt="Logo">
            <?php endif; ?>
            <div class="mt-2">
              <div style="font-size:1.1rem;font-weight:800;color:var(--hsg-gold);"><?= e(ayar('firma_adi')) ?></div>
              <?php if (ayar('firma_unvan')): ?>
                <div style="font-size:0.78rem;opacity:0.8;"><?= e(ayar('firma_unvan')) ?></div>
              <?php endif; ?>
              <?php if (ayar('firma_adres')): ?>
                <div style="font-size:0.72rem;opacity:0.7;margin-top:4px;"><?= e(ayar('firma_adres')) ?></div>
              <?php endif; ?>
              <?php if (ayar('firma_telefon')): ?>
                <div style="font-size:0.72rem;opacity:0.7;">Tel: <?= e(ayar('firma_telefon')) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <div style="color:var(--hsg-gold);font-size:1.3rem;font-weight:800;letter-spacing:-0.5px;">FİYAT TEKLİFİ</div>
            <div style="font-size:1.1rem;font-weight:700;"><?= e($teklif['teklif_no']) ?></div>
            <div class="mt-2 small">
              <div>Tarih: <?= tarihFormat($teklif['tarih']) ?></div>
              <div>Geçerlilik: <?= tarihFormat($teklif['gecerlilik_tarihi']) ?></div>
              <div>Para Birimi: <?= e($teklif['para_birimi']) ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Müşteri Bilgisi -->
      <div class="card-body">
        <?php if ($teklif['on_yazi']): ?>
          <div class="mb-3 p-3 rounded" style="background:#f8fafc;font-size:0.875rem;white-space:pre-line;"><?= e($teklif['on_yazi']) ?></div>
        <?php endif; ?>

        <div class="row mb-3">
          <div class="col-md-6">
            <div class="fw-bold text-muted small text-uppercase mb-1">Müşteri / Alıcı</div>
            <?php if ($teklif['firma_adi']): ?>
              <div class="fw-bold fs-6"><?= e($teklif['firma_adi']) ?></div>
              <?php if ($teklif['yetkili_kisi']): ?>
                <div class="text-muted small">Sayın <?= e($teklif['yetkili_kisi']) ?><?= $teklif['unvan'] ? ', ' . e($teklif['unvan']) : '' ?></div>
              <?php endif; ?>
              <?php if ($teklif['musteri_email']): ?>
                <div class="small"><?= e($teklif['musteri_email']) ?></div>
              <?php endif; ?>
              <?php if ($teklif['musteri_telefon']): ?>
                <div class="small"><?= e($teklif['musteri_telefon']) ?></div>
              <?php endif; ?>
              <?php if ($teklif['musteri_adres']): ?>
                <div class="small text-muted"><?= nl2br(e($teklif['musteri_adres'])) ?></div>
              <?php endif; ?>
              <?php if ($teklif['vergi_no']): ?>
                <div class="small text-muted">VKN: <?= e($teklif['vergi_no']) ?> / <?= e($teklif['vergi_dairesi']) ?></div>
              <?php endif; ?>
            <?php else: ?>
              <span class="text-muted">Müşteri seçilmemiş</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Kalemler -->
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0">
            <thead>
              <tr style="background:var(--hsg-dark); color:rgba(255,255,255,0.9); font-size:0.78rem;">
                <th width="30">#</th>
                <th>Ürün / Hizmet</th>
                <th class="text-center" width="70">Miktar</th>
                <th class="text-center" width="70">Birim</th>
                <th class="text-end" width="120">Birim Fiyat</th>
                <th class="text-center" width="60">İsk %</th>
                <th class="text-center" width="60">KDV %</th>
                <th class="text-end" width="130">Toplam</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($kalemler as $i => $k): ?>
              <tr>
                <td class="text-muted text-center small"><?= $i + 1 ?></td>
                <td>
                  <div class="fw-semibold"><?= e($k['aciklama']) ?></div>
                  <?php if ($k['detay']): ?>
                    <div class="text-muted" style="font-size:0.75rem;"><?= nl2br(e($k['detay'])) ?></div>
                  <?php endif; ?>
                </td>
                <td class="text-center"><?= number_format($k['miktar'], 2, ',', '.') ?></td>
                <td class="text-center"><?= e($k['birim']) ?></td>
                <td class="text-end"><?= $sym ?> <?= number_format($k['birim_fiyat'], 2, ',', '.') ?></td>
                <td class="text-center">%<?= number_format($k['iskonto'], 0) ?></td>
                <td class="text-center">%<?= number_format($k['kdv_orani'], 0) ?></td>
                <td class="text-end fw-bold"><?= $sym ?> <?= number_format($k['toplam'], 2, ',', '.') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot style="background:#f8fafc;">
              <?php if ($teklif['iskonto_tutari'] > 0): ?>
              <tr>
                <td colspan="6"></td>
                <td class="text-end text-muted small">Ara Toplam:</td>
                <td class="text-end"><?= $sym ?> <?= number_format($teklif['ara_toplam'], 2, ',', '.') ?></td>
              </tr>
              <tr>
                <td colspan="6"></td>
                <td class="text-end text-danger small">İskonto:</td>
                <td class="text-end text-danger">- <?= $sym ?> <?= number_format($teklif['iskonto_tutari'], 2, ',', '.') ?></td>
              </tr>
              <?php endif; ?>
              <tr>
                <td colspan="6"></td>
                <td class="text-end text-muted small">KDV:</td>
                <td class="text-end"><?= $sym ?> <?= number_format($teklif['kdv_tutari'], 2, ',', '.') ?></td>
              </tr>
              <tr style="background:var(--hsg-dark); color:var(--hsg-gold);">
                <td colspan="6"></td>
                <td class="text-end fw-bold">GENEL TOPLAM:</td>
                <td class="text-end fw-bold fs-6"><?= $sym ?> <?= number_format($teklif['genel_toplam'], 2, ',', '.') ?></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <?php if ($teklif['son_yazi']): ?>
          <div class="mt-3 p-3 rounded" style="background:#f8fafc;font-size:0.875rem;white-space:pre-line;"><?= e($teklif['son_yazi']) ?></div>
        <?php endif; ?>

        <?php if ($teklif['sartlar']): ?>
          <div class="mt-3">
            <div class="fw-bold small text-uppercase text-muted mb-1">Şartlar ve Koşullar</div>
            <div style="font-size:0.8rem;white-space:pre-line;color:#555;"><?= e($teklif['sartlar']) ?></div>
          </div>
        <?php endif; ?>

        <?php if ($teklif['odeme_kosullari']): ?>
          <div class="mt-2">
            <div class="fw-bold small text-uppercase text-muted mb-1">Ödeme Koşulları</div>
            <div style="font-size:0.8rem;"><?= e($teklif['odeme_kosullari']) ?></div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- İç Notlar -->
    <?php if ($teklif['ic_notlar']): ?>
    <div class="card mb-3">
      <div class="card-header"><h6 class="card-title"><i class="bi bi-sticky me-2 text-gold"></i>İç Notlar (Müşteriye Gösterilmez)</h6></div>
      <div class="card-body"><p class="mb-0"><?= nl2br(e($teklif['ic_notlar'])) ?></p></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Sağ panel -->
  <div class="col-lg-4">
    <!-- Finansal Özet -->
    <div class="card mb-3">
      <div class="card-header" style="background:var(--hsg-dark);color:#fff;">
        <h6 class="mb-0 text-gold"><i class="bi bi-calculator me-2"></i>Finansal Özet</h6>
      </div>
      <div class="card-body p-3">
        <div class="d-flex justify-content-between py-1 border-bottom small">
          <span class="text-muted">Para Birimi</span><span class="fw-semibold"><?= e($teklif['para_birimi']) ?></span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom small">
          <span class="text-muted">Ara Toplam</span><span><?= $sym ?> <?= number_format($teklif['ara_toplam'], 2, ',', '.') ?></span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom small">
          <span class="text-muted">Toplam İskonto</span>
          <span class="text-danger">- <?= $sym ?> <?= number_format($teklif['iskonto_tutari'], 2, ',', '.') ?></span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom small">
          <span class="text-muted">KDV Toplam</span><span><?= $sym ?> <?= number_format($teklif['kdv_tutari'], 2, ',', '.') ?></span>
        </div>
        <div class="d-flex justify-content-between py-2 fw-bold" style="font-size:1.1rem;">
          <span style="color:var(--hsg-dark);">TOPLAM</span>
          <span style="color:var(--hsg-gold);"><?= $sym ?> <?= number_format($teklif['genel_toplam'], 2, ',', '.') ?></span>
        </div>
      </div>
    </div>

    <!-- Teklif Bilgileri -->
    <div class="card mb-3">
      <div class="card-header"><h6 class="card-title"><i class="bi bi-info-circle me-2 text-gold"></i>Teklif Bilgileri</h6></div>
      <div class="card-body p-3">
        <table class="table table-sm table-borderless mb-0">
          <tr><td class="text-muted small">No</td><td class="fw-bold"><code><?= e($teklif['teklif_no']) ?></code></td></tr>
          <tr><td class="text-muted small">Durum</td><td><?= durumBadge($teklif['durum']) ?></td></tr>
          <tr><td class="text-muted small">Tarih</td><td><?= tarihFormat($teklif['tarih']) ?></td></tr>
          <tr><td class="text-muted small">Geçerlilik</td><td><?= tarihFormat($teklif['gecerlilik_tarihi']) ?></td></tr>
          <?php if ($teklif['gonderim_tarihi']): ?>
          <tr><td class="text-muted small">Gönderildi</td><td><?= tarihFormat($teklif['gonderim_tarihi'], 'd.m.Y H:i') ?></td></tr>
          <?php endif; ?>
          <?php if ($teklif['kabul_tarihi']): ?>
          <tr><td class="text-muted small">Kabul</td><td><?= tarihFormat($teklif['kabul_tarihi'], 'd.m.Y H:i') ?></td></tr>
          <?php endif; ?>
          <tr><td class="text-muted small">Oluşturma</td><td><?= tarihFormat($teklif['olusturma_tarihi'], 'd.m.Y H:i') ?></td></tr>
        </table>
      </div>
    </div>

    <!-- Hızlı Durum Güncelle -->
    <div class="card mb-3">
      <div class="card-header"><h6 class="card-title"><i class="bi bi-arrow-repeat me-2 text-gold"></i>Durum Güncelle</h6></div>
      <div class="card-body p-3">
        <div class="d-grid gap-2">
          <?php foreach (teklifDurumlari() as $dk => $dv): ?>
            <?php if ($dk !== $teklif['durum']): ?>
              <a href="index.php?durum_guncelle=<?= $dk ?>&id=<?= $id ?>"
                 class="btn btn-sm btn-outline-secondary text-start">
                 <?= $dv ?>
              </a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Geçmiş -->
    <div class="card">
      <div class="card-header"><h6 class="card-title"><i class="bi bi-clock-history me-2 text-gold"></i>Geçmiş</h6></div>
      <div class="card-body p-3">
        <?php if (empty($gecmis)): ?>
          <p class="text-muted small mb-0">Geçmiş kaydı yok</p>
        <?php else: ?>
          <div class="timeline">
            <?php foreach ($gecmis as $g): ?>
              <div class="d-flex gap-2 mb-2 small">
                <div class="text-muted" style="min-width:80px;"><?= date('d.m.Y', strtotime($g['tarih'])) ?></div>
                <div>
                  <?php if ($g['eski_durum']): ?>
                    <?= durumBadge($g['eski_durum']) ?>
                    <i class="bi bi-arrow-right mx-1"></i>
                  <?php endif; ?>
                  <?= durumBadge($g['yeni_durum']) ?>
                  <?php if ($g['notlar']): ?><div class="text-muted"><?= e($g['notlar']) ?></div><?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
