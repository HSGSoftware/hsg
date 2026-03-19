<?php
/**
 * FinansAI - SQLite Veritabanı Yardımcısı
 * Singleton PDO bağlantısı, tablo oluşturma ve ayar yönetimi
 */

define('FINANS_DB_YOLU', __DIR__ . '/database.sqlite');

/**
 * PDO bağlantısını döndürür (ilk çağrıda oluşturur ve şemayı kurar)
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . FINANS_DB_YOLU);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // WAL modu: eşzamanlı okuma/yazma performansını artırır
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA foreign_keys=ON');
        _semalariKur($pdo);
    }

    return $pdo;
}

/**
 * Tablolar yoksa oluşturur (idempotent)
 */
function _semalariKur(PDO $pdo): void
{
    // Uygulama ayarları tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            key   TEXT PRIMARY KEY,
            value TEXT NOT NULL DEFAULT ''
        )
    ");

    // İşlem / harcama kayıtları tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            date        TEXT    NOT NULL,
            description TEXT    NOT NULL,
            amount      REAL    NOT NULL DEFAULT 0,
            type        TEXT    NOT NULL CHECK(type IN ('income', 'expense')),
            category    TEXT    NOT NULL DEFAULT 'Diğer',
            created_at  TEXT    NOT NULL DEFAULT (datetime('now', 'localtime'))
        )
    ");
}

/**
 * Bir ayar değeri okur; bulunamazsa varsayılanı döndürür
 */
function getSetting(string $key, string $varsayilan = ''): string
{
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE key = ?');
    $stmt->execute([$key]);
    $satir = $stmt->fetch();

    return $satir !== false ? $satir['value'] : $varsayilan;
}

/**
 * Bir ayar değerini kaydeder (yoksa ekler, varsa günceller)
 */
function setSetting(string $key, string $value): void
{
    $pdo  = getDB();
    $stmt = $pdo->prepare('
        INSERT INTO settings (key, value)
        VALUES (?, ?)
        ON CONFLICT(key) DO UPDATE SET value = excluded.value
    ');
    $stmt->execute([$key, $value]);
}

/**
 * Birden fazla ayarı tek seferde kaydeder
 */
function setSettingToplu(array $ayarlar): void
{
    $pdo = getDB();
    $stmt = $pdo->prepare('
        INSERT INTO settings (key, value)
        VALUES (?, ?)
        ON CONFLICT(key) DO UPDATE SET value = excluded.value
    ');

    $pdo->beginTransaction();
    try {
        foreach ($ayarlar as $key => $value) {
            $stmt->execute([$key, (string)$value]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Bir provider/model string'inden provider ve model adını ayırır
 * Örn: "openai/gpt-4o" => ['provider' => 'openai', 'model' => 'gpt-4o']
 */
function modelBilgisiAl(string $providerModel): array
{
    $parcalar = explode('/', $providerModel, 2);

    return [
        'provider' => $parcalar[0] ?? 'openai',
        'model'    => $parcalar[1] ?? 'gpt-4o-mini',
    ];
}
