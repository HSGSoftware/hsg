<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle  = 'Yeni Teklif Oluştur';
$activePage = 'teklif_olustur';
$breadcrumb = [
    ['label' => 'Teklifler', 'url' => BASE_URL . '/teklifler/index.php'],
    ['label' => 'Yeni Teklif', 'url' => ''],
];

// Kopyalama modunu kontrol et
$kopyalaId = (int)($_GET['kopyala'] ?? 0);
$kopyaData = null;
$kopyaKalemler = [];

if ($kopyalaId) {
    $stmt = $pdo->prepare("SELECT * FROM teklifler WHERE id = ?");
    $stmt->execute([$kopyalaId]);
    $kopyaData = $stmt->fetch();
    if ($kopyaData) {
        $stmt = $pdo->prepare("SELECT * FROM teklif_kalemleri WHERE teklif_id = ? ORDER BY sira");
        $stmt->execute([$kopyalaId]);
        $kopyaKalemler = $stmt->fetchAll();
    }
}

// Veri yükle
$musteriler  = $pdo->query("SELECT id, musteri_no, firma_adi, yetkili_kisi, email, telefon, adres, sehir, ulke, vergi_no, vergi_dairesi FROM musteriler WHERE durum='aktif' ORDER BY firma_adi")->fetchAll();
$sablonlar   = $pdo->query("SELECT * FROM sablonlar ORDER BY varsayilan DESC, ad")->fetchAll();
$currencies  = json_decode(CURRENCIES, true);
$kdvOranlari = json_decode(KDV_ORANLARI, true);

// Varsayılan şablon
$varsayilanSablon = null;
foreach ($sablonlar as $s) {
    if ($s['varsayilan']) { $varsayilanSablon = $s; break; }
}
if (!$varsayilanSablon && !empty($sablonlar)) $varsayilanSablon = $sablonlar[0];

// URL parametresi müşteri seçimi
$seciliMusteriId = (int)($_GET['musteri_id'] ?? 0);

// Bugün ve geçerlilik tarihi
$bugün = date('Y-m-d');
$gecerlilikGun = (int)ayar('gecerlilik_gun', '30');
$gecerlilikTarihi = date('Y-m-d', strtotime("+$gecerlilikGun days"));

$hatalar = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $musteriId  = (int)($_POST['musteri_id'] ?? 0);
    $sablonId   = (int)($_POST['sablon_id'] ?? 0) ?: null;
    $baslik     = trim($_POST['baslik'] ?? '');
    $tarih      = $_POST['tarih'] ?? $bugün;
    $gecGecerlTarih = $_POST['gecerlilik_tarihi'] ?? $gecerlilikTarihi;
    $paraBirimi = $_POST['para_birimi'] ?? 'TRY';
    $genelIskonto = (float)str_replace(',', '.', $_POST['genel_iskonto'] ?? '0');
    $on_yazi    = $_POST['on_yazi'] ?? '';
    $son_yazi   = $_POST['son_yazi'] ?? '';
    $sartlar    = $_POST['sartlar'] ?? '';
    $odeme      = $_POST['odeme_kosullari'] ?? '';
    $ic_notlar  = $_POST['ic_notlar'] ?? '';
    $kalemler   = $_POST['kalemler'] ?? [];

    // Validasyon
    if (!$musteriId) $hatalar[] = 'Müşteri seçimi zorunludur.';
    if (empty($kalemler)) $hatalar[] = 'En az bir kalem eklemelisiniz.';

    if (empty($hatalar)) {
        $teklif_no = teklifNoOlustur();

        // Toplamları hesapla
        $araToplam = 0;
        $iskonto_t = 0;
        $kdv_t     = 0;

        $islenenKalemler = [];
        foreach ($kalemler as $k) {
            if (empty(trim($k['aciklama'] ?? ''))) continue;
            $miktar     = max(0, (float)str_replace(',', '.', $k['miktar'] ?? '1'));
            $birimFiyat = max(0, (float)str_replace(',', '.', $k['birim_fiyat'] ?? '0'));
            $iskonto    = max(0, min(100, (float)str_replace(',', '.', $k['iskonto'] ?? '0')));
            $kdvOrani   = max(0, (float)str_replace(',', '.', $k['kdv_orani'] ?? '18'));

            $satirAra     = $miktar * $birimFiyat;
            $satirIskonto = $satirAra * ($iskonto / 100);
            $satirNet     = $satirAra - $satirIskonto;
            $satirKdv     = $satirNet * ($kdvOrani / 100);
            $satirToplam  = $satirNet + $satirKdv;

            $araToplam += $satirAra;
            $iskonto_t += $satirIskonto;
            $kdv_t     += $satirKdv;

            $islenenKalemler[] = [
                'urun_id'     => (int)($k['urun_id'] ?? 0) ?: null,
                'sira'        => (int)($k['sira'] ?? 0),
                'aciklama'    => trim($k['aciklama']),
                'detay'       => trim($k['detay'] ?? ''),
                'miktar'      => $miktar,
                'birim'       => trim($k['birim'] ?? 'Adet'),
                'birim_fiyat' => $birimFiyat,
                'iskonto'     => $iskonto,
                'kdv_orani'   => $kdvOrani,
                'ara_toplam'  => $satirAra,
                'iskonto_tutari' => $satirIskonto,
                'kdv_tutari'  => $satirKdv,
                'toplam'      => $satirToplam,
            ];
        }

        // Genel iskonto uygula
        $genelIskonto_t = ($araToplam - $iskonto_t) * ($genelIskonto / 100);
        $genelToplam = ($araToplam - $iskonto_t - $genelIskonto_t) + $kdv_t;

        // Teklif kaydı
        $stmt = $pdo->prepare("INSERT INTO teklifler
            (teklif_no, musteri_id, sablon_id, baslik, tarih, gecerlilik_tarihi,
             para_birimi, genel_iskonto, ara_toplam, iskonto_tutari, kdv_tutari, genel_toplam,
             on_yazi, son_yazi, sartlar, odeme_kosullari, ic_notlar, durum)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'taslak')");

        $stmt->execute([
            $teklif_no, $musteriId, $sablonId, $baslik, $tarih, $gecGecerlTarih,
            $paraBirimi, $genelIskonto,
            $araToplam, $iskonto_t + $genelIskonto_t, $kdv_t, $genelToplam,
            $on_yazi, $son_yazi, $sartlar, $odeme, $ic_notlar
        ]);

        $teklifId = $pdo->lastInsertId();

        // Kalemleri kaydet
        $kalemStmt = $pdo->prepare("INSERT INTO teklif_kalemleri
            (teklif_id, urun_id, sira, aciklama, detay, miktar, birim, birim_fiyat,
             iskonto, kdv_orani, ara_toplam, iskonto_tutari, kdv_tutari, toplam)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        foreach ($islenenKalemler as $k) {
            $kalemStmt->execute([
                $teklifId, $k['urun_id'], $k['sira'], $k['aciklama'], $k['detay'],
                $k['miktar'], $k['birim'], $k['birim_fiyat'],
                $k['iskonto'], $k['kdv_orani'], $k['ara_toplam'],
                $k['iskonto_tutari'], $k['kdv_tutari'], $k['toplam']
            ]);
        }

        // Geçmiş
        $pdo->prepare("INSERT INTO teklif_gecmisi (teklif_id, yeni_durum) VALUES (?,?)")
            ->execute([$teklifId, 'taslak']);

        flashMesaj('success', "Teklif oluşturuldu: $teklif_no");
        header("Location: goruntule.php?id=$teklifId");
        exit;
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="bi bi-file-earmark-plus me-2 text-gold"></i>Yeni Teklif Oluştur</h1>
    <div class="page-subtitle">Müşteri seçin, kalemler ekleyin ve teklifi kaydedin</div>
  </div>
  <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Geri</a>
</div>

<?php if (!empty($hatalar)): ?>
  <div class="alert alert-danger alert-permanent">
    <?php foreach ($hatalar as $h): ?><div><?= e($h) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<form method="POST" id="teklifForm">
  <div class="row g-3">

    <!-- SOL: Genel Bilgiler + Şablon + Kalemler -->
    <div class="col-lg-8">

      <!-- Müşteri & Şablon -->
      <div class="card mb-3">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-person-lines-fill me-2 text-gold"></i>Müşteri & Şablon</h6></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Müşteri <span class="text-danger">*</span></label>
              <select name="musteri_id" id="musteri_select" class="form-select" required>
                <option value="">— Müşteri Seçin —</option>
                <?php foreach ($musteriler as $m): ?>
                  <option value="<?= $m['id'] ?>"
                    data-yetkili="<?= e($m['yetkili_kisi']) ?>"
                    data-email="<?= e($m['email']) ?>"
                    data-telefon="<?= e($m['telefon']) ?>"
                    data-adres="<?= e($m['adres'] . ($m['sehir'] ? ', ' . $m['sehir'] : '')) ?>"
                    data-ulke="<?= e($m['ulke']) ?>"
                    data-vkn="<?= e($m['vergi_no']) ?>"
                    data-vdairesi="<?= e($m['vergi_dairesi']) ?>"
                    <?= ($kopyaData['musteri_id'] ?? $seciliMusteriId) == $m['id'] ? 'selected' : '' ?>>
                    <?= e($m['firma_adi']) ?> <?= $m['musteri_no'] ? '— ' . $m['musteri_no'] : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <a href="<?= BASE_URL ?>/musteriler/ekle.php" target="_blank" class="text-decoration-none small mt-1 d-inline-block">
                <i class="bi bi-plus-circle me-1"></i>Yeni Müşteri Ekle
              </a>
            </div>
            <div class="col-md-4">
              <label class="form-label">Teklif Şablonu</label>
              <select name="sablon_id" id="sablon_select" class="form-select">
                <option value="">— Şablonsuz —</option>
                <?php foreach ($sablonlar as $s): ?>
                  <option value="<?= $s['id'] ?>"
                    data-on="<?= e($s['on_yazi']) ?>"
                    data-son="<?= e($s['son_yazi']) ?>"
                    data-sartlar="<?= e($s['sartlar']) ?>"
                    data-odeme="<?= e($s['odeme_kosullari']) ?>"
                    data-gecerlilik="<?= $s['gecerlilik_gun'] ?>"
                    <?= ($varsayilanSablon && $s['id'] == $varsayilanSablon['id']) ? 'selected' : '' ?>>
                    <?= e($s['ad']) ?><?= $s['varsayilan'] ? ' ★' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Müşteri özet bilgisi -->
            <div class="col-12" id="musteri_ozet" style="display:none;">
              <div class="p-3 rounded-3 border" style="background:#f8fafc;">
                <div class="row g-2 small text-muted" id="musteri_detay"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Teklif Detayları -->
      <div class="card mb-3">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-info-circle me-2 text-gold"></i>Teklif Bilgileri</h6></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Teklif Başlığı</label>
              <input type="text" name="baslik" class="form-control" value="<?= e($kopyaData['baslik'] ?? '') ?>" placeholder="Fiyat Teklifi (boş bırakılabilir)">
            </div>
            <div class="col-md-4">
              <label class="form-label">Para Birimi</label>
              <select name="para_birimi" id="para_birimi" class="form-select">
                <?php foreach ($currencies as $code => $c): ?>
                  <option value="<?= $code ?>" data-sym="<?= $c['symbol'] ?>"
                    <?= ($kopyaData['para_birimi'] ?? ayar('varsayilan_para_birimi', 'TRY')) === $code ? 'selected' : '' ?>>
                    <?= $code ?> — <?= $c['name'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Teklif Tarihi</label>
              <input type="date" name="tarih" class="form-control" value="<?= $kopyaData['tarih'] ?? $bugün ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Geçerlilik Tarihi</label>
              <input type="date" name="gecerlilik_tarihi" id="gecerlilik_tarihi" class="form-control"
                     value="<?= $kopyaData['gecerlilik_tarihi'] ?? $gecerlilikTarihi ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Genel İskonto (%)</label>
              <div class="input-group">
                <input type="number" name="genel_iskonto" id="genel_iskonto" class="form-control"
                       value="<?= $kopyaData['genel_iskonto'] ?? '0' ?>" min="0" max="100" step="0.01">
                <span class="input-group-text">%</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Ürün Arama Aracı -->
      <div class="card mb-2" style="border: 2px dashed var(--hsg-gold); background: var(--hsg-gold-pale);">
        <div class="card-body py-2 px-3">
          <div class="row g-2 align-items-center">
            <div class="col">
              <div class="search-box">
                <i class="bi bi-search" style="color:var(--hsg-gold)"></i>
                <input type="text" id="urun_ara_input" class="form-control border-0 bg-transparent"
                       placeholder="Katalogdan ürün ara... (kod, ad veya açıklama)" autocomplete="off">
              </div>
              <div id="urun_ara_sonuclar" class="position-absolute bg-white border rounded shadow-lg"
                   style="z-index:9999;max-height:280px;overflow-y:auto;display:none;min-width:400px;"></div>
            </div>
            <div class="col-auto">
              <button type="button" onclick="manuelKalemEkle()" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Manuel Ekle
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Kalemler Tablosu -->
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="card-title mb-0"><i class="bi bi-list-ul me-2 text-gold"></i>Teklif Kalemleri</h6>
          <small class="text-muted">Sıralamak için sürükleyip bırakın</small>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table mb-0" id="kalemler_table">
              <thead>
                <tr style="background:var(--hsg-dark); color:rgba(255,255,255,0.85); font-size:0.75rem;">
                  <th width="30" class="text-center">☰</th>
                  <th>Ürün / Hizmet Açıklaması</th>
                  <th width="80">Miktar</th>
                  <th width="80">Birim</th>
                  <th width="130">Birim Fiyat</th>
                  <th width="70">İsk %</th>
                  <th width="70">KDV %</th>
                  <th width="130" class="text-end">Toplam</th>
                  <th width="40"></th>
                </tr>
              </thead>
              <tbody id="kalemler_body">
                <!-- Kalemler JS ile eklenecek -->
              </tbody>
              <tfoot id="kalemler_foot">
                <tr style="background:#f8fafc;">
                  <td colspan="9">
                    <button type="button" onclick="manuelKalemEkle()" class="btn btn-sm btn-outline-secondary">
                      <i class="bi bi-plus me-1"></i>Satır Ekle
                    </button>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <!-- Ön Yazı / Son Yazı / Şartlar -->
      <div class="card">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-file-text me-2 text-gold"></i>Metin & Koşullar</h6></div>
        <div class="card-body">
          <ul class="nav nav-pills mb-3" id="metinTabs">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab_on">Ön Yazı</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab_son">Son Yazı</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab_sartlar">Şartlar</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab_odeme">Ödeme</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab_notlar">İç Notlar</button></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="tab_on">
              <textarea name="on_yazi" id="on_yazi" class="form-control" rows="4" placeholder="Teklif başında gösterilecek yazı..."><?= e($kopyaData['on_yazi'] ?? ($varsayilanSablon['on_yazi'] ?? '')) ?></textarea>
            </div>
            <div class="tab-pane fade" id="tab_son">
              <textarea name="son_yazi" id="son_yazi" class="form-control" rows="4" placeholder="Teklif sonunda gösterilecek yazı..."><?= e($kopyaData['son_yazi'] ?? ($varsayilanSablon['son_yazi'] ?? '')) ?></textarea>
            </div>
            <div class="tab-pane fade" id="tab_sartlar">
              <textarea name="sartlar" id="sartlar" class="form-control" rows="5" placeholder="Genel şartlar ve koşullar..."><?= e($kopyaData['sartlar'] ?? ($varsayilanSablon['sartlar'] ?? '')) ?></textarea>
            </div>
            <div class="tab-pane fade" id="tab_odeme">
              <textarea name="odeme_kosullari" id="odeme_kosullari" class="form-control" rows="3" placeholder="Ödeme koşulları..."><?= e($kopyaData['odeme_kosullari'] ?? ($varsayilanSablon['odeme_kosullari'] ?? '')) ?></textarea>
            </div>
            <div class="tab-pane fade" id="tab_notlar">
              <textarea name="ic_notlar" class="form-control" rows="3" placeholder="İç notlar (müşteriye gösterilmez)..."><?= e($kopyaData['ic_notlar'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SAĞ: Özet -->
    <div class="col-lg-4">
      <div class="card mb-3" style="position:sticky;top:80px;">
        <div class="card-header" style="background:var(--hsg-dark);color:#fff;">
          <h6 class="mb-0 text-gold"><i class="bi bi-calculator me-2"></i>Teklif Özeti</h6>
        </div>
        <div class="card-body">
          <div class="d-flex justify-content-between py-1 border-bottom small">
            <span class="text-muted">Ara Toplam</span>
            <span id="sum_ara" class="fw-semibold">₺ 0,00</span>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom small">
            <span class="text-muted">Kalem İskontoları</span>
            <span id="sum_kalem_isk" class="text-danger">- ₺ 0,00</span>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom small">
            <span class="text-muted">Genel İskonto (%<span id="sum_gen_isk_pct">0</span>)</span>
            <span id="sum_gen_isk" class="text-danger">- ₺ 0,00</span>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom small">
            <span class="text-muted">Vergi (KDV)</span>
            <span id="sum_kdv" class="fw-semibold">₺ 0,00</span>
          </div>
          <div class="d-flex justify-content-between py-2 fw-bold" style="font-size:1.1rem;">
            <span style="color:var(--hsg-dark)">GENEL TOPLAM</span>
            <span id="sum_toplam" style="color:var(--hsg-gold);">₺ 0,00</span>
          </div>

          <!-- KDV dağılımı -->
          <div id="kdv_dagilim" class="mt-2 pt-2 border-top small text-muted" style="display:none;">
            <div class="fw-semibold mb-1">KDV Dağılımı:</div>
            <div id="kdv_dagilim_icerik"></div>
          </div>
        </div>
        <div class="card-footer">
          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-warning fw-bold btn-lg">
              <i class="bi bi-check2-circle me-2"></i>Teklifi Kaydet
            </button>
            <button type="submit" name="ve_gonder" value="1" class="btn btn-primary">
              <i class="bi bi-send me-2"></i>Kaydet & Gönderildi Yap
            </button>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">İptal</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<!-- Şablon verileri -->
<script>
const BASE_URL = '<?= BASE_URL ?>';
const DEFAULT_KDV = <?= (int)ayar('varsayilan_kdv', '18') ?>;
const CURRENCIES = <?= CURRENCIES ?>;

// Kopyalanan kalemler
const KOPYA_KALEMLER = <?= json_encode($kopyaKalemler) ?>;

let kalemSayaci = 0;

// Para birimi sembolü
function getSym() {
  const sel = document.getElementById('para_birimi');
  return sel.options[sel.selectedIndex]?.dataset?.sym || '₺';
}

function formatPara(val) {
  const sym = getSym();
  return sym + ' ' + parseFloat(val || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Yeni boş kalem satırı oluştur
function olusturKalemSatiri(data = {}) {
  const id = ++kalemSayaci;
  const aciklama = data.aciklama || '';
  const detay    = data.detay || '';
  const miktar   = data.miktar || 1;
  const birim    = data.birim || 'Adet';
  const fiyat    = data.birim_fiyat || 0;
  const iskonto  = data.iskonto || 0;
  const kdv      = data.kdv_orani !== undefined ? data.kdv_orani : DEFAULT_KDV;
  const urunId   = data.urun_id || '';

  const tr = document.createElement('tr');
  tr.className = 'kalem-satir';
  tr.dataset.id = id;
  tr.innerHTML = `
    <td class="text-center ps-2" style="cursor:grab;color:#ccc;">
      <i class="bi bi-grip-vertical"></i>
      <input type="hidden" name="kalemler[${id}][sira]" value="${id}" class="kalem-sira">
      <input type="hidden" name="kalemler[${id}][urun_id]" value="${urunId}">
    </td>
    <td>
      <input type="text" name="kalemler[${id}][aciklama]" class="form-control form-control-sm fw-semibold"
             value="${escAttr(aciklama)}" placeholder="Ürün / Hizmet adı" required
             style="border:none;border-bottom:1px solid #e2e8f0;border-radius:0;padding:6px 2px;">
      <input type="text" name="kalemler[${id}][detay]" class="form-control form-control-sm text-muted mt-1"
             value="${escAttr(detay)}" placeholder="Teknik detay, not (opsiyonel)"
             style="font-size:0.75rem;border:none;border-bottom:1px solid #f0f4f8;border-radius:0;padding:4px 2px;">
    </td>
    <td>
      <input type="number" name="kalemler[${id}][miktar]" class="form-control form-control-sm kalem-input"
             value="${miktar}" min="0" step="0.01" style="text-align:right;">
    </td>
    <td>
      <select name="kalemler[${id}][birim]" class="form-select form-select-sm">
        ${['Adet','Kg','Ton','Metre','m²','Litre','Saat','Gün','Takım','Set','Paket'].map(b =>
          `<option${birim===b?' selected':''}>${b}</option>`).join('')}
      </select>
    </td>
    <td>
      <div class="input-group input-group-sm">
        <span class="input-group-text birim-sym" style="font-size:0.7rem;">${getSym()}</span>
        <input type="number" name="kalemler[${id}][birim_fiyat]" class="form-control form-control-sm kalem-input"
               value="${parseFloat(fiyat).toFixed(2)}" min="0" step="0.01" style="text-align:right;">
      </div>
    </td>
    <td>
      <div class="input-group input-group-sm">
        <input type="number" name="kalemler[${id}][iskonto]" class="form-control form-control-sm kalem-input"
               value="${iskonto}" min="0" max="100" step="0.01" style="text-align:right;">
        <span class="input-group-text" style="font-size:0.7rem;">%</span>
      </div>
    </td>
    <td>
      <div class="input-group input-group-sm">
        <input type="number" name="kalemler[${id}][kdv_orani]" class="form-control form-control-sm kalem-input"
               value="${kdv}" min="0" max="100" step="1" style="text-align:right;">
        <span class="input-group-text" style="font-size:0.7rem;">%</span>
      </div>
    </td>
    <td class="text-end">
      <span class="kalem-toplam fw-bold" style="color:var(--hsg-dark);font-size:0.9rem;">
        ${formatPara(0)}
      </span>
    </td>
    <td class="text-center">
      <button type="button" onclick="kalemSil(this)" class="btn btn-sm btn-link text-danger p-0">
        <i class="bi bi-x-lg"></i>
      </button>
    </td>
  `;

  // Event listener
  tr.querySelectorAll('.kalem-input').forEach(inp => {
    inp.addEventListener('input', hesapla);
  });

  return tr;
}

function kalemSil(btn) {
  btn.closest('tr').remove();
  hesapla();
}

function manuelKalemEkle() {
  const tbody = document.getElementById('kalemler_body');
  const tr = olusturKalemSatiri();
  tbody.appendChild(tr);
  tr.querySelector('[name*=aciklama]').focus();
  hesapla();
}

function urundenKalemEkle(urun) {
  const tbody = document.getElementById('kalemler_body');
  const tr = olusturKalemSatiri({
    aciklama:    urun.ad,
    detay:       urun.aciklama || '',
    miktar:      urun.min_miktar || 1,
    birim:       urun.birim || 'Adet',
    birim_fiyat: urun.birim_fiyat || 0,
    iskonto:     0,
    kdv_orani:   urun.kdv_orani !== undefined ? urun.kdv_orani : DEFAULT_KDV,
    urun_id:     urun.id,
  });
  tbody.appendChild(tr);
  hesapla();
  document.getElementById('urun_ara_input').value = '';
  document.getElementById('urun_ara_sonuclar').style.display = 'none';
}

// Toplamları hesapla
function hesapla() {
  let araToplam  = 0;
  let iskontoT   = 0;
  let kdvBiriktir = {};
  const sym = getSym();

  document.querySelectorAll('.kalem-satir').forEach(tr => {
    const miktar     = parseFloat(tr.querySelector('[name*=miktar]')?.value || 0);
    const birimFiyat = parseFloat(tr.querySelector('[name*=birim_fiyat]')?.value || 0);
    const iskonto    = parseFloat(tr.querySelector('[name*=iskonto]')?.value || 0);
    const kdv        = parseFloat(tr.querySelector('[name*=kdv_orani]')?.value || 0);

    const satirAra  = miktar * birimFiyat;
    const satirIsk  = satirAra * (iskonto / 100);
    const satirNet  = satirAra - satirIsk;
    const satirKdv  = satirNet * (kdv / 100);
    const satirToplam = satirNet + satirKdv;

    araToplam  += satirAra;
    iskontoT   += satirIsk;
    if (kdv > 0) kdvBiriktir[kdv] = (kdvBiriktir[kdv] || 0) + satirKdv;

    tr.querySelector('.kalem-toplam').textContent = formatPara(satirToplam);
  });

  const genelIskontoP = parseFloat(document.getElementById('genel_iskonto')?.value || 0);
  const sonAra = araToplam - iskontoT;
  const genelIskontoT = sonAra * (genelIskontoP / 100);
  const kdvToplam = Object.values(kdvBiriktir).reduce((a, b) => a + b, 0);
  const genelToplam = sonAra - genelIskontoT + kdvToplam;

  document.getElementById('sum_ara').textContent       = formatPara(araToplam);
  document.getElementById('sum_kalem_isk').textContent  = '- ' + formatPara(iskontoT);
  document.getElementById('sum_gen_isk_pct').textContent = genelIskontoP.toFixed(0);
  document.getElementById('sum_gen_isk').textContent    = '- ' + formatPara(genelIskontoT);
  document.getElementById('sum_kdv').textContent        = formatPara(kdvToplam);
  document.getElementById('sum_toplam').textContent     = formatPara(genelToplam);

  // KDV dağılımı
  const kdvDiv = document.getElementById('kdv_dagilim');
  const kdvIcerik = document.getElementById('kdv_dagilim_icerik');
  if (Object.keys(kdvBiriktir).length > 0) {
    kdvDiv.style.display = 'block';
    kdvIcerik.innerHTML = Object.entries(kdvBiriktir).map(([oran, tutar]) =>
      `<div class="d-flex justify-content-between">
        <span>KDV %${oran}</span>
        <span>${formatPara(tutar)}</span>
      </div>`
    ).join('');
  } else {
    kdvDiv.style.display = 'none';
  }

  // Para birimi sembollerini güncelle
  document.querySelectorAll('.birim-sym').forEach(el => el.textContent = sym);
}

// Müşteri seçimi
document.getElementById('musteri_select').addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  const ozet = document.getElementById('musteri_ozet');
  const detay = document.getElementById('musteri_detay');
  if (this.value) {
    ozet.style.display = 'block';
    let html = '';
    if (opt.dataset.yetkili) html += `<div class="col-md-3"><strong>Yetkili:</strong> ${escHtml(opt.dataset.yetkili)}</div>`;
    if (opt.dataset.email)   html += `<div class="col-md-4"><strong>E-posta:</strong> <a href="mailto:${escHtml(opt.dataset.email)}">${escHtml(opt.dataset.email)}</a></div>`;
    if (opt.dataset.telefon) html += `<div class="col-md-3"><strong>Tel:</strong> ${escHtml(opt.dataset.telefon)}</div>`;
    if (opt.dataset.adres)   html += `<div class="col-12"><strong>Adres:</strong> ${escHtml(opt.dataset.adres)}, ${escHtml(opt.dataset.ulke)}</div>`;
    detay.innerHTML = html;
  } else {
    ozet.style.display = 'none';
  }
});

// Şablon seçimi
document.getElementById('sablon_select').addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  if (this.value && opt.dataset.on !== undefined) {
    if (confirm('Şablon metinleri uygulanacak. Mevcut metinler değiştirilecek. Devam?')) {
      document.getElementById('on_yazi').value       = opt.dataset.on;
      document.getElementById('son_yazi').value      = opt.dataset.son;
      document.getElementById('sartlar').value       = opt.dataset.sartlar;
      document.getElementById('odeme_kosullari').value = opt.dataset.odeme;

      const gun = parseInt(opt.dataset.gecerlilik || 30);
      const gecTarih = new Date();
      gecTarih.setDate(gecTarih.getDate() + gun);
      document.getElementById('gecerlilik_tarihi').value = gecTarih.toISOString().split('T')[0];
    }
  }
});

// Para birimi değişince
document.getElementById('para_birimi').addEventListener('change', function() {
  hesapla();
});

// Genel iskonto değişince
document.getElementById('genel_iskonto').addEventListener('input', hesapla);

// Ürün arama
let araTimeout;
document.getElementById('urun_ara_input').addEventListener('input', function() {
  clearTimeout(araTimeout);
  const q = this.value.trim();
  const sonuclar = document.getElementById('urun_ara_sonuclar');
  if (q.length < 1) { sonuclar.style.display = 'none'; return; }

  araTimeout = setTimeout(() => {
    fetch(`${BASE_URL}/ajax/urun_ara.php?q=${encodeURIComponent(q)}`)
      .then(r => r.json())
      .then(data => {
        if (data.length === 0) {
          sonuclar.innerHTML = '<div class="p-3 text-muted small">Ürün bulunamadı. <button type="button" onclick="manuelKalemEkle()" class="btn btn-sm btn-outline-secondary ms-2">Manuel ekle</button></div>';
        } else {
          sonuclar.innerHTML = data.map(u => `
            <div class="p-2 border-bottom urun-sonuc-item" onclick="urundenKalemEkle(${JSON.stringify(u).replace(/"/g, '&quot;')})" style="cursor:pointer;">
              <div class="d-flex align-items-center gap-2">
                ${u.urun_kodu ? `<code class="text-muted" style="font-size:0.7rem;">${escHtml(u.urun_kodu)}</code>` : ''}
                <strong style="font-size:0.85rem;">${escHtml(u.ad)}</strong>
                <span class="ms-auto text-success fw-bold" style="font-size:0.85rem;">${u.sym} ${parseFloat(u.birim_fiyat).toFixed(2)}</span>
                <span class="text-muted" style="font-size:0.7rem;">/${u.birim}</span>
              </div>
              ${u.aciklama ? `<div class="text-muted" style="font-size:0.72rem;">${escHtml(u.aciklama.substring(0, 70))}</div>` : ''}
            </div>
          `).join('');
        }
        sonuclar.style.display = 'block';
        // Sonuçlara hover efekti
        sonuclar.querySelectorAll('.urun-sonuc-item').forEach(el => {
          el.addEventListener('mouseenter', () => el.style.background = '#f8f9fa');
          el.addEventListener('mouseleave', () => el.style.background = '');
        });
      })
      .catch(() => { sonuclar.style.display = 'none'; });
  }, 250);
});

// Ürün arama alanı dışı tıklama
document.addEventListener('click', e => {
  if (!e.target.closest('#urun_ara_input') && !e.target.closest('#urun_ara_sonuclar')) {
    document.getElementById('urun_ara_sonuclar').style.display = 'none';
  }
});

// Drag & Drop (Sortable)
document.addEventListener('DOMContentLoaded', () => {
  Sortable.create(document.getElementById('kalemler_body'), {
    handle: '.bi-grip-vertical',
    animation: 150,
    onEnd: () => {
      document.querySelectorAll('.kalem-sira').forEach((inp, i) => {
        inp.value = i + 1;
      });
    }
  });

  // Select2
  if ($.fn.select2) {
    $('#musteri_select').select2({ placeholder: '— Müşteri Seçin —', allowClear: true });
  }

  // Müşteri zaten seçiliyse göster
  const ms = document.getElementById('musteri_select');
  if (ms.value) ms.dispatchEvent(new Event('change'));

  // Kopyalanan kalemleri yükle
  if (KOPYA_KALEMLER.length > 0) {
    KOPYA_KALEMLER.forEach(k => urundenKalemEkle({
      id: k.urun_id,
      ad: k.aciklama,
      aciklama: k.detay,
      min_miktar: k.miktar,
      birim: k.birim,
      birim_fiyat: k.birim_fiyat,
      iskonto: k.iskonto,
      kdv_orani: k.kdv_orani,
    }));
  } else if (KOPYA_KALEMLER.length === 0) {
    // Yeni teklif - boş satır ekle
    manuelKalemEkle();
  }
});

function escHtml(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function escAttr(s) {
  return String(s || '').replace(/"/g,'&quot;');
}
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
