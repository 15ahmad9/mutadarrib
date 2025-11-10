<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav class="navbar">
  <div class="logo">⚖️ متدرب</div>
  <ul class="nav-links">
    <li><a href="index.php">الرئيسية</a></li>
    <li><a href="#about">من نحن</a></li>
    <li><a href="#services">الخدمات</a></li>

    <?php if (isset($_SESSION['user_id'])): ?>
      <?php 
        $role_ar = ($_SESSION['role'] === 'lawyer') ? 'مزاول' : 'طالب';
      ?>
      <li class="user-dropdown">
        <button class="user-toggle" id="userToggle">
          <?= htmlspecialchars($_SESSION['full_name']); ?> (<?= $role_ar; ?>) ▾
        </button>
        <ul class="dropdown-menu" id="dropdownMenu">
          <li><a href="profile.php">الملف الشخصي</a></li>
          <li><a href="./auth/logout.php">تسجيل الخروج</a></li>
        </ul>
      </li>
    <?php else: ?>
      <li><a href="./auth/login.php" class="login-btn">تسجيل الدخول</a></li>
      <li><a href="./auth/register.php" class="register-btn">إنشاء حساب</a></li>
    <?php endif; ?>
  </ul>
</nav>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const toggleBtn = document.getElementById("userToggle");
  const dropdown = document.getElementById("dropdownMenu");

  if (toggleBtn && dropdown) {
    toggleBtn.addEventListener("click", function(e) {
      e.stopPropagation(); // منع إغلاق القائمة فور النقر
      dropdown.classList.toggle("show");
    });

    // عند النقر في أي مكان خارج القائمة تُغلق
    document.addEventListener("click", function(e) {
      if (!dropdown.contains(e.target) && !toggleBtn.contains(e.target)) {
        dropdown.classList.remove("show");
      }
    });
  }
});
</script>
