<?php
/**
 * HSG Aviation - Veritabanı Bağlantısı
 */

if (!defined('DB_HOST')) {
    require_once dirname(__DIR__) . '/config.php';
}

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    if (PHP_SAPI === 'cli' || defined('KURULUM')) {
        throw $e;
    }
    die('<div style="font-family:sans-serif;padding:30px;background:#fee;border:1px solid #f00;margin:20px;border-radius:8px;">
        <h2>⚠️ Veritabanı Bağlantı Hatası</h2>
        <p>Veritabanına bağlanılamadı. Lütfen <a href="kurulum.php">kurulum sayfasını</a> kontrol edin.</p>
        <code>' . htmlspecialchars($e->getMessage()) . '</code>
    </div>');
}
