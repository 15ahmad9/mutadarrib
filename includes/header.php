<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($pdo)) {
  require_once __DIR__ . '/../config/db.php';
}

// إشعارات التدريب للمتدرب
$notificationsCount = 0;

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'trainee') {
  $userId = (int)$_SESSION['user_id'];

  $stmtNotif = $pdo->prepare("
    SELECT t.trainee_id
    FROM trainees t
    WHERE t.user_id = ?
    LIMIT 1
  ");
  $stmtNotif->execute([$userId]);
  $traineeRow = $stmtNotif->fetch(PDO::FETCH_ASSOC);

  if ($traineeRow) {
    $traineeId = (int)$traineeRow['trainee_id'];

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

// تذكيرات التقويم (Badge للتذكيرات القريبة) لكل المستخدمين
$calendarRemindersCount = 0;

if (isset($_SESSION['user_id'])) {
  $userId = (int)$_SESSION['user_id'];

  $stmtRem = $pdo->prepare("
    SELECT COUNT(*)
    FROM calendar_events
    WHERE user_id = ?
      AND reminder_minutes IS NOT NULL
      AND reminder_minutes > 0
      AND start_at IS NOT NULL
      AND NOW() >= DATE_SUB(start_at, INTERVAL reminder_minutes MINUTE)
      AND NOW() < start_at
  ");
  $stmtRem->execute([$userId]);
  $calendarRemindersCount = (int)$stmtRem->fetchColumn();
}
?>

<nav class="navbar">
  <div class="logo">متدرب</div>

  <ul class="nav-links">
    <li><a href="/mutadarrib/index.php">الرئيسية</a></li>
    <li><a href="/mutadarrib/contact.php">تواصل معنا</a></li>
    <li><a href="/mutadarrib/index.php#services">الخدمات</a></li>

    <?php if (isset($_SESSION['user_id'])): ?>

      <?php
        $role = $_SESSION['role'] ?? '';
        $role_ar = ($role === 'lawyer')
          ? 'مزاول'
          : (($role === 'trainee')
              ? 'متدرب'
              : (($role === 'syndicate_admin') ? 'موظف النقابة' : 'مدير'));
      ?>

      <?php if ($role === 'trainee'): ?>
        <li><a href="/mutadarrib/trainee/training_progress.php">مدة التدريب</a></li>
      <?php endif; ?>

      <li><a href="/mutadarrib/calendar/calendar.php">التقويم</a></li>

      <li class="nav-notifications">
        <a class="nav-badge-link" href="/mutadarrib/calendar/upcoming_reminders.php" title="تذكيرات التقويم القريبة">
          تذكيرات
          <?php if ($calendarRemindersCount > 0): ?>
            <span class="notif-badge"><?= $calendarRemindersCount ?></span>
          <?php endif; ?>
        </a>
      </li>

      <!-- إشعارات التدريب (متدرب فقط) -->
      <?php if ($role === 'trainee'): ?>
        <li class="nav-notifications">
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
          <?= htmlspecialchars($_SESSION['full_name'] ?? ''); ?> (<?= $role_ar; ?>) ▾
        </button>

        <ul class="dropdown-menu" id="dropdownMenu">
          <li><a href="/mutadarrib/profile.php">الملف الشخصي</a></li>

          <?php if ($role === 'admin'): ?>
            <li><a href="/mutadarrib/admin/dashboard.php">لوحة التحكم</a></li>
          <?php endif; ?>

          <?php if ($role === 'lawyer'): ?>
            <li><a href="/mutadarrib/lawyer/dashboard.php">لوحة المحامي</a></li>
          <?php endif; ?>

          <?php if ($role === 'syndicate_admin'): ?>
            <li><a href="/mutadarrib/syndicate/dashboard.php">لوحة النقابة</a></li>
          <?php endif; ?>

          <li><a href="/mutadarrib/auth/logout.php">تسجيل الخروج</a></li>
        </ul>
      </li>

    <?php else: ?>
      <li><a href="/mutadarrib/auth/login.php" class="login-btn">تسجيل الدخول</a></li>
      <li><a href="/mutadarrib/auth/choose_specialization.php" class="register-btn">إنشاء حساب</a></li>
    <?php endif; ?>
  </ul>
</nav>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("userToggle");
    const dropdown = document.getElementById("dropdownMenu");

    if (toggleBtn && dropdown) {
      toggleBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        dropdown.classList.toggle("show");
      });

      document.addEventListener("click", function (e) {
        if (!dropdown.contains(e.target) && !toggleBtn.contains(e.target)) {
          dropdown.classList.remove("show");
        }
      });
    }
  });
</script>
