<?php
// UI-only: CSS-only sidebar toggle (no JS).
?>
<input type="checkbox" id="dash-sidebar-toggle" class="dash-sidebar-toggle" aria-hidden="true">
<label for="dash-sidebar-toggle" class="sidebar-backdrop" aria-hidden="true"></label>

<header class="header">
  <label for="dash-sidebar-toggle" class="sidebar-toggle-btn" aria-label="فتح/إغلاق القائمة">☰</label>

  <div style="display:flex;flex-direction:column;gap:2px;">
    <h2 style="margin:0;">لوحة مزود IT</h2>
    <div style="font-size:12px;opacity:.85;">
      <?= h($companyName ?: 'إدارة فرص التدريب') ?>
    </div>
  </div>

  <div class="user-info">
    <a class="dash-theme-toggle"
       href="/mutadarrib/toggle_theme.php?redirect=<?= $redirect_uri ?>"
       aria-label="تبديل الوضع" title="تبديل الوضع">
      <?= ($theme === 'dark') ? '☀️' : '🌙' ?>
    </a>

    <details class="dash-user-dropdown">
      <summary class="dash-user-toggle">
        <?= h($displayName) ?> ▾
      </summary>
      <div class="dash-user-menu">
        <a href="/mutadarrib/profile.php">👤 الملف الشخصي</a>
        <a href="/mutadarrib/it/dashboard.php">📊 لوحة مزود IT</a>
        <a href="/mutadarrib/auth/logout.php">🚪 تسجيل الخروج</a>
      </div>
    </details>
  </div>
</header>
