<?php
/**
 * HSG Aviation - Teklif Yönetim Sistemi
 * Konfigürasyon Dosyası
 */

// Kurulum kontrolü
if (!file_exists(__DIR__ . '/.installed') && basename($_SERVER['PHP_SELF']) !== 'kurulum.php') {
    header('Location: kurulum.php');
    exit;
}

// Veritabanı Ayarları
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'hsg_teklif');
define('DB_CHARSET', 'utf8mb4');

// Uygulama Ayarları
define('APP_NAME', 'HSG Aviation Teklif Sistemi');
define('APP_VERSION', '2.0.0');
define('BASE_PATH', __DIR__);

// URL tespiti — config.php her zaman uygulama kökünde olduğu için __DIR__ kullanılır
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$docRoot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$appRoot = rtrim(str_replace('\\', '/', realpath(__DIR__)), '/');
$baseDir = ($docRoot && strpos($appRoot, $docRoot) === 0) ? substr($appRoot, strlen($docRoot)) : '';
define('BASE_URL', $protocol . '://' . $host . $baseDir);

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Hata yönetimi
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Para birimleri
define('CURRENCIES', json_encode([
    'TRY' => ['symbol' => '₺', 'name' => 'Türk Lirası'],
    'USD' => ['symbol' => '$', 'name' => 'Amerikan Doları'],
    'EUR' => ['symbol' => '€', 'name' => 'Euro'],
    'GBP' => ['symbol' => '£', 'name' => 'İngiliz Sterlini'],
    'JPY' => ['symbol' => '¥', 'name' => 'Japon Yeni'],
]));

// KDV Oranları
define('KDV_ORANLARI', json_encode([0, 1, 8, 10, 18, 20]));
