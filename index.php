<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Gösterge Paneli';
$activePage = 'dashboard';
$breadcrumb = [['label' => 'Gösterge Paneli', 'url' => 'index.php']];

$stats = dashboardIstatistikleri();

// Ay isimleri
$aylar = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="bi bi-speedometer2 me-2 text-gold"></i>Gösterge Paneli</h1>
    <div class="page-subtitle">Hoş geldiniz — <?= e(ayar('firma_adi', 'HSG Aviation')) ?> Teklif Sistemi</div>
  </div>
  <a href="<?= BASE_URL ?>/teklifler/olustur.php" class="btn btn-warning fw-bold">
    <i class="bi bi-plus-lg me-2"></i>Yeni Teklif Oluştur
  </a>
</div>

<!-- İstatistik Kartları -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon gold"><i class="bi bi-file-earmark-text"></i></div>
      <div>
        <div class="stat-value"><?= $stats['bu_ay']['sayi'] ?? 0 ?></div>
        <div class="stat-label">Bu Ay Teklif</div>
        <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> Bu ay</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
      <div>
        <div class="stat-value"><?= $stats['teklifler']['kabul']['sayi'] ?? 0 ?></div>
        <div class="stat-label">Kabul Edilen</div>
        <div class="stat-change up">%<?= $stats['basari_orani'] ?> başarı oranı</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-people"></i></div>
      <div>
        <div class="stat-value"><?= $stats['musteri_sayisi'] ?></div>
        <div class="stat-label">Aktif Müşteri</div>
        <div class="stat-change"><i class="bi bi-building me-1"></i>Kayıtlı</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon purple"><i class="bi bi-box-seam"></i></div>
      <div>
        <div class="stat-value"><?= $stats['urun_sayisi'] ?></div>
        <div class="stat-label">Katalog Ürünü</div>
        <div class="stat-change"><i class="bi bi-tag me-1"></i>Aktif</div>
      </div>
    </div>
  </div>
</div>

<!-- Durum Özeti -->
<div class="row g-3 mb-4">
  <?php
  $durumlar = [
    ['key' => 'taslak',       'label' => 'Taslak',       'color' => 'secondary', 'icon' => 'pencil-square'],
    ['key' => 'gonderildi',   'label' => 'Gönderildi',   'color' => 'primary',   'icon' => 'send'],
    ['key' => 'kabul',        'label' => 'Kabul',         'color' => 'success',   'icon' => 'check-circle'],
    ['key' => 'red',          'label' => 'Red',           'color' => 'danger',    'icon' => 'x-circle'],
    ['key' => 'suresi_doldu', 'label' => 'Süresi Doldu', 'color' => 'warning',   'icon' => 'clock-history'],
  ];
  foreach ($durumlar as $d):
    $sayi   = $stats['teklifler'][$d['key']]['sayi'] ?? 0;
    $toplam = $stats['teklifler'][$d['key']]['toplam'] ?? 0;
  ?>
  <div class="col-6 col-md-4 col-lg">
    <a href="<?= BASE_URL ?>/teklifler/index.php?durum=<?= $d['key'] ?>" class="text-decoration-none">
      <div class="card h-100 border-0" style="background:#fff;">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-<?= $d['color'] ?> fs-6"><?= $sayi ?></span>
            <i class="bi bi-<?= $d['icon'] ?> text-<?= $d['color'] ?> fs-5"></i>
          </div>
          <div class="fw-bold text-dark small"><?= $d['label'] ?></div>
          <div class="text-muted" style="font-size:0.72rem;"><?= paraFormat($toplam) ?></div>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<!-- Grafik & Son Teklifler -->
<div class="row g-3">
  <!-- Aylık Grafik -->
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0"><i class="bi bi-bar-chart me-2 text-gold"></i>Son 6 Ay</h6>
      </div>
      <div class="card-body">
        <?php if (!empty($stats['aylik'])): ?>
          <canvas id="aylikChart" height="200"></canvas>
        <?php else: ?>
          <div class="empty-state py-5">
            <div class="empty-icon"><i class="bi bi-bar-chart-line"></i></div>
            <p class="mb-0 small">Henüz yeterli veri yok</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Hızlı Erişim -->
  <div class="col-lg-2">
    <div class="card">
      <div class="card-header">
        <h6 class="card-title mb-0"><i class="bi bi-lightning me-2 text-gold"></i>Hızlı İşlem</h6>
      </div>
      <div class="card-body p-2">
        <div class="d-grid gap-2">
          <a href="<?= BASE_URL ?>/teklifler/olustur.php" class="btn btn-sm btn-warning fw-bold">
            <i class="bi bi-plus-circle me-1"></i> Yeni Teklif
          </a>
          <a href="<?= BASE_URL ?>/musteriler/ekle.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-person-plus me-1"></i> Müşteri Ekle
          </a>
          <a href="<?= BASE_URL ?>/urunler/ekle.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-box-seam me-1"></i> Ürün Ekle
          </a>
          <a href="<?= BASE_URL ?>/teklifler/index.php?durum=suresi_doldu" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-clock-history me-1"></i> Süresi Dolan
          </a>
          <a href="<?= BASE_URL ?>/sablonlar/index.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-layout-text-sidebar me-1"></i> Şablonlar
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Son Teklifler -->
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0"><i class="bi bi-clock-history me-2 text-gold"></i>Son Teklifler</h6>
        <a href="<?= BASE_URL ?>/teklifler/index.php" class="btn btn-sm btn-outline-secondary">Tümü</a>
      </div>
      <div class="card-body p-0">
        <?php if (!empty($stats['son_teklifler'])): ?>
          <div class="list-group list-group-flush">
            <?php foreach ($stats['son_teklifler'] as $t): ?>
              <a href="<?= BASE_URL ?>/teklifler/goruntule.php?id=<?= $t['id'] ?>"
                 class="list-group-item list-group-item-action px-3 py-2 border-0" style="border-bottom:1px solid #f0f4f8!important;">
                <div class="d-flex align-items-center justify-content-between gap-2">
                  <div class="d-flex align-items-center gap-2 min-w-0">
                    <div class="avatar" style="font-size:0.7rem;"><?= strtoupper(substr($t['musteri_adi'] ?? 'M', 0, 2)) ?></div>
                    <div class="min-w-0">
                      <div class="fw-semibold text-dark small text-truncate" style="max-width:120px;"><?= e($t['musteri_adi'] ?? 'Bilinmiyor') ?></div>
                      <div class="text-muted" style="font-size:0.7rem;"><?= e($t['teklif_no']) ?></div>
                    </div>
                  </div>
                  <div class="text-end flex-shrink-0">
                    <?= durumBadge($t['durum']) ?>
                    <div class="text-muted mt-1" style="font-size:0.7rem;"><?= paraFormat($t['genel_toplam'], $t['para_birimi']) ?></div>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-state py-5">
            <div class="empty-icon"><i class="bi bi-file-earmark-text"></i></div>
            <p class="mb-0 small">Henüz teklif yok</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
$aylikLabels = json_encode(array_map(function($a) use ($aylar) {
    $parts = explode('-', $a['ay']);
    return $aylar[(int)$parts[1] - 1] . ' ' . $parts[0];
}, $stats['aylik'] ?? []));
$aylikSayilar = json_encode(array_column($stats['aylik'] ?? [], 'sayi'));
$aylikToplamlar = json_encode(array_column($stats['aylik'] ?? [], 'toplam'));

$extraJs = <<<JS
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
const labels = $aylikLabels;
const sayilar = $aylikSayilar;

if (labels.length > 0) {
  new Chart(document.getElementById('aylikChart'), {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Teklif Sayısı',
        data: sayilar,
        backgroundColor: '#0a1628',
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (ctx) => ' ' + ctx.parsed.y + ' teklif'
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1 },
          grid: { color: '#f0f4f8' }
        },
        x: { grid: { display: false } }
      }
    }
  });
}
</script>
JS;
?>

<?php include __DIR__ . '/includes/footer.php'; ?>
