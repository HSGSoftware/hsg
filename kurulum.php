<?php
/**
 * HSG Aviation - Teklif Yönetim Sistemi
 * Kurulum Sihirbazı v2.0
 */
define('KURULUM', true);

// Session MUTLAKA başlatılmalı
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$adim = (int)($_GET['adim'] ?? 1);
$hata = '';
$basari = '';

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($adim === 1) {
        // Veritabanı bağlantı testi
        $host   = trim($_POST['db_host'] ?? 'localhost');
        $user   = trim($_POST['db_user'] ?? '');
        $pass   = $_POST['db_pass'] ?? '';
        $dbname = trim($_POST['db_name'] ?? 'hsg_teklif');

        try {
            $testPdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            // Veritabanı oluştur
            $testPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $testPdo->exec("USE `$dbname`");

            // Config dosyası yaz (kurulum sonrası kullanılacak)
            $dbHostExp   = var_export($host,   true);
            $dbUserExp   = var_export($user,   true);
            $dbPassExp   = var_export($pass,   true);
            $dbnameExp   = var_export($dbname, true);
            $configContent = <<<PHPEOF
<?php
// Kurulum kontrolü
if (!file_exists(__DIR__ . '/.installed') && basename(\$_SERVER['PHP_SELF'] ?? '') !== 'kurulum.php') {
    header('Location: kurulum.php');
    exit;
}
define('DB_HOST',    $dbHostExp);
define('DB_USER',    $dbUserExp);
define('DB_PASS',    $dbPassExp);
define('DB_NAME',    $dbnameExp);
define('DB_CHARSET', 'utf8mb4');
define('APP_NAME',    'HSG Aviation Teklif Sistemi');
define('APP_VERSION', '2.0.0');
define('BASE_PATH', __DIR__);
\$_p = (isset(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
\$_h = \$_SERVER['HTTP_HOST'] ?? 'localhost';
\$_d = rtrim(str_replace(['\\\\', '/index.php'], ['/', ''], dirname(\$_SERVER['SCRIPT_NAME'] ?? '')), '/');
define('BASE_URL', \$_p . '://' . \$_h . \$_d);
date_default_timezone_set('Europe/Istanbul');
error_reporting(0);
ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
define('CURRENCIES', json_encode([
    'TRY' => ['symbol' => '₺', 'name' => 'Türk Lirası'],
    'USD' => ['symbol' => '$', 'name' => 'Amerikan Doları'],
    'EUR' => ['symbol' => '€', 'name' => 'Euro'],
    'GBP' => ['symbol' => '£', 'name' => 'İngiliz Sterlini'],
]));
define('KDV_ORANLARI', json_encode([0, 1, 8, 10, 18, 20]));
PHPEOF;
            file_put_contents(__DIR__ . '/config.php', $configContent);
            $_SESSION['kurulum_db'] = ['host' => $host, 'user' => $user, 'pass' => $pass, 'name' => $dbname];
            header('Location: kurulum.php?adim=2');
            exit;
        } catch (PDOException $e) {
            $hata = 'Bağlantı hatası: ' . $e->getMessage();
        }
    } elseif ($adim === 2) {
        // Tabloları oluştur
        $db = $_SESSION['kurulum_db'] ?? null;
        if (!$db) { header('Location: kurulum.php?adim=1'); exit; }

        try {
            $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4", $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            $sql = "
-- Ayarlar
CREATE TABLE IF NOT EXISTS `ayarlar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anahtar` varchar(100) NOT NULL,
  `deger` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `anahtar` (`anahtar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kategoriler
CREATE TABLE IF NOT EXISTS `kategoriler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad` varchar(100) NOT NULL,
  `aciklama` text,
  `sira` int(11) DEFAULT 0,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Müşteriler
CREATE TABLE IF NOT EXISTS `musteriler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `musteri_no` varchar(20) NOT NULL,
  `firma_adi` varchar(200) NOT NULL,
  `yetkili_kisi` varchar(100) DEFAULT NULL,
  `unvan` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefon` varchar(50) DEFAULT NULL,
  `telefon2` varchar(50) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL,
  `adres` text,
  `ilce` varchar(100) DEFAULT NULL,
  `sehir` varchar(100) DEFAULT NULL,
  `il` varchar(100) DEFAULT NULL,
  `posta_kodu` varchar(20) DEFAULT NULL,
  `ulke` varchar(100) DEFAULT 'Türkiye',
  `vergi_no` varchar(50) DEFAULT NULL,
  `vergi_dairesi` varchar(100) DEFAULT NULL,
  `banka_adi` varchar(100) DEFAULT NULL,
  `iban` varchar(50) DEFAULT NULL,
  `notlar` text,
  `durum` enum('aktif','pasif') NOT NULL DEFAULT 'aktif',
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `musteri_no` (`musteri_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ürünler
CREATE TABLE IF NOT EXISTS `urunler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `urun_kodu` varchar(50) DEFAULT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `ad` varchar(200) NOT NULL,
  `aciklama` text,
  `birim` varchar(50) DEFAULT 'Adet',
  `birim_fiyat` decimal(15,2) NOT NULL DEFAULT 0.00,
  `para_birimi` varchar(10) DEFAULT 'TRY',
  `kdv_orani` decimal(5,2) DEFAULT 18.00,
  `min_miktar` decimal(10,2) DEFAULT 1.00,
  `stok_durumu` enum('var','yok','sorulacak') DEFAULT 'var',
  `durum` enum('aktif','pasif') DEFAULT 'aktif',
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `kategori_id` (`kategori_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teklif Şablonları
CREATE TABLE IF NOT EXISTS `sablonlar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad` varchar(100) NOT NULL,
  `baslik` varchar(200) DEFAULT NULL,
  `on_yazi` text,
  `son_yazi` text,
  `sartlar` text,
  `odeme_kosullari` text,
  `renk_ana` varchar(20) DEFAULT '#0a1628',
  `renk_aksan` varchar(20) DEFAULT '#e8a000',
  `logo_goster` tinyint(1) DEFAULT 1,
  `imza_alani` tinyint(1) DEFAULT 1,
  `kdv_goster` tinyint(1) DEFAULT 1,
  `iskonto_goster` tinyint(1) DEFAULT 1,
  `kalem_aciklama_goster` tinyint(1) DEFAULT 1,
  `gecerlilik_gun` int(11) DEFAULT 30,
  `varsayilan` tinyint(1) DEFAULT 0,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teklifler
CREATE TABLE IF NOT EXISTS `teklifler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teklif_no` varchar(30) NOT NULL,
  `musteri_id` int(11) DEFAULT NULL,
  `sablon_id` int(11) DEFAULT NULL,
  `baslik` varchar(200) DEFAULT NULL,
  `tarih` date NOT NULL,
  `gecerlilik_tarihi` date DEFAULT NULL,
  `para_birimi` varchar(10) DEFAULT 'TRY',
  `kur` decimal(10,4) DEFAULT 1.0000,
  `genel_iskonto` decimal(5,2) DEFAULT 0.00,
  `ara_toplam` decimal(15,2) DEFAULT 0.00,
  `iskonto_tutari` decimal(15,2) DEFAULT 0.00,
  `kdv_tutari` decimal(15,2) DEFAULT 0.00,
  `genel_toplam` decimal(15,2) DEFAULT 0.00,
  `durum` enum('taslak','gonderildi','kabul','red','suresi_doldu') DEFAULT 'taslak',
  `on_yazi` text,
  `son_yazi` text,
  `sartlar` text,
  `odeme_kosullari` text,
  `ic_notlar` text,
  `gonderim_tarihi` datetime DEFAULT NULL,
  `kabul_tarihi` datetime DEFAULT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teklif_no` (`teklif_no`),
  KEY `musteri_id` (`musteri_id`),
  KEY `sablon_id` (`sablon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teklif Kalemleri
CREATE TABLE IF NOT EXISTS `teklif_kalemleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teklif_id` int(11) NOT NULL,
  `urun_id` int(11) DEFAULT NULL,
  `sira` int(11) DEFAULT 0,
  `aciklama` varchar(500) NOT NULL,
  `detay` text,
  `miktar` decimal(10,2) NOT NULL DEFAULT 1.00,
  `birim` varchar(50) DEFAULT 'Adet',
  `birim_fiyat` decimal(15,2) NOT NULL DEFAULT 0.00,
  `iskonto` decimal(5,2) DEFAULT 0.00,
  `kdv_orani` decimal(5,2) DEFAULT 18.00,
  `ara_toplam` decimal(15,2) DEFAULT 0.00,
  `iskonto_tutari` decimal(15,2) DEFAULT 0.00,
  `kdv_tutari` decimal(15,2) DEFAULT 0.00,
  `toplam` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `teklif_id` (`teklif_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teklif Geçmişi
CREATE TABLE IF NOT EXISTS `teklif_gecmisi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teklif_id` int(11) NOT NULL,
  `eski_durum` varchar(50) DEFAULT NULL,
  `yeni_durum` varchar(50) NOT NULL,
  `notlar` text,
  `tarih` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `teklif_id` (`teklif_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

";

            // Her CREATE TABLE sorgusunu ayrı ayrı çalıştır
            $queries = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($queries as $q) {
                // SQL yorumlarını ve boş satırları temizle
                $q = preg_replace('/^--.*$/m', '', $q);
                $q = trim($q);
                if (!empty($q)) {
                    $pdo->exec($q);
                }
            }

            // Foreign Key kısıtlamalarını ayrı try/catch bloklarıyla ekle
            // (MySQL sürümüne göre hata verirse geç)
            $fkSorguları = [
                "ALTER TABLE `urunler` ADD CONSTRAINT `urunler_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategoriler` (`id`) ON DELETE SET NULL",
                "ALTER TABLE `teklifler` ADD CONSTRAINT `teklifler_ibfk_1` FOREIGN KEY (`musteri_id`) REFERENCES `musteriler` (`id`) ON DELETE SET NULL",
                "ALTER TABLE `teklifler` ADD CONSTRAINT `teklifler_ibfk_2` FOREIGN KEY (`sablon_id`) REFERENCES `sablonlar` (`id`) ON DELETE SET NULL",
                "ALTER TABLE `teklif_kalemleri` ADD CONSTRAINT `tklm_ibfk_1` FOREIGN KEY (`teklif_id`) REFERENCES `teklifler` (`id`) ON DELETE CASCADE",
                "ALTER TABLE `teklif_kalemleri` ADD CONSTRAINT `tklm_ibfk_2` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`) ON DELETE SET NULL",
                "ALTER TABLE `teklif_gecmisi` ADD CONSTRAINT `tgecmis_ibfk_1` FOREIGN KEY (`teklif_id`) REFERENCES `teklifler` (`id`) ON DELETE CASCADE",
            ];
            foreach ($fkSorguları as $fk) {
                try {
                    $pdo->exec($fk);
                } catch (PDOException $fkErr) {
                    // FK zaten varsa (errno 1826 veya 1050) geç, diğer hataları görmezden gel
                    // Tablolar zaten oluştu, FK olmasa da sistem çalışır
                }
            }

            // Varsayılan ayarları ekle
            $defaultAyarlar = [
                'firma_adi'              => 'HSG Aviation',
                'firma_unvan'            => 'Orsan OPS Sanayi ve Ticaret A.Ş.',
                'firma_adres'            => 'İstanbul Trakya Free Zone, Ferhatpaşa Free Zone District, Ali Rıza Efendi Cad. No:27, Çatalca 34540 / İstanbul',
                'firma_sehir'            => 'İstanbul',
                'firma_ulke'             => 'Türkiye',
                'firma_telefon'          => '',
                'firma_email'            => '',
                'firma_website'          => 'https://hsgaviation.com',
                'firma_vergi_no'         => '',
                'firma_vergi_dairesi'    => '',
                'firma_iban'             => '',
                'firma_logo'             => '',
                'varsayilan_para_birimi' => 'TRY',
                'varsayilan_kdv'         => '18',
                'teklif_prefix'          => 'TKL',
                'gecerlilik_gun'         => '30',
                'firma_slogan'           => 'Havacılık ve Savunma Sanayii Çözümleri',
            ];

            $stmt = $pdo->prepare("INSERT IGNORE INTO ayarlar (anahtar, deger) VALUES (?, ?)");
            foreach ($defaultAyarlar as $k => $v) {
                $stmt->execute([$k, $v]);
            }

            // Varsayılan şablon
            $pdo->exec("INSERT IGNORE INTO sablonlar (id, ad, baslik, on_yazi, son_yazi, sartlar, odeme_kosullari, varsayilan) VALUES
            (1, 'Standart Teklif', 'FİYAT TEKLİFİ',
            'Sayın İlgili,\n\nAşağıda belirtilen ürün/hizmetlere ait fiyat teklifimizi bilgilerinize sunarız.',
            'Teklifimizi değerlendirmenizi ve olumlu yanıtınızı bekliyoruz.\n\nSaygılarımızla,\nHSG Aviation Ekibi',
            '1. Bu teklif yukarıda belirtilen geçerlilik tarihine kadar geçerlidir.\n2. Fiyatlar KDV hariçtir, aksi belirtilmedikçe.\n3. Teslimat süresi sipariş onayından sonra bildirilecektir.\n4. Ödeme koşulları ayrıca belirlenecektir.',
            'Peşin veya anlaşmaya göre',
            1)");

            // Örnek kategoriler
            $pdo->exec("INSERT IGNORE INTO kategoriler (id, ad, aciklama) VALUES
            (1, 'Yapısal Parçalar', 'Havacılık ve savunma yapısal gövde parçaları'),
            (2, 'İHA / Drone', 'İnsansız hava aracı ve drone bileşenleri'),
            (3, 'Mühendislik Hizmetleri', 'Tasarım ve mühendislik hizmetleri'),
            (4, 'Bakım Onarım', 'Bakım, onarım ve revizyon hizmetleri'),
            (5, 'Dikiş Makinesi Parçaları', 'Dikiş makinesi yedek parçaları')");

            // .installed dosyası oluştur
            file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));

            $_SESSION['kurulum_tamam'] = true;
            header('Location: kurulum.php?adim=3');
            exit;
        } catch (PDOException $e) {
            $hata = 'Tablo oluşturma hatası: ' . $e->getMessage();
        }
    } elseif ($adim === 3) {
        // Firma bilgileri
        $db = $_SESSION['kurulum_db'] ?? null;
        if (!$db) { header('Location: kurulum.php?adim=1'); exit; }

        // config.php yeniden yazıldıktan sonra include et
        if (!defined('DB_HOST')) {
            require_once __DIR__ . '/config.php';
        }
        require_once __DIR__ . '/includes/db.php';
        require_once __DIR__ . '/includes/functions.php';

        $kaydet = [
            'firma_adi'           => trim($_POST['firma_adi'] ?? ''),
            'firma_unvan'         => trim($_POST['firma_unvan'] ?? ''),
            'firma_adres'         => trim($_POST['firma_adres'] ?? ''),
            'firma_sehir'         => trim($_POST['firma_sehir'] ?? ''),
            'firma_ulke'          => trim($_POST['firma_ulke'] ?? 'Türkiye'),
            'firma_telefon'       => trim($_POST['firma_telefon'] ?? ''),
            'firma_email'         => trim($_POST['firma_email'] ?? ''),
            'firma_website'       => trim($_POST['firma_website'] ?? ''),
            'firma_vergi_no'      => trim($_POST['firma_vergi_no'] ?? ''),
            'firma_vergi_dairesi' => trim($_POST['firma_vergi_dairesi'] ?? ''),
            'teklif_prefix'       => strtoupper(trim($_POST['teklif_prefix'] ?? 'TKL')),
        ];

        foreach ($kaydet as $k => $v) {
            ayarKaydet($k, $v);
        }

        // Logo yükleme
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            try {
                $logoAd = dosyaYukle($_FILES['logo'], 'uploads/logos');
                ayarKaydet('firma_logo', $logoAd);
            } catch (Exception $e) {
                $hata = 'Logo yükleme hatası: ' . $e->getMessage();
            }
        }

        if (!$hata) {
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HSG Aviation - Sistem Kurulumu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --hsg-dark: #0a1628;
    --hsg-navy: #162c4a;
    --hsg-gold: #e8a000;
    --hsg-gold-light: #f0b429;
  }
  * { box-sizing: border-box; }
  body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, var(--hsg-dark) 0%, var(--hsg-navy) 60%, #1a3a5c 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .setup-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 25px 60px rgba(0,0,0,0.4);
    overflow: hidden;
    width: 100%;
    max-width: 680px;
  }
  .setup-header {
    background: linear-gradient(135deg, var(--hsg-dark), var(--hsg-navy));
    padding: 40px 40px 30px;
    text-align: center;
    color: #fff;
    position: relative;
    overflow: hidden;
  }
  .setup-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(232,160,0,0.1) 0%, transparent 60%);
    animation: pulse 4s ease-in-out infinite;
  }
  @keyframes pulse { 0%,100%{opacity:0.5} 50%{opacity:1} }
  .setup-logo {
    font-size: 3rem;
    margin-bottom: 10px;
  }
  .setup-title {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--hsg-gold);
    letter-spacing: -0.5px;
  }
  .setup-subtitle {
    font-size: 0.9rem;
    opacity: 0.8;
    margin-top: 5px;
  }
  .steps-nav {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 0;
    list-style: none;
    margin: 0;
  }
  .steps-nav li {
    flex: 1;
    text-align: center;
    padding: 14px 10px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #adb5bd;
    border-bottom: 3px solid transparent;
    position: relative;
  }
  .steps-nav li.active {
    color: var(--hsg-dark);
    border-bottom-color: var(--hsg-gold);
  }
  .steps-nav li.done {
    color: #28a745;
    border-bottom-color: #28a745;
  }
  .steps-nav li .step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #dee2e6;
    color: #6c757d;
    font-size: 0.75rem;
    margin-right: 6px;
  }
  .steps-nav li.active .step-num { background: var(--hsg-gold); color: #fff; }
  .steps-nav li.done .step-num { background: #28a745; color: #fff; }
  .setup-body {
    padding: 35px 40px;
  }
  .btn-hsg {
    background: linear-gradient(135deg, var(--hsg-dark), var(--hsg-navy));
    color: #fff;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s;
    width: 100%;
  }
  .btn-hsg:hover {
    background: linear-gradient(135deg, var(--hsg-navy), #1e4a7a);
    color: var(--hsg-gold);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
  }
  .form-control:focus, .form-select:focus {
    border-color: var(--hsg-gold);
    box-shadow: 0 0 0 0.25rem rgba(232,160,0,0.2);
  }
  .form-label { font-weight: 500; font-size: 0.9rem; color: #495057; }
  .req { color: #dc3545; }
  .section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--hsg-dark);
    border-bottom: 2px solid var(--hsg-gold);
    padding-bottom: 8px;
    margin-bottom: 20px;
  }
  .step3-success {
    text-align: center;
    padding: 20px;
  }
  .success-icon { font-size: 5rem; color: #28a745; }
</style>
</head>
<body>
<div class="setup-card">
  <div class="setup-header">
    <div class="setup-logo">✈️</div>
    <div class="setup-title">HSG Aviation</div>
    <div class="setup-subtitle">Teklif Yönetim Sistemi - Kurulum Sihirbazı</div>
  </div>

  <ul class="steps-nav">
    <li class="<?= $adim === 1 ? 'active' : ($adim > 1 ? 'done' : '') ?>">
      <span class="step-num"><?= $adim > 1 ? '✓' : '1' ?></span>Veritabanı
    </li>
    <li class="<?= $adim === 2 ? 'active' : ($adim > 2 ? 'done' : '') ?>">
      <span class="step-num"><?= $adim > 2 ? '✓' : '2' ?></span>Tablolar
    </li>
    <li class="<?= $adim === 3 ? 'active' : '' ?>">
      <span class="step-num">3</span>Firma Bilgileri
    </li>
  </ul>

  <div class="setup-body">
    <?php if ($hata): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($hata) ?></div>
    <?php endif; ?>

    <?php if ($adim === 1): ?>
    <h5 class="section-title"><i class="bi bi-database me-2"></i>Veritabanı Bağlantı Ayarları</h5>
    <form method="POST">
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">Sunucu Adresi <span class="req">*</span></label>
          <input type="text" name="db_host" class="form-control" value="localhost" required>
          <div class="form-text">Genellikle "localhost" kullanılır</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Veritabanı Adı <span class="req">*</span></label>
          <input type="text" name="db_name" class="form-control" value="hsg_teklif" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Kullanıcı Adı <span class="req">*</span></label>
          <input type="text" name="db_user" class="form-control" value="root" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Şifre</label>
          <input type="password" name="db_pass" class="form-control" placeholder="(boş bırakılabilir)">
        </div>
      </div>
      <div class="alert alert-info mt-3 small">
        <i class="bi bi-info-circle me-2"></i>
        Veritabanı yoksa otomatik oluşturulacaktır. MySQL kullanıcısının veritabanı oluşturma yetkisi olması gerekir.
      </div>
      <div class="mt-4">
        <button type="submit" class="btn-hsg">
          <i class="bi bi-arrow-right-circle me-2"></i>Bağlantıyı Test Et ve Devam Et
        </button>
      </div>
    </form>

    <?php elseif ($adim === 2): ?>
    <h5 class="section-title"><i class="bi bi-table me-2"></i>Veritabanı Tabloları Oluşturuluyor</h5>
    <p class="text-muted">Sistem tabloları ve varsayılan veriler oluşturulacaktır:</p>
    <ul class="list-group mb-3">
      <li class="list-group-item d-flex align-items-center">
        <i class="bi bi-check2-circle text-success me-3 fs-5"></i>
        <div><strong>Müşteriler</strong> tablosu</div>
      </li>
      <li class="list-group-item d-flex align-items-center">
        <i class="bi bi-check2-circle text-success me-3 fs-5"></i>
        <div><strong>Ürünler & Kategoriler</strong> tablosu</div>
      </li>
      <li class="list-group-item d-flex align-items-center">
        <i class="bi bi-check2-circle text-success me-3 fs-5"></i>
        <div><strong>Teklifler & Teklif Kalemleri</strong> tablosu</div>
      </li>
      <li class="list-group-item d-flex align-items-center">
        <i class="bi bi-check2-circle text-success me-3 fs-5"></i>
        <div><strong>Teklif Şablonları</strong> tablosu</div>
      </li>
      <li class="list-group-item d-flex align-items-center">
        <i class="bi bi-check2-circle text-success me-3 fs-5"></i>
        <div><strong>Sistem Ayarları</strong> ve varsayılan veriler</div>
      </li>
    </ul>
    <form method="POST">
      <button type="submit" class="btn-hsg">
        <i class="bi bi-database-fill-gear me-2"></i>Tabloları Oluştur ve Devam Et
      </button>
    </form>

    <?php elseif ($adim === 3): ?>
    <h5 class="section-title"><i class="bi bi-building me-2"></i>Firma Bilgilerinizi Girin</h5>
    <form method="POST" enctype="multipart/form-data">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Firma Adı <span class="req">*</span></label>
          <input type="text" name="firma_adi" class="form-control" value="HSG Aviation" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Tüzel Unvan</label>
          <input type="text" name="firma_unvan" class="form-control" value="Orsan OPS Sanayi ve Ticaret A.Ş.">
        </div>
        <div class="col-12">
          <label class="form-label">Firma Adresi</label>
          <textarea name="firma_adres" class="form-control" rows="2">İstanbul Trakya Free Zone, Ferhatpaşa Serbest Bölge Mah. Ali Rıza Efendi Cad. No:27, Çatalca 34540 / İstanbul</textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Şehir</label>
          <input type="text" name="firma_sehir" class="form-control" value="İstanbul">
        </div>
        <div class="col-md-6">
          <label class="form-label">Ülke</label>
          <input type="text" name="firma_ulke" class="form-control" value="Türkiye">
        </div>
        <div class="col-md-6">
          <label class="form-label">Telefon</label>
          <input type="text" name="firma_telefon" class="form-control" placeholder="+90 212 000 00 00">
        </div>
        <div class="col-md-6">
          <label class="form-label">E-posta</label>
          <input type="email" name="firma_email" class="form-control" placeholder="info@hsgaviation.com">
        </div>
        <div class="col-md-6">
          <label class="form-label">Web Sitesi</label>
          <input type="text" name="firma_website" class="form-control" value="https://hsgaviation.com">
        </div>
        <div class="col-md-6">
          <label class="form-label">Teklif Ön Eki</label>
          <input type="text" name="teklif_prefix" class="form-control" value="TKL" maxlength="5">
          <div class="form-text">Örnek: TKL-2024-0001</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Vergi No</label>
          <input type="text" name="firma_vergi_no" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Vergi Dairesi</label>
          <input type="text" name="firma_vergi_dairesi" class="form-control">
        </div>
        <div class="col-12">
          <label class="form-label">Firma Logosu</label>
          <input type="file" name="logo" class="form-control" accept="image/*">
          <div class="form-text">JPG, PNG veya WEBP — Max 5MB. Daha sonra ayarlardan da yükleyebilirsiniz.</div>
        </div>
      </div>
      <div class="mt-4">
        <button type="submit" class="btn-hsg">
          <i class="bi bi-rocket-takeoff me-2"></i>Kurulumu Tamamla ve Başla!
        </button>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
