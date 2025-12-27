<?php
?>
<input type="checkbox" id="dash-sidebar-toggle" class="dash-sidebar-toggle" aria-hidden="true">
<label for="dash-sidebar-toggle" class="sidebar-backdrop" aria-hidden="true"></label>


<header class="admin-header">
  <label for="dash-sidebar-toggle" class="sidebar-toggle-btn" aria-label="فتح/إغلاق القائمة">☰</label>
  <h2>لوحة تحكم الإدارة</h2>
  
  <div class="user-info">
    <details class="dash-user-dropdown">
      <summary class="dash-user-toggle">
        <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?> ▾
      </summary>
      <div class="dash-user-menu">
        <a href="/mutadarrib/toggle_theme.php?redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? '../index.php') ?>">الوضع الداكن: <?= ($theme === 'dark') ? 'مفعل' : 'غير مفعل' ?></a>
        <a href="/mutadarrib/auth/logout.php">تسجيل الخروج</a>
      </div>
    </details>
  </div>
</header>
