<?php
?>
<input type="checkbox" id="dash-sidebar-toggle" class="dash-sidebar-toggle" aria-hidden="true">
<label for="dash-sidebar-toggle" class="sidebar-backdrop" aria-hidden="true"></label>


<header class="admin-header">
  <label for="dash-sidebar-toggle" class="sidebar-toggle-btn" aria-label="فتح/إغلاق القائمة">☰</label>
  <h2>لوحة تحكم الإدارة</h2>
  <div class="user-info">
    <span>مرحباً، <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></span>
    <!-- <a href="../auth/logout.php" class="btn btn-sm outline">تسجيل خروج</a> -->
  </div>
</header>
