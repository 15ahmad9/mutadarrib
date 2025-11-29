<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<link rel="stylesheet" href="../../assets/css/style.css">
<nav class="navbar">
  <div class="logo">متدرب</div>
  <ul class="nav-links">
    <li><a href="index.php">الرئيسية</a></li>
    <li><a href="#about">من نحن</a></li>
    <li><a href="#services">الخدمات</a></li>

    <?php if (isset($_SESSION['user_id'])): ?>
      <?php 
        $role = $_SESSION['role'];
        $role_ar = ($role === 'lawyer') ? 'مزاول' : (($role === 'trainee') ? 'متدرب' : 'مدير');
      ?>
      <li class="user-dropdown">
        <button class="user-toggle" id="userToggle">
          <?= htmlspecialchars($_SESSION['full_name']); ?> (<?= $role_ar; ?>) ▾
        </button>
        <ul class="dropdown-menu" id="dropdownMenu">
          <li><a href="profile.php">الملف الشخصي</a></li>

          <?php if ($role === 'admin'): ?>
            <li><a href="admin/dashboard.php">لوحة التحكم</a></li>
          <?php endif; ?>
<?php if ($role === 'lawyer'): ?>
            <li><a href="lawyer/dashboard.php">لوحة التحكم</a></li>
          <?php endif; ?>
          <li><a href="./auth/logout.php">تسجيل الخروج</a></li>
        </ul>
      </li>
    <?php else: ?>
      <li><a href="./auth/login.php" class="login-btn">تسجيل الدخول</a></li>
      <li><a href="./auth/choose_specialization.php" class="register-btn">إنشاء حساب</a></li>
    <?php endif; ?>
  </ul>
</nav>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const toggleBtn = document.getElementById("userToggle");
  const dropdown = document.getElementById("dropdownMenu");

  if (toggleBtn && dropdown) {
    toggleBtn.addEventListener("click", function(e) {
      e.stopPropagation();
      dropdown.classList.toggle("show");
    });

    document.addEventListener("click", function(e) {
      if (!dropdown.contains(e.target) && !toggleBtn.contains(e.target)) {
        dropdown.classList.remove("show");
      }
    });
  }
});
</script>
