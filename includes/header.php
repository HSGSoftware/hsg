<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= e(ayar('firma_adi', 'HSG Aviation')) ?> Teklif Sistemi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<style>
:root {
  --hsg-dark:       #0a1628;
  --hsg-navy:       #162c4a;
  --hsg-navy2:      #1e3a5f;
  --hsg-gold:       #e8a000;
  --hsg-gold-light: #f0b429;
  --hsg-gold-pale:  #fff8e6;
  --hsg-sidebar-w:  260px;
  --hsg-topbar-h:   64px;
}

/* === LAYOUT === */
body {
  font-family: 'Inter', sans-serif;
  background: #f0f4f8;
  color: #1a202c;
  min-height: 100vh;
}

/* === SIDEBAR === */
#sidebar {
  position: fixed;
  top: 0; left: 0; bottom: 0;
  width: var(--hsg-sidebar-w);
  background: linear-gradient(180deg, var(--hsg-dark) 0%, var(--hsg-navy) 50%, var(--hsg-navy2) 100%);
  z-index: 1050;
  display: flex;
  flex-direction: column;
  transition: transform 0.3s ease;
  overflow-y: auto;
  overflow-x: hidden;
  box-shadow: 3px 0 20px rgba(0,0,0,0.3);
}

#sidebar::-webkit-scrollbar { width: 4px; }
#sidebar::-webkit-scrollbar-track { background: transparent; }
#sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 2px; }

.sidebar-brand {
  padding: 20px 20px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  flex-shrink: 0;
}
.sidebar-brand .brand-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
}
.sidebar-brand .logo-img {
  width: 42px; height: 42px;
  object-fit: contain;
  filter: brightness(1.1);
}
.sidebar-brand .logo-icon {
  width: 42px; height: 42px;
  background: var(--hsg-gold);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem;
  color: var(--hsg-dark);
  flex-shrink: 0;
}
.sidebar-brand .brand-text { flex: 1; min-width: 0; }
.sidebar-brand .brand-name {
  font-size: 1rem;
  font-weight: 800;
  color: var(--hsg-gold);
  letter-spacing: -0.3px;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sidebar-brand .brand-sub {
  font-size: 0.68rem;
  color: rgba(255,255,255,0.5);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar-section {
  padding: 12px 12px 4px;
}
.sidebar-section-label {
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 1.2px;
  color: rgba(255,255,255,0.3);
  text-transform: uppercase;
  padding: 0 10px;
  margin-bottom: 4px;
}
.sidebar-nav { padding: 0; margin: 0; list-style: none; }
.sidebar-nav .nav-item { margin: 1px 0; }
.sidebar-nav .nav-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 14px;
  border-radius: 8px;
  color: rgba(255,255,255,0.7);
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s;
  white-space: nowrap;
}
.sidebar-nav .nav-link:hover {
  background: rgba(255,255,255,0.08);
  color: #fff;
}
.sidebar-nav .nav-link.active {
  background: linear-gradient(135deg, var(--hsg-gold), var(--hsg-gold-light));
  color: var(--hsg-dark) !important;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(232,160,0,0.3);
}
.sidebar-nav .nav-link .bi {
  font-size: 1.05rem;
  width: 20px;
  text-align: center;
  flex-shrink: 0;
}
.sidebar-nav .nav-link .badge {
  margin-left: auto;
  font-size: 0.68rem;
}

.sidebar-bottom {
  margin-top: auto;
  padding: 12px;
  border-top: 1px solid rgba(255,255,255,0.08);
  flex-shrink: 0;
}
.sidebar-version {
  font-size: 0.65rem;
  color: rgba(255,255,255,0.2);
  text-align: center;
  padding: 4px;
}

/* === TOPBAR === */
#topbar {
  position: fixed;
  top: 0;
  left: var(--hsg-sidebar-w);
  right: 0;
  height: var(--hsg-topbar-h);
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  padding: 0 24px;
  gap: 16px;
  z-index: 1040;
  box-shadow: 0 1px 8px rgba(0,0,0,0.06);
  transition: left 0.3s ease;
}
.topbar-toggle {
  background: none; border: none;
  font-size: 1.3rem;
  color: #4a5568;
  cursor: pointer;
  padding: 6px;
  border-radius: 6px;
  display: none;
}
.topbar-toggle:hover { background: #f7fafc; color: var(--hsg-dark); }
.topbar-breadcrumb {
  flex: 1;
  font-size: 0.875rem;
}
.topbar-breadcrumb .bc-item { color: #718096; }
.topbar-breadcrumb .bc-sep { color: #cbd5e0; margin: 0 6px; }
.topbar-breadcrumb .bc-current { font-weight: 600; color: var(--hsg-dark); }
.topbar-actions { display: flex; align-items: center; gap: 10px; }
.topbar-btn {
  background: none;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  padding: 7px 14px;
  font-size: 0.82rem;
  font-weight: 500;
  color: #4a5568;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
}
.topbar-btn:hover { border-color: var(--hsg-gold); color: var(--hsg-dark); }
.topbar-btn.primary {
  background: var(--hsg-dark);
  border-color: var(--hsg-dark);
  color: #fff;
}
.topbar-btn.primary:hover { background: var(--hsg-navy2); color: var(--hsg-gold); }

/* === MAIN CONTENT === */
#main-content {
  margin-left: var(--hsg-sidebar-w);
  padding-top: var(--hsg-topbar-h);
  min-height: 100vh;
  transition: margin-left 0.3s ease;
}
.content-area { padding: 28px 28px; }

/* === CARDS === */
.card {
  border: none;
  border-radius: 12px;
  box-shadow: 0 1px 6px rgba(0,0,0,0.07);
}
.card-header {
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  padding: 16px 20px;
  border-radius: 12px 12px 0 0 !important;
}
.card-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--hsg-dark);
  margin: 0;
}

/* === STAT CARDS === */
.stat-card {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 1px 6px rgba(0,0,0,0.07);
  display: flex;
  align-items: center;
  gap: 16px;
  transition: transform 0.2s, box-shadow 0.2s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
.stat-icon {
  width: 52px; height: 52px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}
.stat-icon.gold    { background: var(--hsg-gold-pale); color: var(--hsg-gold); }
.stat-icon.navy    { background: #eef2ff; color: var(--hsg-navy); }
.stat-icon.green   { background: #f0fff4; color: #38a169; }
.stat-icon.red     { background: #fff5f5; color: #e53e3e; }
.stat-icon.blue    { background: #ebf8ff; color: #3182ce; }
.stat-icon.purple  { background: #faf5ff; color: #805ad5; }
.stat-value {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--hsg-dark);
  line-height: 1;
}
.stat-label {
  font-size: 0.78rem;
  color: #718096;
  font-weight: 500;
  margin-top: 3px;
}
.stat-change {
  font-size: 0.72rem;
  font-weight: 600;
  margin-top: 4px;
}
.stat-change.up { color: #38a169; }
.stat-change.down { color: #e53e3e; }

/* === BUTTONS === */
.btn-primary { background: var(--hsg-dark); border-color: var(--hsg-dark); }
.btn-primary:hover { background: var(--hsg-navy2); border-color: var(--hsg-navy2); }
.btn-warning { background: var(--hsg-gold); border-color: var(--hsg-gold); color: var(--hsg-dark); }
.btn-warning:hover { background: var(--hsg-gold-light); border-color: var(--hsg-gold-light); color: var(--hsg-dark); }

/* === TABLES === */
.table thead th {
  background: var(--hsg-dark);
  color: rgba(255,255,255,0.9);
  font-size: 0.78rem;
  font-weight: 600;
  letter-spacing: 0.5px;
  border: none;
  padding: 12px 16px;
}
.table tbody td {
  padding: 12px 16px;
  vertical-align: middle;
  border-color: #f0f4f8;
  font-size: 0.875rem;
}
.table tbody tr:hover { background: #f8fafc; }
.table-responsive { border-radius: 0 0 12px 12px; overflow: hidden; }

/* === FORMS === */
.form-control, .form-select {
  border-color: #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  padding: 9px 14px;
}
.form-control:focus, .form-select:focus {
  border-color: var(--hsg-gold);
  box-shadow: 0 0 0 3px rgba(232,160,0,0.15);
}
.form-label { font-weight: 600; font-size: 0.82rem; color: #4a5568; margin-bottom: 5px; }
.input-group-text { background: #f7fafc; border-color: #e2e8f0; font-size: 0.875rem; }

/* === BADGE === */
.badge { font-weight: 600; letter-spacing: 0.3px; }

/* === ALERT === */
.alert { border-radius: 10px; font-size: 0.875rem; }

/* === SEARCH === */
.search-box {
  position: relative;
}
.search-box .bi-search {
  position: absolute;
  left: 12px; top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
  font-size: 0.9rem;
}
.search-box input { padding-left: 36px; }

/* === MOBILE === */
@media (max-width: 991.98px) {
  #sidebar {
    transform: translateX(-100%);
  }
  #sidebar.sidebar-open {
    transform: translateX(0);
  }
  #topbar {
    left: 0;
  }
  #main-content {
    margin-left: 0;
  }
  .topbar-toggle { display: flex; }
  .content-area { padding: 16px; }
  #sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1049;
  }
  #sidebar-overlay.show { display: block; }
}

/* === UTILITIES === */
.text-hsg { color: var(--hsg-dark); }
.text-gold { color: var(--hsg-gold); }
.bg-hsg { background: var(--hsg-dark); color: #fff; }
.border-gold { border-color: var(--hsg-gold) !important; }
.rounded-12 { border-radius: 12px; }

/* === PAGE HEADER === */
.page-header {
  margin-bottom: 24px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}
.page-header h1 {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--hsg-dark);
  margin: 0;
}
.page-header .page-subtitle {
  font-size: 0.82rem;
  color: #718096;
  margin-top: 2px;
}

/* === AVATAR === */
.avatar {
  width: 36px; height: 36px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.875rem;
  background: var(--hsg-gold-pale);
  color: var(--hsg-gold);
  flex-shrink: 0;
}
.avatar.lg { width: 52px; height: 52px; font-size: 1.2rem; border-radius: 12px; }

/* === EMPTY STATE === */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #a0aec0;
}
.empty-state .empty-icon { font-size: 4rem; margin-bottom: 16px; opacity: 0.5; }
.empty-state h5 { color: #4a5568; font-weight: 600; }

/* === DROPDOWN ACTIONS === */
.action-btn {
  padding: 5px 10px;
  font-size: 0.8rem;
  border-radius: 6px;
}
</style>
</head>
<body>

<!-- Sidebar Overlay (mobile) -->
<div id="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<nav id="sidebar">
  <div class="sidebar-brand">
    <a href="<?= BASE_URL ?>/index.php" class="brand-logo">
      <?php $logo = ayar('firma_logo', ''); ?>
      <?php if ($logo && file_exists(BASE_PATH . '/uploads/logos/' . $logo)): ?>
        <img src="<?= BASE_URL ?>/uploads/logos/<?= e($logo) ?>" class="logo-img" alt="Logo">
      <?php else: ?>
        <div class="logo-icon">✈</div>
      <?php endif; ?>
      <div class="brand-text">
        <div class="brand-name"><?= e(ayar('firma_adi', 'HSG Aviation')) ?></div>
        <div class="brand-sub">Teklif Yönetim Sistemi</div>
      </div>
    </a>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Ana Menü</div>
    <ul class="sidebar-nav">
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/index.php" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
          <i class="bi bi-speedometer2"></i> Gösterge Paneli
        </a>
      </li>
    </ul>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Teklif Yönetimi</div>
    <ul class="sidebar-nav">
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/teklifler/index.php" class="nav-link <?= $activePage === 'teklifler' ? 'active' : '' ?>">
          <i class="bi bi-file-earmark-text"></i> Teklifler
          <?php
            global $pdo;
            $cnt = $pdo->query("SELECT COUNT(*) FROM teklifler WHERE durum='taslak'")->fetchColumn();
            if ($cnt > 0): ?>
            <span class="badge bg-warning text-dark"><?= $cnt ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/teklifler/olustur.php" class="nav-link <?= $activePage === 'teklif_olustur' ? 'active' : '' ?>">
          <i class="bi bi-plus-circle"></i> Yeni Teklif
        </a>
      </li>
    </ul>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Müşteriler & Ürünler</div>
    <ul class="sidebar-nav">
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/musteriler/index.php" class="nav-link <?= $activePage === 'musteriler' ? 'active' : '' ?>">
          <i class="bi bi-people"></i> Müşteriler
        </a>
      </li>
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/urunler/index.php" class="nav-link <?= $activePage === 'urunler' ? 'active' : '' ?>">
          <i class="bi bi-box-seam"></i> Ürün Kataloğu
        </a>
      </li>
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/kategoriler/index.php" class="nav-link <?= $activePage === 'kategoriler' ? 'active' : '' ?>">
          <i class="bi bi-tags"></i> Kategoriler
        </a>
      </li>
    </ul>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Sistem</div>
    <ul class="sidebar-nav">
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/sablonlar/index.php" class="nav-link <?= $activePage === 'sablonlar' ? 'active' : '' ?>">
          <i class="bi bi-layout-text-sidebar"></i> Teklif Şablonları
        </a>
      </li>
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/ayarlar/index.php" class="nav-link <?= $activePage === 'ayarlar' ? 'active' : '' ?>">
          <i class="bi bi-gear"></i> Ayarlar
        </a>
      </li>
    </ul>
  </div>

  <div class="sidebar-bottom">
    <div class="sidebar-version">HSG Aviation Teklif v2.0 © <?= date('Y') ?></div>
  </div>
</nav>

<!-- TOPBAR -->
<div id="topbar">
  <button class="topbar-toggle" onclick="toggleSidebar()">
    <i class="bi bi-list"></i>
  </button>
  <div class="topbar-breadcrumb">
    <?php if (isset($breadcrumb) && is_array($breadcrumb)): ?>
      <?php foreach ($breadcrumb as $i => $bc): ?>
        <?php if ($i < count($breadcrumb) - 1): ?>
          <a href="<?= e($bc['url']) ?>" class="bc-item text-decoration-none"><?= e($bc['label']) ?></a>
          <span class="bc-sep">/</span>
        <?php else: ?>
          <span class="bc-current"><?= e($bc['label']) ?></span>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php else: ?>
      <span class="bc-current"><?= e($pageTitle ?? 'Gösterge Paneli') ?></span>
    <?php endif; ?>
  </div>
  <div class="topbar-actions">
    <a href="<?= BASE_URL ?>/teklifler/olustur.php" class="topbar-btn primary">
      <i class="bi bi-plus-lg"></i> <span class="d-none d-sm-inline">Yeni Teklif</span>
    </a>
    <a href="<?= BASE_URL ?>/ayarlar/index.php" class="topbar-btn">
      <i class="bi bi-gear"></i>
    </a>
  </div>
</div>

<!-- MAIN CONTENT -->
<div id="main-content">
  <div class="content-area">
    <?= flashGoster() ?>
