<?php
/**
 * HSG Aviation - Müşteri Arama AJAX
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) { echo json_encode([]); exit; }

$search = '%' . $q . '%';
$stmt = $pdo->prepare("SELECT id, musteri_no, firma_adi, yetkili_kisi, email, telefon, sehir, ulke
    FROM musteriler
    WHERE durum = 'aktif'
    AND (firma_adi LIKE ? OR musteri_no LIKE ? OR yetkili_kisi LIKE ? OR email LIKE ?)
    ORDER BY firma_adi LIMIT 10");
$stmt->execute([$search, $search, $search, $search]);

echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
