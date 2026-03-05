<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

suresiDolanlariGuncelle();

$pageTitle  = 'Teklifler';
$activePage = 'teklifler';
$breadcrumb = [
    ['label' => 'Ana Sayfa', 'url' => BASE_URL . '/index.php'],
    ['label' => 'Teklifler', 'url' => ''],
];

// Sil
if (isset($_GET['sil'])) {
    $pdo->prepare("DELETE FROM teklifler WHERE id = ?")->execute([(int)$_GET['sil']]);
    flashMesaj('success', 'Teklif silindi.');
    header('Location: index.php'); exit;
}

// Durum güncelle
if (isset($_GET['durum_guncelle']) && isset($_GET['id'])) {
    $yeniDurum = $_GET['durum_guncelle'];
    $gecerliDurumlar = ['taslak','gonderildi','kabul','red','suresi_doldu'];
    if (in_array($yeniDurum, $gecerliDurumlar)) {
        $stmt = $pdo->prepare("SELECT durum FROM teklifler WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        $eskiDurum = $stmt->fetchColumn();

        $pdo->prepare("UPDATE teklifler SET durum = ?, " .
            ($yeniDurum === 'gonderildi' ? 'gonderim_tarihi = NOW(),' : '') .
            ($yeniDurum === 'kabul' ? 'kabul_tarihi = NOW(),' : '') .
            "guncelleme_tarihi = NOW() WHERE id = ?")->execute([$yeniDurum, (int)$_GET['id']]);

        // Geçmiş kaydı
        $pdo->prepare("INSERT INTO teklif_gecmisi (teklif_id, eski_durum, yeni_durum) VALUES (?,?,?)")
            ->execute([(int)$_GET['id'], $eskiDurum, $yeniDurum]);

        flashMesaj('success', 'Teklif durumu güncellendi.');
    }
    header('Location: index.php'); exit;
}

// Filtreler
$ara      = trim($_GET['ara'] ?? '');
$durum    = $_GET['durum'] ?? '';
$musteri  = (int)($_GET['musteri'] ?? 0);
$tarih_bas = $_GET['tarih_bas'] ?? '';
$tarih_bit = $_GET['tarih_bit'] ?? '';
$sayfa    = max(1, (int)($_GET['sayfa'] ?? 1));
$limit    = 20;
$offset   = ($sayfa - 1) * $limit;

$where  = ['1=1'];
$params = [];

if ($ara) {
    $where[] = "(t.teklif_no LIKE ? OR t.baslik LIKE ? OR m.firma_adi LIKE ?)";
    $a = "%$ara%"; $params = array_merge($params, [$a, $a, $a]);
}
if ($durum)   { $where[] = "t.durum = ?";       $params[] = $durum; }
if ($musteri) { $where[] = "t.musteri_id = ?";  $params[] = $musteri; }
if ($tarih_bas) { $where[] = "t.tarih >= ?";    $params[] = $tarih_bas; }
if ($tarih_bit) { $where[] = "t.tarih <= ?";    $params[] = $tarih_bit; }

$whereStr = implode(' AND ', $where);

$toplamStmt = $pdo->prepare("SELECT COUNT(*) FROM teklifler t LEFT JOIN musteriler m ON t.musteri_id = m.id WHERE $whereStr");
$toplamStmt->execute($params);
$toplam = $toplamStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT t.*, m.firma_adi as musteri_adi
    FROM teklifler t
    LEFT JOIN musteriler m ON t.musteri_id = m.id
    WHERE $whereStr
    ORDER BY t.olusturma_tarihi DESC
    LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$teklifler = $stmt->fetchAll();

$tumMusteriler = $pdo->query("SELECT id, firma_adi FROM musteriler ORDER BY firma_adi")->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="bi bi-file-earmark-text me-2 text-gold"></i>Teklifler</h1>
    <div class="page-subtitle"><?= $toplam ?> teklif</div>
  </div>
  <a href="olustur.php" class="btn btn-warning fw-bold">
    <i class="bi bi-plus-circle me-2"></i>Yeni Teklif
  </a>
</div>

<!-- Filtreler -->
<div class="card mb-3">
  <div class="card-body py-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-3">
        <div class="search-box">
          <i class="bi bi-search"></i>
          <input type="text" name="ara" class="form-control" placeholder="Teklif no, başlık, müşteri..." value="<?= e($ara) ?>">
        </div>
      </div>
      <div class="col-md-2">
        <select name="durum" class="form-select">
          <option value="">Tüm Durumlar</option>
          <?php foreach (teklifDurumlari() as $k => $v): ?>
            <option value="<?= $k ?>" <?= $durum === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="musteri" class="form-select">
          <option value="">Tüm Müşteriler</option>
          <?php foreach ($tumMusteriler as $m): ?>
            <option value="<?= $m['id'] ?>" <?= $musteri === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['firma_adi']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" name="tarih_bas" class="form-control" value="<?= e($tarih_bas) ?>" placeholder="Başlangıç">
      </div>
      <div class="col-md-2">
        <input type="date" name="tarih_bit" class="form-control" value="<?= e($tarih_bit) ?>" placeholder="Bitiş">
      </div>
      <div class="col-md-1">
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i></button>
          <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Tablo -->
<div class="card">
  <div class="card-body p-0">
    <?php if (empty($teklifler)): ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-file-earmark-text"></i></div>
        <h5>Teklif bulunamadı</h5>
        <a href="olustur.php" class="btn btn-warning mt-2"><i class="bi bi-plus me-2"></i>İlk Teklifi Oluştur</a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr>
            <th width="130">Teklif No</th>
            <th>Müşteri</th>
            <th class="d-none d-md-table-cell">Başlık</th>
            <th>Tarih</th>
            <th class="d-none d-lg-table-cell">Geçerlilik</th>
            <th class="d-none d-md-table-cell">Para Birimi</th>
            <th>Toplam</th>
            <th>Durum</th>
            <th width="150">İşlemler</th>
          </tr></thead>
          <tbody>
            <?php foreach ($teklifler as $t): ?>
            <tr>
              <td>
                <a href="goruntule.php?id=<?= $t['id'] ?>" class="fw-bold text-decoration-none text-hsg">
                  <?= e($t['teklif_no']) ?>
                </a>
              </td>
              <td>
                <?php if ($t['musteri_adi']): ?>
                  <div class="d-flex align-items-center gap-2">
                    <div class="avatar" style="font-size:0.7rem;"><?= strtoupper(substr($t['musteri_adi'], 0, 2)) ?></div>
                    <a href="<?= BASE_URL ?>/musteriler/goruntule.php?id=<?= $t['musteri_id'] ?>" class="text-decoration-none text-dark">
                      <?= e($t['musteri_adi']) ?>
                    </a>
                  </div>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td class="d-none d-md-table-cell"><?= e($t['baslik'] ?: 'Fiyat Teklifi') ?></td>
              <td><?= tarihFormat($t['tarih']) ?></td>
              <td class="d-none d-lg-table-cell">
                <?php
                $bugün = new DateTime();
                $gec   = new DateTime($t['gecerlilik_tarihi'] ?: $t['tarih']);
                $kalanGun = $bugün->diff($gec)->days * ($bugün > $gec ? -1 : 1);
                ?>
                <?= tarihFormat($t['gecerlilik_tarihi']) ?>
                <?php if ($t['durum'] === 'gonderildi' && $kalanGun <= 7 && $kalanGun >= 0): ?>
                  <span class="badge bg-warning ms-1" style="font-size:0.65rem;"><?= $kalanGun ?>g kaldı</span>
                <?php endif; ?>
              </td>
              <td class="d-none d-md-table-cell"><?= e($t['para_birimi']) ?></td>
              <td class="fw-bold"><?= paraFormat($t['genel_toplam'], $t['para_birimi']) ?></td>
              <td><?= durumBadge($t['durum']) ?></td>
              <td>
                <div class="d-flex gap-1 flex-wrap">
                  <a href="goruntule.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-secondary action-btn" title="Görüntüle">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="duzenle.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary action-btn" title="Düzenle">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a href="yazdir.php?id=<?= $t['id'] ?>" target="_blank" class="btn btn-sm btn-outline-success action-btn" title="Yazdır/PDF">
                    <i class="bi bi-printer"></i>
                  </a>
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary action-btn dropdown-toggle" data-bs-toggle="dropdown" title="Durum Değiştir">
                      <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                      <li><h6 class="dropdown-header">Durum Değiştir</h6></li>
                      <?php foreach (teklifDurumlari() as $dk => $dv): ?>
                        <?php if ($dk !== $t['durum']): ?>
                        <li>
                          <a class="dropdown-item" href="index.php?durum_guncelle=<?= $dk ?>&id=<?= $t['id'] ?>">
                            <?= $dv ?>
                          </a>
                        </li>
                        <?php endif; ?>
                      <?php endforeach; ?>
                      <li><hr class="dropdown-divider"></li>
                      <li>
                        <a class="dropdown-item" href="olustur.php?kopyala=<?= $t['id'] ?>">
                          <i class="bi bi-copy me-2"></i>Kopyala
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item text-danger" onclick="onayIste('Bu teklifi silmek istediğinize emin misiniz?','index.php?sil=<?= $t['id'] ?>')" href="#">
                          <i class="bi bi-trash me-2"></i>Sil
                        </a>
                      </li>
                    </ul>
                  </div>
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
          <small class="text-muted"><?= $offset+1 ?>–<?= min($offset+$limit,$toplam) ?> / <?= $toplam ?></small>
          <?= sayfalama($toplam, $limit, $sayfa, '?ara='.urlencode($ara).'&durum='.urlencode($durum).'&musteri='.$musteri) ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
