<?php
/**
 * HSG Aviation - Teklif Yazdırma / PDF Görünümü
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT t.*, m.firma_adi, m.yetkili_kisi, m.unvan, m.email as musteri_email,
    m.telefon as musteri_telefon, m.fax as musteri_fax, m.adres as musteri_adres,
    m.ilce, m.sehir, m.ulke, m.posta_kodu,
    m.vergi_no, m.vergi_dairesi, m.iban as musteri_iban, m.banka_adi as musteri_banka,
    s.renk_ana, s.renk_aksan, s.logo_goster, s.imza_alani, s.kdv_goster, s.iskonto_goster
    FROM teklifler t
    LEFT JOIN musteriler m ON t.musteri_id = m.id
    LEFT JOIN sablonlar s ON t.sablon_id = s.id
    WHERE t.id = ?");
$stmt->execute([$id]);
$teklif = $stmt->fetch();
if (!$teklif) die('Teklif bulunamadı');

$stmt = $pdo->prepare("SELECT * FROM teklif_kalemleri WHERE teklif_id = ? ORDER BY sira, id");
$stmt->execute([$id]);
$kalemler = $stmt->fetchAll();

$currencies = json_decode(CURRENCIES, true);
$sym = $currencies[$teklif['para_birimi']]['symbol'] ?? $teklif['para_birimi'];

$renkAna   = $teklif['renk_ana']   ?: '#0a1628';
$renkAksan = $teklif['renk_aksan'] ?: '#e8a000';

$firma_adi      = ayar('firma_adi', 'HSG Aviation');
$firma_unvan    = ayar('firma_unvan', '');
$firma_adres    = ayar('firma_adres', '');
$firma_sehir    = ayar('firma_sehir', '');
$firma_ulke     = ayar('firma_ulke', 'Türkiye');
$firma_telefon  = ayar('firma_telefon', '');
$firma_email    = ayar('firma_email', '');
$firma_website  = ayar('firma_website', '');
$firma_vergi    = ayar('firma_vergi_no', '');
$firma_vdairesi = ayar('firma_vergi_dairesi', '');
$firma_iban     = ayar('firma_iban', '');

// KDV grupları
$kdvGruplari = [];
foreach ($kalemler as $k) {
    $oran = (float)$k['kdv_orani'];
    if ($oran > 0) {
        $kdvGruplari[$oran] = ($kdvGruplari[$oran] ?? 0) + (float)$k['kdv_tutari'];
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teklif <?= e($teklif['teklif_no']) ?> — <?= e($firma_adi) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --rena: <?= e($renkAna) ?>;
  --raks: <?= e($renkAksan) ?>;
}
@page { margin: 0; size: A4; }
body {
  font-family: 'Inter', Arial, sans-serif;
  font-size: 10pt;
  color: #1a202c;
  background: #fff;
  max-width: 210mm;
  margin: 0 auto;
}

/* Print toolbar */
.print-toolbar {
  position: fixed;
  top: 0; left: 0; right: 0;
  background: var(--rena);
  padding: 10px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 999;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}
.print-toolbar button, .print-toolbar a {
  padding: 8px 18px;
  border-radius: 6px;
  border: none;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.print-toolbar .btn-print {
  background: var(--raks);
  color: var(--rena);
}
.print-toolbar .btn-back {
  background: rgba(255,255,255,0.15);
  color: #fff;
}
.print-toolbar .btn-back:hover {
  background: rgba(255,255,255,0.25);
}

/* Document */
.document {
  padding: 0;
  min-height: 297mm;
}

/* Header */
.doc-header {
  background: linear-gradient(135deg, var(--rena) 0%, #1e3a5f 100%);
  padding: 28px 32px 24px;
  color: #fff;
}
.header-grid {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
}
.firma-logo img {
  max-height: 55px;
  max-width: 160px;
  object-fit: contain;
  filter: brightness(1.2);
}
.firma-logo-text {
  font-size: 1.3rem;
  font-weight: 800;
  color: var(--raks);
  letter-spacing: -0.5px;
}
.firma-info {
  font-size: 8.5pt;
  opacity: 0.8;
  margin-top: 4px;
  line-height: 1.5;
}
.teklif-baslik {
  text-align: right;
}
.teklif-baslik .baslik-text {
  font-size: 18pt;
  font-weight: 800;
  color: var(--raks);
  letter-spacing: -0.5px;
  text-transform: uppercase;
}
.teklif-baslik .teklif-no {
  font-size: 13pt;
  font-weight: 700;
  margin-top: 4px;
}
.teklif-baslik .teklif-meta {
  font-size: 8.5pt;
  opacity: 0.85;
  line-height: 1.6;
  margin-top: 6px;
}

/* Body */
.doc-body {
  padding: 24px 32px;
}

/* Müşteri & Firma Bilgileri */
.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 20px;
  padding: 16px;
  background: #f8fafc;
  border-radius: 8px;
  border-left: 4px solid var(--raks);
}
.info-block .label {
  font-size: 7.5pt;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #718096;
  margin-bottom: 4px;
}
.info-block .value { font-size: 9.5pt; font-weight: 700; }
.info-block .value-sub { font-size: 8.5pt; color: #4a5568; line-height: 1.5; }

/* On yazı */
.on-yazi {
  font-size: 9.5pt;
  color: #2d3748;
  margin-bottom: 16px;
  padding: 12px 16px;
  background: #f8fafc;
  border-radius: 6px;
  line-height: 1.6;
  white-space: pre-line;
}

/* Tablo */
.teklif-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 16px;
  font-size: 9pt;
}
.teklif-table thead tr {
  background: var(--rena);
  color: rgba(255,255,255,0.9);
}
.teklif-table thead th {
  padding: 8px 10px;
  font-size: 7.5pt;
  font-weight: 600;
  letter-spacing: 0.3px;
  text-transform: uppercase;
  border: 1px solid rgba(255,255,255,0.1);
}
.teklif-table tbody td {
  padding: 8px 10px;
  border: 1px solid #e2e8f0;
  vertical-align: top;
}
.teklif-table tbody tr:nth-child(even) { background: #f8fafc; }
.teklif-table .kalem-adi { font-weight: 600; font-size: 9pt; }
.teklif-table .kalem-detay { font-size: 7.5pt; color: #718096; margin-top: 2px; }
.teklif-table .text-right { text-align: right; }
.teklif-table .text-center { text-align: center; }
.teklif-table tfoot tr td { border: 1px solid #e2e8f0; padding: 7px 10px; font-size: 9pt; }
.teklif-table tfoot .sum-row { background: #f8fafc; }
.teklif-table tfoot .total-row { background: var(--rena); color: #fff; font-size: 11pt; font-weight: 800; }
.teklif-table tfoot .total-row td { color: var(--raks); }

/* KDV Tablosu */
.kdv-table {
  float: right;
  width: 280px;
  border-collapse: collapse;
  font-size: 8.5pt;
  margin-bottom: 16px;
}
.kdv-table td { padding: 4px 10px; border: 1px solid #e2e8f0; }
.kdv-table .label-col { color: #718096; }

/* Son yazı */
.son-yazi {
  font-size: 9pt;
  color: #2d3748;
  margin: 16px 0;
  line-height: 1.6;
  white-space: pre-line;
  clear: both;
}

/* Şartlar */
.sartlar-box {
  background: #f8fafc;
  border-radius: 6px;
  padding: 12px 16px;
  font-size: 8pt;
  color: #4a5568;
  margin-bottom: 16px;
  line-height: 1.5;
  white-space: pre-line;
}
.sartlar-box .baslik {
  font-weight: 700;
  text-transform: uppercase;
  font-size: 7.5pt;
  color: var(--rena);
  margin-bottom: 6px;
  letter-spacing: 0.5px;
}

/* İmza */
.imza-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  margin-top: 30px;
}
.imza-box {
  border-top: 2px solid var(--rena);
  padding-top: 8px;
}
.imza-box .imza-label { font-size: 8pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #718096; }
.imza-alan { height: 50px; }

/* Footer */
.doc-footer {
  background: var(--rena);
  padding: 12px 32px;
  color: rgba(255,255,255,0.6);
  font-size: 7.5pt;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
}
.doc-footer .footer-bar {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--raks);
}

/* Print */
@media print {
  .print-toolbar { display: none !important; }
  .document { padding-top: 0 !important; }
  body { padding-top: 0 !important; }
  @page { margin: 10mm; }
  .no-print { display: none !important; }
}

/* Print padding (for toolbar) */
@media screen {
  .document { padding-top: 54px; }
}
</style>
</head>
<body>

<!-- Yazdırma Araç Çubuğu -->
<div class="print-toolbar no-print">
  <button onclick="window.print()" class="btn-print">
    🖨️ Yazdır / PDF Kaydet
  </button>
  <a href="goruntule.php?id=<?= $id ?>" class="btn-back">← Geri Dön</a>
  <a href="duzenle.php?id=<?= $id ?>" class="btn-back">✏️ Düzenle</a>
  <div style="margin-left:auto;color:rgba(255,255,255,0.5);font-size:0.75rem;">
    PDF kaydetmek için: Yazdır → "PDF olarak kaydet" seçin
  </div>
</div>

<!-- TEKLIF BELGESI -->
<div class="document">

  <!-- BAŞLIK -->
  <div class="doc-header">
    <div class="header-grid">
      <div class="firma-logo">
        <?php $logo = ayar('firma_logo', ''); ?>
        <?php if ($logo && file_exists(BASE_PATH . '/uploads/logos/' . $logo) && ($teklif['logo_goster'] ?? 1)): ?>
          <img src="<?= BASE_URL ?>/uploads/logos/<?= e($logo) ?>" alt="<?= e($firma_adi) ?>">
          <div style="margin-top:6px;" class="firma-info">
        <?php else: ?>
          <div class="firma-logo-text"><?= e($firma_adi) ?></div>
          <div class="firma-info">
        <?php endif; ?>
          <?php if ($firma_unvan): ?><div><?= e($firma_unvan) ?></div><?php endif; ?>
          <?php if ($firma_adres): ?><div><?= e($firma_adres) ?><?php if ($firma_sehir): ?>, <?= e($firma_sehir) ?><?php endif; ?></div><?php endif; ?>
          <?php if ($firma_telefon): ?><div>Tel: <?= e($firma_telefon) ?></div><?php endif; ?>
          <?php if ($firma_email): ?><div><?= e($firma_email) ?></div><?php endif; ?>
          <?php if ($firma_website): ?><div><?= e($firma_website) ?></div><?php endif; ?>
          <?php if ($firma_vergi): ?><div>Vergi No: <?= e($firma_vergi) ?> — <?= e($firma_vdairesi) ?></div><?php endif; ?>
        </div>
      </div>
      <div class="teklif-baslik">
        <div class="baslik-text"><?= e($teklif['baslik'] ?: 'Fiyat Teklifi') ?></div>
        <div class="teklif-no"><?= e($teklif['teklif_no']) ?></div>
        <div class="teklif-meta">
          <div>Tarih: <?= tarihFormat($teklif['tarih']) ?></div>
          <div>Geçerlilik: <strong><?= tarihFormat($teklif['gecerlilik_tarihi']) ?></strong></div>
          <div>Para Birimi: <?= e($teklif['para_birimi']) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- GÖVDE -->
  <div class="doc-body">

    <!-- Müşteri Bilgisi -->
    <div class="info-grid">
      <div class="info-block">
        <div class="label">Sayın / Alıcı</div>
        <div class="value"><?= e($teklif['firma_adi'] ?: 'Müşteri Seçilmemiş') ?></div>
        <?php if ($teklif['yetkili_kisi']): ?>
          <div class="value-sub">Sayın <?= e($teklif['yetkili_kisi']) ?><?= $teklif['unvan'] ? ', ' . e($teklif['unvan']) : '' ?></div>
        <?php endif; ?>
        <?php if ($teklif['musteri_adres']): ?>
          <div class="value-sub"><?= nl2br(e($teklif['musteri_adres'])) ?><?php if ($teklif['sehir']): ?>, <?= e($teklif['sehir']) ?><?php endif; ?></div>
        <?php endif; ?>
        <?php if ($teklif['musteri_email']): ?>
          <div class="value-sub"><?= e($teklif['musteri_email']) ?></div>
        <?php endif; ?>
        <?php if ($teklif['musteri_telefon']): ?>
          <div class="value-sub">Tel: <?= e($teklif['musteri_telefon']) ?></div>
        <?php endif; ?>
        <?php if ($teklif['vergi_no']): ?>
          <div class="value-sub">VKN: <?= e($teklif['vergi_no']) ?> / <?= e($teklif['vergi_dairesi']) ?></div>
        <?php endif; ?>
      </div>
      <div class="info-block">
        <div class="label">Teklif Detayları</div>
        <div class="value"><?= e($teklif['teklif_no']) ?></div>
        <div class="value-sub">Tarih: <?= tarihFormat($teklif['tarih']) ?></div>
        <div class="value-sub">Geçerlilik: <?= tarihFormat($teklif['gecerlilik_tarihi']) ?></div>
        <div class="value-sub">Para Birimi: <?= e($teklif['para_birimi']) ?></div>
        <?php if ($teklif['gonderim_tarihi']): ?>
          <div class="value-sub">Gönderildi: <?= tarihFormat($teklif['gonderim_tarihi']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Ön Yazı -->
    <?php if ($teklif['on_yazi']): ?>
      <div class="on-yazi"><?= e($teklif['on_yazi']) ?></div>
    <?php endif; ?>

    <!-- Kalem Tablosu -->
    <table class="teklif-table">
      <thead>
        <tr>
          <th width="28">#</th>
          <th>Ürün / Hizmet Açıklaması</th>
          <th class="text-center" width="55">Miktar</th>
          <th class="text-center" width="50">Birim</th>
          <th class="text-right" width="90">Birim Fiyat</th>
          <?php if ($teklif['iskonto_goster'] ?? 1): ?>
          <th class="text-center" width="45">İsk %</th>
          <?php endif; ?>
          <?php if ($teklif['kdv_goster'] ?? 1): ?>
          <th class="text-center" width="45">KDV %</th>
          <?php endif; ?>
          <th class="text-right" width="100">Toplam</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($kalemler as $i => $k): ?>
        <tr>
          <td class="text-center" style="color:#718096;"><?= $i + 1 ?></td>
          <td>
            <div class="kalem-adi"><?= e($k['aciklama']) ?></div>
            <?php if ($k['detay']): ?>
              <div class="kalem-detay"><?= nl2br(e($k['detay'])) ?></div>
            <?php endif; ?>
          </td>
          <td class="text-right"><?= number_format((float)$k['miktar'], 2, ',', '.') ?></td>
          <td class="text-center"><?= e($k['birim']) ?></td>
          <td class="text-right"><?= $sym ?> <?= number_format((float)$k['birim_fiyat'], 2, ',', '.') ?></td>
          <?php if ($teklif['iskonto_goster'] ?? 1): ?>
          <td class="text-center">%<?= number_format((float)$k['iskonto'], 0) ?></td>
          <?php endif; ?>
          <?php if ($teklif['kdv_goster'] ?? 1): ?>
          <td class="text-center">%<?= number_format((float)$k['kdv_orani'], 0) ?></td>
          <?php endif; ?>
          <td class="text-right" style="font-weight:600;"><?= $sym ?> <?= number_format((float)$k['toplam'], 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <?php if ((float)$teklif['iskonto_tutari'] > 0 && ($teklif['iskonto_goster'] ?? 1)): ?>
        <tr class="sum-row">
          <td colspan="<?= 5 + (($teklif['iskonto_goster'] ?? 1) ? 1 : 0) + (($teklif['kdv_goster'] ?? 1) ? 1 : 0) ?>"></td>
          <td style="text-align:right;color:#718096;">Ara Toplam:</td>
          <td class="text-right"><?= $sym ?> <?= number_format((float)$teklif['ara_toplam'], 2, ',', '.') ?></td>
        </tr>
        <tr class="sum-row">
          <td colspan="<?= 5 + (($teklif['iskonto_goster'] ?? 1) ? 1 : 0) + (($teklif['kdv_goster'] ?? 1) ? 1 : 0) ?>"></td>
          <td style="text-align:right;color:#e53e3e;">İskonto:</td>
          <td class="text-right" style="color:#e53e3e;">- <?= $sym ?> <?= number_format((float)$teklif['iskonto_tutari'], 2, ',', '.') ?></td>
        </tr>
        <?php endif; ?>
        <?php if (($teklif['kdv_goster'] ?? 1) && (float)$teklif['kdv_tutari'] > 0): ?>
        <?php foreach ($kdvGruplari as $oran => $tutar): ?>
        <tr class="sum-row">
          <td colspan="<?= 5 + (($teklif['iskonto_goster'] ?? 1) ? 1 : 0) + (($teklif['kdv_goster'] ?? 1) ? 1 : 0) ?>"></td>
          <td style="text-align:right;color:#718096;">KDV %<?= $oran ?>:</td>
          <td class="text-right"><?= $sym ?> <?= number_format($tutar, 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        <tr class="total-row">
          <td colspan="<?= 5 + (($teklif['iskonto_goster'] ?? 1) ? 1 : 0) + (($teklif['kdv_goster'] ?? 1) ? 1 : 0) ?>"></td>
          <td style="text-align:right;">GENEL TOPLAM:</td>
          <td class="text-right"><?= $sym ?> <?= number_format((float)$teklif['genel_toplam'], 2, ',', '.') ?></td>
        </tr>
      </tfoot>
    </table>

    <!-- Son Yazı -->
    <?php if ($teklif['son_yazi']): ?>
      <div class="son-yazi"><?= e($teklif['son_yazi']) ?></div>
    <?php endif; ?>

    <!-- Ödeme Koşulları -->
    <?php if ($teklif['odeme_kosullari']): ?>
      <div class="sartlar-box">
        <div class="baslik">Ödeme Koşulları</div>
        <?= e($teklif['odeme_kosullari']) ?>
      </div>
    <?php endif; ?>

    <!-- Şartlar -->
    <?php if ($teklif['sartlar']): ?>
      <div class="sartlar-box">
        <div class="baslik">Şartlar ve Koşullar</div>
        <?= e($teklif['sartlar']) ?>
      </div>
    <?php endif; ?>

    <!-- İmza Alanı -->
    <?php if ($teklif['imza_alani'] ?? 1): ?>
    <div class="imza-grid">
      <div class="imza-box">
        <div class="imza-alan"></div>
        <div class="imza-label"><?= e($firma_adi) ?></div>
        <div style="font-size:7.5pt;color:#718096;margin-top:2px;">Yetkili İmza / Kaşe</div>
      </div>
      <div class="imza-box">
        <div class="imza-alan"></div>
        <div class="imza-label"><?= e($teklif['firma_adi'] ?: 'Alıcı') ?></div>
        <div style="font-size:7.5pt;color:#718096;margin-top:2px;">Yetkili İmza / Kaşe</div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- FOOTER -->
  <div class="doc-footer">
    <div class="footer-bar"></div>
    <div><?= e($firma_adi) ?><?php if ($firma_website): ?> — <?= e($firma_website) ?><?php endif; ?></div>
    <div><?= e($teklif['teklif_no']) ?> | <?= tarihFormat($teklif['tarih']) ?></div>
    <div>Teklif Sistemi v2.0</div>
  </div>

</div>

<script>
// Kısayol: Ctrl+P veya Cmd+P
document.addEventListener('keydown', e => {
  if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
    e.preventDefault();
    window.print();
  }
});
</script>
</body>
</html>
