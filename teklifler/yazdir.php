<?php
/**
 * HSG Aviation - Teklif Yazdırma / PDF Görünümü
 * Profesyonel çok sayfalı baskı desteği
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

$showIsk = $teklif['iskonto_goster'] ?? 1;
$showKdv = $teklif['kdv_goster'] ?? 1;
$colCount = 5 + ($showIsk ? 1 : 0) + ($showKdv ? 1 : 0);
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

/* === PAGE SETUP === */
@page {
  size: A4 portrait;
  margin: 18mm 15mm 22mm 15mm;
}

body {
  font-family: 'Inter', -apple-system, Arial, sans-serif;
  font-size: 9.5pt;
  color: #1a202c;
  background: #e2e8f0;
  line-height: 1.45;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}

/* === SCREEN WRAPPER === */
.page-wrapper {
  max-width: 210mm;
  margin: 0 auto;
  background: #fff;
}

/* === PRINT TOOLBAR === */
.print-toolbar {
  position: fixed;
  top: 0; left: 0; right: 0;
  background: var(--rena);
  padding: 12px 24px;
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 9999;
  box-shadow: 0 2px 12px rgba(0,0,0,0.3);
}
.print-toolbar button, .print-toolbar a {
  padding: 8px 20px;
  border-radius: 6px;
  border: none;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}
.print-toolbar .btn-print {
  background: var(--raks);
  color: var(--rena);
}
.print-toolbar .btn-print:hover { opacity: 0.9; }
.print-toolbar .btn-back {
  background: rgba(255,255,255,0.12);
  color: #fff;
}
.print-toolbar .btn-back:hover { background: rgba(255,255,255,0.22); }
.print-toolbar .toolbar-hint {
  margin-left: auto;
  color: rgba(255,255,255,0.45);
  font-size: 0.75rem;
}

/* === HEADER BANNER === */
.doc-header {
  background: linear-gradient(135deg, var(--rena) 0%, #1a3050 60%, #243b55 100%);
  padding: 28px 36px 24px;
  color: #fff;
  position: relative;
  overflow: hidden;
}
.doc-header::before {
  content: '';
  position: absolute;
  top: -40px; right: -40px;
  width: 200px; height: 200px;
  background: rgba(255,255,255,0.03);
  border-radius: 50%;
}
.doc-header::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--raks), var(--raks) 40%, transparent);
}

.header-flex {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 24px;
}
.header-left { flex: 1; }
.header-right { text-align: right; flex-shrink: 0; }

/* Firma bilgisi */
.firma-logo img {
  max-height: 50px;
  max-width: 150px;
  object-fit: contain;
  margin-bottom: 8px;
}
.firma-name {
  font-size: 15pt;
  font-weight: 800;
  color: var(--raks);
  letter-spacing: -0.3px;
  margin-bottom: 6px;
}
.firma-details {
  font-size: 7.5pt;
  color: rgba(255,255,255,0.65);
  line-height: 1.6;
}
.firma-details span { display: block; }

/* Teklif başlık */
.doc-type {
  font-size: 22pt;
  font-weight: 800;
  color: var(--raks);
  letter-spacing: -0.5px;
  line-height: 1;
  text-transform: uppercase;
}
.doc-no {
  font-size: 12pt;
  font-weight: 700;
  margin-top: 6px;
  color: #fff;
}
.doc-meta {
  margin-top: 10px;
  font-size: 8pt;
  color: rgba(255,255,255,0.7);
  line-height: 1.7;
}
.doc-meta strong { color: rgba(255,255,255,0.95); font-weight: 600; }

/* === BODY === */
.doc-body {
  padding: 24px 36px 20px;
}

/* Bilgi kutuları */
.info-row {
  display: flex;
  gap: 16px;
  margin-bottom: 20px;
}
.info-box {
  flex: 1;
  border: 1px solid #e8ecf1;
  border-radius: 8px;
  padding: 14px 16px;
  position: relative;
  background: #fff;
}
.info-box::before {
  content: '';
  position: absolute;
  left: 0; top: 0; bottom: 0;
  width: 3px;
  border-radius: 8px 0 0 8px;
}
.info-box.alici::before { background: var(--raks); }
.info-box.detay::before { background: var(--rena); }
.info-box .box-label {
  font-size: 6.5pt;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1.2px;
  color: #94a3b8;
  margin-bottom: 6px;
}
.info-box .box-title {
  font-size: 10.5pt;
  font-weight: 700;
  color: var(--rena);
  margin-bottom: 4px;
}
.info-box .box-line {
  font-size: 8pt;
  color: #64748b;
  line-height: 1.55;
}

/* Ön yazı */
.intro-text {
  font-size: 9pt;
  color: #475569;
  margin-bottom: 18px;
  padding: 12px 16px;
  background: #f8fafc;
  border-left: 3px solid var(--raks);
  border-radius: 0 6px 6px 0;
  line-height: 1.65;
  white-space: pre-line;
}

/* === TABLO === */
.items-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 0;
  font-size: 8.5pt;
}
.items-table thead th {
  background: var(--rena);
  color: rgba(255,255,255,0.92);
  padding: 9px 10px;
  font-size: 7pt;
  font-weight: 700;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  border: none;
  white-space: nowrap;
}
.items-table thead th:first-child { border-radius: 6px 0 0 0; }
.items-table thead th:last-child  { border-radius: 0 6px 0 0; }

.items-table tbody td {
  padding: 9px 10px;
  border-bottom: 1px solid #f0f3f7;
  vertical-align: middle;
}
.items-table tbody tr:nth-child(even) td { background: #fafbfd; }
.items-table tbody tr:hover td { background: #f0f4ff; }

.items-table .col-no { width: 28px; text-align: center; color: #94a3b8; font-weight: 600; }
.items-table .col-desc { }
.items-table .col-qty  { width: 55px; text-align: center; }
.items-table .col-unit { width: 45px; text-align: center; }
.items-table .col-price { width: 85px; text-align: right; }
.items-table .col-disc  { width: 42px; text-align: center; }
.items-table .col-vat   { width: 42px; text-align: center; }
.items-table .col-total { width: 95px; text-align: right; font-weight: 700; color: var(--rena); }

.item-name { font-weight: 600; font-size: 8.5pt; color: #1e293b; }
.item-detail { font-size: 7pt; color: #94a3b8; margin-top: 1px; line-height: 1.4; }

/* Satır bölünmesini önle */
.items-table tbody tr {
  page-break-inside: avoid;
  break-inside: avoid;
}

/* === TOPLAM BÖLÜMÜ === */
.totals-section {
  display: flex;
  justify-content: flex-end;
  margin-top: 2px;
  page-break-inside: avoid;
  break-inside: avoid;
}
.totals-table {
  width: 280px;
  border-collapse: collapse;
  font-size: 8.5pt;
}
.totals-table tr td {
  padding: 7px 12px;
  border-bottom: 1px solid #f0f3f7;
}
.totals-table .t-label {
  color: #64748b;
  text-align: right;
  font-weight: 500;
}
.totals-table .t-value {
  text-align: right;
  font-weight: 600;
  color: #1e293b;
  width: 120px;
}
.totals-table .t-discount .t-label { color: #ef4444; }
.totals-table .t-discount .t-value { color: #ef4444; }
.totals-table .t-grand {
  background: var(--rena);
}
.totals-table .t-grand td {
  padding: 11px 12px;
  border: none;
  font-size: 11pt;
  font-weight: 800;
}
.totals-table .t-grand .t-label { color: rgba(255,255,255,0.85); }
.totals-table .t-grand .t-value { color: var(--raks); }

/* === ALT BİLGİLER === */
.closing-text {
  margin-top: 20px;
  font-size: 9pt;
  color: #475569;
  line-height: 1.6;
  white-space: pre-line;
}

.terms-box {
  margin-top: 16px;
  background: #f8fafc;
  border: 1px solid #e8ecf1;
  border-radius: 8px;
  padding: 14px 16px;
  page-break-inside: avoid;
  break-inside: avoid;
}
.terms-box .terms-title {
  font-size: 7pt;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--rena);
  margin-bottom: 6px;
  padding-bottom: 5px;
  border-bottom: 1px solid #e2e8f0;
}
.terms-box .terms-content {
  font-size: 7.5pt;
  color: #64748b;
  line-height: 1.55;
  white-space: pre-line;
}

/* İmza alanı */
.signature-row {
  display: flex;
  gap: 40px;
  margin-top: 36px;
  page-break-inside: avoid;
  break-inside: avoid;
}
.signature-box {
  flex: 1;
  text-align: center;
}
.sig-space {
  height: 60px;
  border-bottom: 2px solid var(--rena);
  margin-bottom: 8px;
}
.sig-name {
  font-size: 8.5pt;
  font-weight: 700;
  color: var(--rena);
}
.sig-hint {
  font-size: 7pt;
  color: #94a3b8;
  margin-top: 2px;
}

/* Banka bilgileri */
.bank-info {
  margin-top: 20px;
  padding: 12px 16px;
  background: #f8fafc;
  border: 1px solid #e8ecf1;
  border-radius: 8px;
  font-size: 7.5pt;
  color: #64748b;
  page-break-inside: avoid;
  break-inside: avoid;
}
.bank-info .bank-title {
  font-size: 7pt;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--rena);
  margin-bottom: 4px;
}

/* === FOOTER === */
.doc-footer {
  margin-top: 30px;
  padding: 14px 36px;
  background: var(--rena);
  color: rgba(255,255,255,0.5);
  font-size: 7pt;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
}
.doc-footer::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--raks);
}

/* === PRINT === */
@media print {
  .print-toolbar { display: none !important; }
  body {
    background: #fff;
    padding: 0;
    margin: 0;
  }
  .page-wrapper {
    max-width: none;
    box-shadow: none;
  }

  /* Devam eden sayfalarda tablo başlığı tekrarlansın */
  .items-table thead {
    display: table-header-group;
  }
  .items-table tfoot {
    display: table-footer-group;
  }

  /* Header & footer sadece bir kez */
  .doc-header {
    page-break-after: avoid;
  }

  .doc-footer {
    margin-top: auto;
  }

  /* Toplamlar bölünmesin */
  .totals-section,
  .terms-box,
  .signature-row,
  .bank-info {
    page-break-inside: avoid;
    break-inside: avoid;
  }
}

/* === SCREEN ONLY === */
@media screen {
  body { padding-top: 60px; }
  .page-wrapper {
    margin: 20px auto;
    box-shadow: 0 4px 30px rgba(0,0,0,0.12);
    border-radius: 4px;
    overflow: hidden;
  }
}

/* === RESPONSIVE === */
@media screen and (max-width: 700px) {
  .header-flex { flex-direction: column; gap: 16px; }
  .header-right { text-align: left; }
  .info-row { flex-direction: column; }
  .doc-body { padding: 16px 20px; }
  .doc-header { padding: 20px; }
  .signature-row { flex-direction: column; gap: 20px; }
}
</style>
</head>
<body>

<!-- Yazdırma Araç Çubuğu -->
<div class="print-toolbar">
  <button onclick="window.print()" class="btn-print">
    &#128424; Yazdir / PDF
  </button>
  <a href="goruntule.php?id=<?= $id ?>" class="btn-back">&larr; Geri</a>
  <a href="duzenle.php?id=<?= $id ?>" class="btn-back">&#9998; Duzenle</a>
  <span class="toolbar-hint">PDF kaydetmek icin: Yazdir &rarr; "PDF olarak kaydet"</span>
</div>

<div class="page-wrapper">

  <!-- ===================== HEADER ===================== -->
  <div class="doc-header">
    <div class="header-flex">
      <div class="header-left">
        <?php $logo = ayar('firma_logo', ''); ?>
        <?php if ($logo && file_exists(BASE_PATH . '/uploads/logos/' . $logo) && ($teklif['logo_goster'] ?? 1)): ?>
          <div class="firma-logo">
            <img src="<?= BASE_URL ?>/uploads/logos/<?= e($logo) ?>" alt="<?= e($firma_adi) ?>">
          </div>
        <?php else: ?>
          <div class="firma-name"><?= e($firma_adi) ?></div>
        <?php endif; ?>
        <div class="firma-details">
          <?php if ($firma_unvan): ?><span><?= e($firma_unvan) ?></span><?php endif; ?>
          <?php if ($firma_adres): ?><span><?= e($firma_adres) ?><?php if ($firma_sehir): ?>, <?= e($firma_sehir) ?><?php endif; ?></span><?php endif; ?>
          <?php if ($firma_telefon): ?><span>Tel: <?= e($firma_telefon) ?><?php if ($firma_email): ?> | <?= e($firma_email) ?><?php endif; ?></span><?php endif; ?>
          <?php if ($firma_website): ?><span><?= e($firma_website) ?></span><?php endif; ?>
          <?php if ($firma_vergi): ?><span>VKN: <?= e($firma_vergi) ?> / <?= e($firma_vdairesi) ?></span><?php endif; ?>
        </div>
      </div>
      <div class="header-right">
        <div class="doc-type"><?= e($teklif['baslik'] ?: 'Fiyat Teklifi') ?></div>
        <div class="doc-no"><?= e($teklif['teklif_no']) ?></div>
        <div class="doc-meta">
          Tarih: <strong><?= tarihFormat($teklif['tarih']) ?></strong><br>
          Gecerlilik: <strong><?= tarihFormat($teklif['gecerlilik_tarihi']) ?></strong><br>
          Para Birimi: <strong><?= e($teklif['para_birimi']) ?></strong>
        </div>
      </div>
    </div>
  </div>

  <!-- ===================== BODY ===================== -->
  <div class="doc-body">

    <!-- Alıcı & Detay Bilgisi -->
    <div class="info-row">
      <div class="info-box alici">
        <div class="box-label">Sayin / Alici</div>
        <div class="box-title"><?= e($teklif['firma_adi'] ?: 'Musteri Secilmemis') ?></div>
        <?php if ($teklif['yetkili_kisi']): ?>
          <div class="box-line">Sayin <?= e($teklif['yetkili_kisi']) ?><?= $teklif['unvan'] ? ' - ' . e($teklif['unvan']) : '' ?></div>
        <?php endif; ?>
        <?php if ($teklif['musteri_adres']): ?>
          <div class="box-line"><?= nl2br(e($teklif['musteri_adres'])) ?><?php if ($teklif['sehir']): ?>, <?= e($teklif['sehir']) ?><?php endif; ?></div>
        <?php endif; ?>
        <?php if ($teklif['musteri_telefon']): ?>
          <div class="box-line">Tel: <?= e($teklif['musteri_telefon']) ?></div>
        <?php endif; ?>
        <?php if ($teklif['musteri_email']): ?>
          <div class="box-line"><?= e($teklif['musteri_email']) ?></div>
        <?php endif; ?>
        <?php if ($teklif['vergi_no']): ?>
          <div class="box-line">VKN: <?= e($teklif['vergi_no']) ?> / <?= e($teklif['vergi_dairesi']) ?></div>
        <?php endif; ?>
      </div>
      <div class="info-box detay">
        <div class="box-label">Teklif Detaylari</div>
        <div class="box-title"><?= e($teklif['teklif_no']) ?></div>
        <div class="box-line">Duzenleme Tarihi: <?= tarihFormat($teklif['tarih']) ?></div>
        <div class="box-line">Gecerlilik Tarihi: <?= tarihFormat($teklif['gecerlilik_tarihi']) ?></div>
        <div class="box-line">Para Birimi: <?= e($teklif['para_birimi']) ?> (<?= $sym ?>)</div>
        <?php if ($teklif['gonderim_tarihi']): ?>
          <div class="box-line">Gonderim: <?= tarihFormat($teklif['gonderim_tarihi']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Ön Yazı -->
    <?php if ($teklif['on_yazi']): ?>
      <div class="intro-text"><?= nl2br(e($teklif['on_yazi'])) ?></div>
    <?php endif; ?>

    <!-- =================== KALEM TABLOSU =================== -->
    <table class="items-table">
      <thead>
        <tr>
          <th class="col-no">#</th>
          <th>Urun / Hizmet Aciklamasi</th>
          <th class="col-qty">Miktar</th>
          <th class="col-unit">Birim</th>
          <th class="col-price">Birim Fiyat</th>
          <?php if ($showIsk): ?><th class="col-disc">Isk.</th><?php endif; ?>
          <?php if ($showKdv): ?><th class="col-vat">KDV</th><?php endif; ?>
          <th class="col-total">Toplam</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($kalemler as $i => $k): ?>
        <tr>
          <td class="col-no"><?= $i + 1 ?></td>
          <td>
            <div class="item-name"><?= e($k['aciklama']) ?></div>
            <?php if ($k['detay']): ?>
              <div class="item-detail"><?= nl2br(e($k['detay'])) ?></div>
            <?php endif; ?>
          </td>
          <td class="col-qty"><?= number_format((float)$k['miktar'], 2, ',', '.') ?></td>
          <td class="col-unit"><?= e($k['birim']) ?></td>
          <td class="col-price"><?= $sym ?> <?= number_format((float)$k['birim_fiyat'], 2, ',', '.') ?></td>
          <?php if ($showIsk): ?>
          <td class="col-disc"><?= (float)$k['iskonto'] > 0 ? '%' . number_format((float)$k['iskonto'], 0) : '-' ?></td>
          <?php endif; ?>
          <?php if ($showKdv): ?>
          <td class="col-vat">%<?= number_format((float)$k['kdv_orani'], 0) ?></td>
          <?php endif; ?>
          <td class="col-total"><?= $sym ?> <?= number_format((float)$k['toplam'], 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- =================== TOPLAM =================== -->
    <div class="totals-section">
      <table class="totals-table">
        <?php if ((float)$teklif['ara_toplam'] != (float)$teklif['genel_toplam'] || (float)$teklif['iskonto_tutari'] > 0): ?>
        <tr>
          <td class="t-label">Ara Toplam</td>
          <td class="t-value"><?= $sym ?> <?= number_format((float)$teklif['ara_toplam'], 2, ',', '.') ?></td>
        </tr>
        <?php endif; ?>

        <?php if ((float)$teklif['iskonto_tutari'] > 0 && $showIsk): ?>
        <tr class="t-discount">
          <td class="t-label">Iskonto</td>
          <td class="t-value">- <?= $sym ?> <?= number_format((float)$teklif['iskonto_tutari'], 2, ',', '.') ?></td>
        </tr>
        <?php endif; ?>

        <?php if ($showKdv && count($kdvGruplari) > 0): ?>
          <?php foreach ($kdvGruplari as $oran => $tutar): ?>
          <tr>
            <td class="t-label">KDV %<?= $oran ?></td>
            <td class="t-value"><?= $sym ?> <?= number_format($tutar, 2, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>

        <tr class="t-grand">
          <td class="t-label">GENEL TOPLAM</td>
          <td class="t-value"><?= $sym ?> <?= number_format((float)$teklif['genel_toplam'], 2, ',', '.') ?></td>
        </tr>
      </table>
    </div>

    <!-- Son Yazı -->
    <?php if ($teklif['son_yazi']): ?>
      <div class="closing-text"><?= nl2br(e($teklif['son_yazi'])) ?></div>
    <?php endif; ?>

    <!-- Ödeme Koşulları -->
    <?php if ($teklif['odeme_kosullari']): ?>
      <div class="terms-box">
        <div class="terms-title">Odeme Kosullari</div>
        <div class="terms-content"><?= nl2br(e($teklif['odeme_kosullari'])) ?></div>
      </div>
    <?php endif; ?>

    <!-- Şartlar -->
    <?php if ($teklif['sartlar']): ?>
      <div class="terms-box">
        <div class="terms-title">Sartlar ve Kosullar</div>
        <div class="terms-content"><?= nl2br(e($teklif['sartlar'])) ?></div>
      </div>
    <?php endif; ?>

    <!-- Banka Bilgileri -->
    <?php if ($firma_iban): ?>
    <div class="bank-info">
      <div class="bank-title">Banka / Odeme Bilgileri</div>
      <div>IBAN: <?= e($firma_iban) ?></div>
      <?php $firma_banka = ayar('firma_banka', ''); if ($firma_banka): ?>
        <div>Banka: <?= e($firma_banka) ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- İmza Alanı -->
    <?php if ($teklif['imza_alani'] ?? 1): ?>
    <div class="signature-row">
      <div class="signature-box">
        <div class="sig-space"></div>
        <div class="sig-name"><?= e($firma_adi) ?></div>
        <div class="sig-hint">Yetkili Imza / Kase</div>
      </div>
      <div class="signature-box">
        <div class="sig-space"></div>
        <div class="sig-name"><?= e($teklif['firma_adi'] ?: 'Alici') ?></div>
        <div class="sig-hint">Yetkili Imza / Kase</div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- ===================== FOOTER ===================== -->
  <div class="doc-footer">
    <div><?= e($firma_adi) ?><?php if ($firma_website): ?> &mdash; <?= e($firma_website) ?><?php endif; ?></div>
    <div><?= e($teklif['teklif_no']) ?> | <?= tarihFormat($teklif['tarih']) ?></div>
    <div>Teklif Sistemi v2.0</div>
  </div>

</div>

<script>
document.addEventListener('keydown', function(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
    e.preventDefault();
    window.print();
  }
});
</script>
</body>
</html>
