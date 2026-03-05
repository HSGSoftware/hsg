  </div><!-- /content-area -->
</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/tr.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
// Sidebar Toggle
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  sidebar.classList.toggle('sidebar-open');
  overlay.classList.toggle('show');
}

// Select2 varsayılan ayarları
$(document).ready(function() {
  if ($.fn.select2) {
    $.fn.select2.defaults.set('theme', 'bootstrap-5');
    $.fn.select2.defaults.set('language', 'tr');
    $.fn.select2.defaults.set('width', '100%');
  }

  // Tooltips
  const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltips.forEach(el => new bootstrap.Tooltip(el));

  // Auto-dismiss alerts
  setTimeout(() => {
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(el => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
      if (bsAlert) bsAlert.close();
    });
  }, 5000);
});

// Para formatı (JS)
function paraFormat(tutar, sembol = '₺') {
  return sembol + ' ' + parseFloat(tutar || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Onay dialogu
function onayIste(mesaj, url) {
  if (confirm(mesaj)) {
    window.location.href = url;
  }
}
</script>
<?php if (isset($extraJs)): ?>
<?= $extraJs ?>
<?php endif; ?>
</body>
</html>
