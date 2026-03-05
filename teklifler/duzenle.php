<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM teklifler WHERE id = ?");
$stmt->execute([$id]);
$teklif = $stmt->fetch();
if (!$teklif) { flashMesaj('danger','Teklif bulunamadı.'); header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM teklif_kalemleri WHERE teklif_id = ? ORDER BY sira, id");
$stmt->execute([$id]);
$kalemler = $stmt->fetchAll();

$pageTitle  = 'Teklif Düzenle: ' . $teklif['teklif_no'];
$activePage = 'teklifler';
$breadcrumb = [
    ['label' => 'Teklifler', 'url' => BASE_URL . '/teklifler/index.php'],
    ['label' => $teklif['teklif_no'], 'url' => BASE_URL . '/teklifler/goruntule.php?id=' . $id],
    ['label' => 'Düzenle', 'url' => ''],
];

$musteriler  = $pdo->query("SELECT id, musteri_no, firma_adi, yetkili_kisi, email, telefon, adres, sehir, ulke, vergi_no, vergi_dairesi FROM musteriler WHERE durum='aktif' ORDER BY firma_adi")->fetchAll();
$sablonlar   = $pdo->query("SELECT * FROM sablonlar ORDER BY varsayilan DESC, ad")->fetchAll();
$currencies  = json_decode(CURRENCIES, true);
$kdvOranlari = json_decode(KDV_ORANLARI, true);

$hatalar = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $musteriId    = (int)($_POST['musteri_id'] ?? 0);
    $sablonId     = (int)($_POST['sablon_id'] ?? 0) ?: null;
    $baslik       = trim($_POST['baslik'] ?? '');
    $tarih        = $_POST['tarih'] ?? date('Y-m-d');
    $gecGecerlTarih = $_POST['gecerlilik_tarihi'] ?? '';
    $paraBirimi   = $_POST['para_birimi'] ?? 'TRY';
    $genelIskonto = (float)str_replace(',', '.', $_POST['genel_iskonto'] ?? '0');
    $on_yazi      = $_POST['on_yazi'] ?? '';
    $son_yazi     = $_POST['son_yazi'] ?? '';
    $sartlar      = $_POST['sartlar'] ?? '';
    $odeme        = $_POST['odeme_kosullari'] ?? '';
    $ic_notlar    = $_POST['ic_notlar'] ?? '';
    $postKalemler = $_POST['kalemler'] ?? [];
    $yeniDurum    = $_POST['durum'] ?? $teklif['durum'];

    if (!$musteriId) $hatalar[] = 'Müşteri seçimi zorunludur.';
    if (empty($postKalemler)) $hatalar[] = 'En az bir kalem gerekli.';

    if (empty($hatalar)) {
        $araToplam = 0; $iskontoT = 0; $kdvT = 0;
        $islenenKalemler = [];

        foreach ($postKalemler as $k) {
            if (empty(trim($k['aciklama'] ?? ''))) continue;
            $mik    = max(0, (float)str_replace(',', '.', $k['miktar'] ?? '1'));
            $fiyat  = max(0, (float)str_replace(',', '.', $k['birim_fiyat'] ?? '0'));
            $isk    = max(0, min(100, (float)str_replace(',', '.', $k['iskonto'] ?? '0')));
            $kdv    = max(0, (float)str_replace(',', '.', $k['kdv_orani'] ?? '18'));

            $sAra  = $mik * $fiyat;
            $sIsk  = $sAra * ($isk / 100);
            $sNet  = $sAra - $sIsk;
            $sKdv  = $sNet * ($kdv / 100);
            $sToplam = $sNet + $sKdv;

            $araToplam += $sAra;
            $iskontoT  += $sIsk;
            $kdvT      += $sKdv;

            $islenenKalemler[] = [
                'urun_id'        => (int)($k['urun_id'] ?? 0) ?: null,
                'sira'           => (int)($k['sira'] ?? 0),
                'aciklama'       => trim($k['aciklama']),
                'detay'          => trim($k['detay'] ?? ''),
                'miktar'         => $mik, 'birim' => trim($k['birim'] ?? 'Adet'),
                'birim_fiyat'    => $fiyat, 'iskonto' => $isk, 'kdv_orani' => $kdv,
                'ara_toplam'     => $sAra, 'iskonto_tutari' => $sIsk, 'kdv_tutari' => $sKdv, 'toplam' => $sToplam,
            ];
        }

        $genelIskontoT = ($araToplam - $iskontoT) * ($genelIskonto / 100);
        $genelToplam = ($araToplam - $iskontoT - $genelIskontoT) + $kdvT;

        $pdo->prepare("UPDATE teklifler SET
            musteri_id=?, sablon_id=?, baslik=?, tarih=?, gecerlilik_tarihi=?,
            para_birimi=?, genel_iskonto=?, ara_toplam=?, iskonto_tutari=?, kdv_tutari=?, genel_toplam=?,
            on_yazi=?, son_yazi=?, sartlar=?, odeme_kosullari=?, ic_notlar=?, durum=?,
            guncelleme_tarihi=NOW()
            WHERE id=?")->execute([
            $musteriId, $sablonId, $baslik, $tarih, $gecGecerlTarih,
            $paraBirimi, $genelIskonto,
            $araToplam, $iskontoT + $genelIskontoT, $kdvT, $genelToplam,
            $on_yazi, $son_yazi, $sartlar, $odeme, $ic_notlar,
            $yeniDurum, $id
        ]);

        // Eski kalemleri sil, yenilerini ekle
        $pdo->prepare("DELETE FROM teklif_kalemleri WHERE teklif_id=?")->execute([$id]);
        $kalemStmt = $pdo->prepare("INSERT INTO teklif_kalemleri
            (teklif_id, urun_id, sira, aciklama, detay, miktar, birim, birim_fiyat,
             iskonto, kdv_orani, ara_toplam, iskonto_tutari, kdv_tutari, toplam)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        foreach ($islenenKalemler as $k) {
            $kalemStmt->execute([
                $id, $k['urun_id'], $k['sira'], $k['aciklama'], $k['detay'],
                $k['miktar'], $k['birim'], $k['birim_fiyat'],
                $k['iskonto'], $k['kdv_orani'], $k['ara_toplam'],
                $k['iskonto_tutari'], $k['kdv_tutari'], $k['toplam']
            ]);
        }

        flashMesaj('success', 'Teklif güncellendi.');
        header("Location: goruntule.php?id=$id"); exit;
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1><i class="bi bi-pencil-square me-2 text-gold"></i>Teklif Düzenle</h1>
    <div class="page-subtitle"><?= e($teklif['teklif_no']) ?></div>
  </div>
  <a href="goruntule.php?id=<?= $id ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Geri</a>
</div>

<?php if (!empty($hatalar)): ?>
  <div class="alert alert-danger alert-permanent"><?= implode('<br>', array_map('htmlspecialchars', $hatalar)) ?></div>
<?php endif; ?>

<form method="POST" id="teklifForm">
  <div class="row g-3">
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
                    <?= $teklif['musteri_id'] == $m['id'] ? 'selected' : '' ?>>
                    <?= e($m['firma_adi']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Şablon</label>
              <select name="sablon_id" class="form-select">
                <option value="">— Şablonsuz —</option>
                <?php foreach ($sablonlar as $s): ?>
                  <option value="<?= $s['id'] ?>" <?= $teklif['sablon_id'] == $s['id'] ? 'selected' : '' ?>>
                    <?= e($s['ad']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Teklif Bilgileri -->
      <div class="card mb-3">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-info-circle me-2 text-gold"></i>Teklif Bilgileri</h6></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Başlık</label>
              <input type="text" name="baslik" class="form-control" value="<?= e($teklif['baslik']) ?>" placeholder="Fiyat Teklifi">
            </div>
            <div class="col-md-3">
              <label class="form-label">Para Birimi</label>
              <select name="para_birimi" id="para_birimi" class="form-select">
                <?php foreach ($currencies as $code => $c): ?>
                  <option value="<?= $code ?>" data-sym="<?= $c['symbol'] ?>" <?= $teklif['para_birimi'] === $code ? 'selected' : '' ?>><?= $code ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Durum</label>
              <select name="durum" class="form-select">
                <?php foreach (teklifDurumlari() as $dk => $dv): ?>
                  <option value="<?= $dk ?>" <?= $teklif['durum'] === $dk ? 'selected' : '' ?>><?= $dv ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Teklif Tarihi</label>
              <input type="date" name="tarih" class="form-control" value="<?= e($teklif['tarih']) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Geçerlilik</label>
              <input type="date" name="gecerlilik_tarihi" id="gecerlilik_tarihi" class="form-control" value="<?= e($teklif['gecerlilik_tarihi']) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Genel İskonto (%)</label>
              <div class="input-group">
                <input type="number" name="genel_iskonto" id="genel_iskonto" class="form-control"
                       value="<?= e($teklif['genel_iskonto']) ?>" min="0" max="100" step="0.01">
                <span class="input-group-text">%</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Ürün Arama -->
      <div class="card mb-2" style="border:2px dashed var(--hsg-gold);background:var(--hsg-gold-pale);">
        <div class="card-body py-2 px-3">
          <div class="row g-2 align-items-center">
            <div class="col">
              <div class="search-box">
                <i class="bi bi-search" style="color:var(--hsg-gold)"></i>
                <input type="text" id="urun_ara_input" class="form-control border-0 bg-transparent"
                       placeholder="Katalogdan ürün ara..." autocomplete="off">
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
        <div class="card-header"><h6 class="card-title"><i class="bi bi-list-ul me-2 text-gold"></i>Teklif Kalemleri</h6></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table mb-0" id="kalemler_table">
              <thead>
                <tr style="background:var(--hsg-dark);color:rgba(255,255,255,0.85);font-size:0.75rem;">
                  <th width="30" class="text-center">☰</th>
                  <th>Ürün / Hizmet</th>
                  <th width="80">Miktar</th>
                  <th width="80">Birim</th>
                  <th width="130">Birim Fiyat</th>
                  <th width="70">İsk %</th>
                  <th width="70">KDV %</th>
                  <th width="130" class="text-end">Toplam</th>
                  <th width="40"></th>
                </tr>
              </thead>
              <tbody id="kalemler_body"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Metinler -->
      <div class="card">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-file-text me-2 text-gold"></i>Metin & Koşullar</h6></div>
        <div class="card-body">
          <ul class="nav nav-pills mb-3">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab_on">Ön Yazı</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab_son">Son Yazı</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab_sartlar">Şartlar</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab_odeme">Ödeme</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab_notlar">İç Notlar</button></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="tab_on"><textarea name="on_yazi" id="on_yazi" class="form-control" rows="4"><?= e($teklif['on_yazi']) ?></textarea></div>
            <div class="tab-pane fade" id="tab_son"><textarea name="son_yazi" class="form-control" rows="4"><?= e($teklif['son_yazi']) ?></textarea></div>
            <div class="tab-pane fade" id="tab_sartlar"><textarea name="sartlar" class="form-control" rows="5"><?= e($teklif['sartlar']) ?></textarea></div>
            <div class="tab-pane fade" id="tab_odeme"><textarea name="odeme_kosullari" class="form-control" rows="3"><?= e($teklif['odeme_kosullari']) ?></textarea></div>
            <div class="tab-pane fade" id="tab_notlar"><textarea name="ic_notlar" class="form-control" rows="3"><?= e($teklif['ic_notlar']) ?></textarea></div>
          </div>
        </div>
      </div>
    </div>

    <!-- SAĞ ÖZET -->
    <div class="col-lg-4">
      <div class="card mb-3" style="position:sticky;top:80px;">
        <div class="card-header" style="background:var(--hsg-dark);color:#fff;">
          <h6 class="mb-0 text-gold"><i class="bi bi-calculator me-2"></i>Teklif Özeti</h6>
        </div>
        <div class="card-body">
          <div class="d-flex justify-content-between py-1 border-bottom small"><span class="text-muted">Ara Toplam</span><span id="sum_ara">₺ 0,00</span></div>
          <div class="d-flex justify-content-between py-1 border-bottom small"><span class="text-muted">Kalem İskontoları</span><span id="sum_kalem_isk" class="text-danger">- ₺ 0,00</span></div>
          <div class="d-flex justify-content-between py-1 border-bottom small"><span class="text-muted">Genel İskonto (%<span id="sum_gen_isk_pct">0</span>)</span><span id="sum_gen_isk" class="text-danger">- ₺ 0,00</span></div>
          <div class="d-flex justify-content-between py-1 border-bottom small"><span class="text-muted">KDV</span><span id="sum_kdv">₺ 0,00</span></div>
          <div class="d-flex justify-content-between py-2 fw-bold" style="font-size:1.1rem;">
            <span style="color:var(--hsg-dark)">TOPLAM</span>
            <span id="sum_toplam" style="color:var(--hsg-gold);">₺ 0,00</span>
          </div>
          <div id="kdv_dagilim" style="display:none;" class="mt-2 pt-2 border-top small text-muted">
            <div class="fw-semibold mb-1">KDV Dağılımı:</div>
            <div id="kdv_dagilim_icerik"></div>
          </div>
        </div>
        <div class="card-footer">
          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-check2 me-2"></i>Güncelle</button>
            <a href="goruntule.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">İptal</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const DEFAULT_KDV = <?= (int)ayar('varsayilan_kdv', '18') ?>;
const MEVCUT_KALEMLER = <?= json_encode($kalemler) ?>;

let kalemSayaci = 0;

function getSym() {
  const sel = document.getElementById('para_birimi');
  return sel.options[sel.selectedIndex]?.dataset?.sym || '₺';
}
function formatPara(val) {
  return getSym() + ' ' + parseFloat(val||0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,'.');
}

function olusturKalemSatiri(data={}) {
  const id = ++kalemSayaci;
  const birimler = ['Adet','Kg','Ton','Metre','m²','Litre','Saat','Gün','Takım','Set','Paket'];
  const tr = document.createElement('tr');
  tr.className = 'kalem-satir';
  tr.dataset.id = id;
  tr.innerHTML = `
    <td class="text-center ps-2" style="cursor:grab;color:#ccc;"><i class="bi bi-grip-vertical"></i>
      <input type="hidden" name="kalemler[${id}][sira]" value="${id}" class="kalem-sira">
      <input type="hidden" name="kalemler[${id}][urun_id]" value="${data.urun_id||''}">
    </td>
    <td>
      <input type="text" name="kalemler[${id}][aciklama]" class="form-control form-control-sm fw-semibold"
             value="${escAttr(data.aciklama||'')}" placeholder="Açıklama" required
             style="border:none;border-bottom:1px solid #e2e8f0;border-radius:0;padding:6px 2px;">
      <input type="text" name="kalemler[${id}][detay]" class="form-control form-control-sm text-muted mt-1"
             value="${escAttr(data.detay||'')}" placeholder="Detay (opsiyonel)"
             style="font-size:0.75rem;border:none;border-bottom:1px solid #f0f4f8;border-radius:0;padding:4px 2px;">
    </td>
    <td><input type="number" name="kalemler[${id}][miktar]" class="form-control form-control-sm kalem-input" value="${data.miktar||1}" min="0" step="0.01" style="text-align:right;"></td>
    <td><select name="kalemler[${id}][birim]" class="form-select form-select-sm">${birimler.map(b=>`<option${(data.birim||'Adet')===b?' selected':''}>${b}</option>`).join('')}</select></td>
    <td><div class="input-group input-group-sm"><span class="input-group-text birim-sym" style="font-size:0.7rem;">${getSym()}</span>
      <input type="number" name="kalemler[${id}][birim_fiyat]" class="form-control form-control-sm kalem-input" value="${parseFloat(data.birim_fiyat||0).toFixed(2)}" min="0" step="0.01" style="text-align:right;"></div></td>
    <td><div class="input-group input-group-sm"><input type="number" name="kalemler[${id}][iskonto]" class="form-control form-control-sm kalem-input" value="${data.iskonto||0}" min="0" max="100" step="0.01" style="text-align:right;"><span class="input-group-text" style="font-size:0.7rem;">%</span></div></td>
    <td><div class="input-group input-group-sm"><input type="number" name="kalemler[${id}][kdv_orani]" class="form-control form-control-sm kalem-input" value="${data.kdv_orani!==undefined?data.kdv_orani:DEFAULT_KDV}" min="0" max="100" step="1" style="text-align:right;"><span class="input-group-text" style="font-size:0.7rem;">%</span></div></td>
    <td class="text-end"><span class="kalem-toplam fw-bold" style="color:var(--hsg-dark);font-size:0.9rem;">${formatPara(0)}</span></td>
    <td class="text-center"><button type="button" onclick="kalemSil(this)" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-x-lg"></i></button></td>
  `;
  tr.querySelectorAll('.kalem-input').forEach(inp => inp.addEventListener('input', hesapla));
  return tr;
}

function kalemSil(btn) { btn.closest('tr').remove(); hesapla(); }
function manuelKalemEkle() { document.getElementById('kalemler_body').appendChild(olusturKalemSatiri()); hesapla(); }

function urundenKalemEkle(urun) {
  document.getElementById('kalemler_body').appendChild(olusturKalemSatiri({
    aciklama: urun.ad, detay: urun.aciklama||'', miktar: urun.min_miktar||1,
    birim: urun.birim||'Adet', birim_fiyat: urun.birim_fiyat||0,
    iskonto: 0, kdv_orani: urun.kdv_orani!==undefined?urun.kdv_orani:DEFAULT_KDV, urun_id: urun.id,
  }));
  hesapla();
  document.getElementById('urun_ara_input').value = '';
  document.getElementById('urun_ara_sonuclar').style.display = 'none';
}

function hesapla() {
  let araToplam=0,iskontoT=0;
  const kdvBiriktir={};
  const sym=getSym();
  document.querySelectorAll('.kalem-satir').forEach(tr => {
    const mik=parseFloat(tr.querySelector('[name*=miktar]')?.value||0);
    const fiyat=parseFloat(tr.querySelector('[name*=birim_fiyat]')?.value||0);
    const isk=parseFloat(tr.querySelector('[name*=iskonto]')?.value||0);
    const kdv=parseFloat(tr.querySelector('[name*=kdv_orani]')?.value||0);
    const sAra=mik*fiyat; const sIsk=sAra*(isk/100);
    const sNet=sAra-sIsk; const sKdv=sNet*(kdv/100);
    araToplam+=sAra; iskontoT+=sIsk;
    if(kdv>0) kdvBiriktir[kdv]=(kdvBiriktir[kdv]||0)+sKdv;
    tr.querySelector('.kalem-toplam').textContent=formatPara(sNet+sKdv);
  });
  const giP=parseFloat(document.getElementById('genel_iskonto')?.value||0);
  const sonAra=araToplam-iskontoT;
  const giT=sonAra*(giP/100);
  const kdvT=Object.values(kdvBiriktir).reduce((a,b)=>a+b,0);
  const gToplam=sonAra-giT+kdvT;
  document.getElementById('sum_ara').textContent=formatPara(araToplam);
  document.getElementById('sum_kalem_isk').textContent='- '+formatPara(iskontoT);
  document.getElementById('sum_gen_isk_pct').textContent=giP.toFixed(0);
  document.getElementById('sum_gen_isk').textContent='- '+formatPara(giT);
  document.getElementById('sum_kdv').textContent=formatPara(kdvT);
  document.getElementById('sum_toplam').textContent=formatPara(gToplam);
  const kdvDiv=document.getElementById('kdv_dagilim');
  if(Object.keys(kdvBiriktir).length>0){
    kdvDiv.style.display='block';
    document.getElementById('kdv_dagilim_icerik').innerHTML=Object.entries(kdvBiriktir).map(([o,t])=>`<div class="d-flex justify-content-between"><span>KDV %${o}</span><span>${formatPara(t)}</span></div>`).join('');
  } else { kdvDiv.style.display='none'; }
  document.querySelectorAll('.birim-sym').forEach(el=>el.textContent=sym);
}

let araTimeout;
document.getElementById('urun_ara_input').addEventListener('input', function(){
  clearTimeout(araTimeout);
  const q=this.value.trim();
  const son=document.getElementById('urun_ara_sonuclar');
  if(q.length<1){son.style.display='none';return;}
  araTimeout=setTimeout(()=>{
    fetch(`${BASE_URL}/ajax/urun_ara.php?q=${encodeURIComponent(q)}`)
      .then(r=>r.json()).then(data=>{
        son.innerHTML=data.length===0?'<div class="p-3 text-muted small">Bulunamadı</div>':
          data.map(u=>`<div class="p-2 border-bottom urun-sonuc-item" onclick="urundenKalemEkle(${JSON.stringify(u).replace(/"/g,'&quot;')})" style="cursor:pointer;">
            <strong style="font-size:0.85rem;">${escHtml(u.ad)}</strong>
            <span class="ms-2 text-success fw-bold" style="font-size:0.85rem;">${u.sym} ${parseFloat(u.birim_fiyat).toFixed(2)}</span>
          </div>`).join('');
        son.style.display='block';
      });
  },250);
});
document.addEventListener('click',e=>{if(!e.target.closest('#urun_ara_input')&&!e.target.closest('#urun_ara_sonuclar'))document.getElementById('urun_ara_sonuclar').style.display='none';});
document.getElementById('para_birimi').addEventListener('change',hesapla);
document.getElementById('genel_iskonto').addEventListener('input',hesapla);

function escHtml(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function escAttr(s){return String(s||'').replace(/"/g,'&quot;');}

document.addEventListener('DOMContentLoaded',()=>{
  Sortable.create(document.getElementById('kalemler_body'),{handle:'.bi-grip-vertical',animation:150,onEnd:()=>{document.querySelectorAll('.kalem-sira').forEach((inp,i)=>inp.value=i+1);}});
  if($.fn.select2) $('#musteri_select').select2({placeholder:'— Müşteri Seçin —',allowClear:true});
  MEVCUT_KALEMLER.forEach(k=>document.getElementById('kalemler_body').appendChild(olusturKalemSatiri({
    urun_id:k.urun_id,aciklama:k.aciklama,detay:k.detay,
    miktar:k.miktar,birim:k.birim,birim_fiyat:k.birim_fiyat,
    iskonto:k.iskonto,kdv_orani:k.kdv_orani,
  })));
  if(MEVCUT_KALEMLER.length===0) manuelKalemEkle();
  hesapla();
});
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
