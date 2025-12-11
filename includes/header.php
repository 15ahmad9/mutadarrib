<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تحميل الاتصال بقاعدة البيانات (لو لم يكن محمّلاً)
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/db.php';
}

// حساب عدد الإشعارات للمتدرب (طلبات تم قبولها أو رفضها)
$notificationsCount = 0;

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'trainee') {

    $userId = $_SESSION['user_id'];

    // إيجاد رقم المتدرب المرتبط بهذا المستخدم
    $stmtNotif = $pdo->prepare("
        SELECT t.trainee_id
        FROM trainees t
        WHERE t.user_id = ?
        LIMIT 1
    ");
    $stmtNotif->execute([$userId]);
    $traineeRow = $stmtNotif->fetch(PDO::FETCH_ASSOC);

    if ($traineeRow) {
        $traineeId = $traineeRow['trainee_id'];

        // حساب عدد الطلبات التي تم اتخاذ قرار فيها (accepted أو rejected)
        $stmtCount = $pdo->prepare("
            SELECT COUNT(*)
            FROM training_applications
            WHERE trainee_id = ?
              AND status IN ('accepted','rejected')
        ");
        $stmtCount->execute([$traineeId]);
        $notificationsCount = (int)$stmtCount->fetchColumn();
    }
}
?>

<link rel="stylesheet" href="../../assets/css/style.css">

<!-- يمكن إضافة بعض التنسيقات البسيطة للإشعارات -->
<style>
  .nav-notifications {
    position: relative;
    margin-left: 10px;
    margin-right: 10px;
  }

  .nav-notifications a {
    text-decoration: none;
    font-size: 20px;
    position: relative;
  }

  .notif-badge {
    position: absolute;
    top: -6px;
    right: -10px;
    background: red;
    color: #fff;
    font-size: 11px;
    border-radius: 50%;
    padding: 2px 6px;
    min-width: 18px;
    text-align: center;
  }
</style>

<nav class="navbar">
  <div class="logo">متدرب</div>
  <ul class="nav-links">
    <li><a href="/mutadarrib/index.php">الرئيسية</a></li>
    <li><a href="#about">من نحن</a></li>
    <li><a href="#services">الخدمات</a></li>

    <?php if (isset($_SESSION['user_id'])): ?>
      <?php 
        $role = $_SESSION['role'];
        $role_ar = ($role === 'lawyer') ? 'مزاول' : (($role === 'trainee') ? 'متدرب' : (($role === 'syndicate_admin') ? 'موظف النقابة' : 'مدير'));
      ?>

      <!-- 🔔 أيقونة إشعارات للمتدرب فقط -->
      <?php if ($role === 'trainee'): ?>
        <li class="nav-notifications">
          <!-- غيّر الرابط حسب مكان صفحة الإشعارات عندك (مثلاً notifications.php) -->
          <a href="/mutadarrib/trainee/notifications.php" title="إشعارات طلبات التدريب">
            🔔
            <?php if ($notificationsCount > 0): ?>
              <span class="notif-badge"><?= $notificationsCount ?></span>
            <?php endif; ?>
          </a>
        </li>
      <?php endif; ?>

      <!-- قائمة المستخدم -->
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
            <li><a href="lawyer/dashboard.php">لوحة المحامي</a></li>
          <?php endif; ?>

          <?php if ($role === 'syndicate_admin'): ?>
    <li><a href="syndicate/dashboard.php">لوحة النقابة</a></li>
<?php endif; ?>

          <li><a href="/mutadarrib/auth/logout.php">تسجيل الخروج</a></li>
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
