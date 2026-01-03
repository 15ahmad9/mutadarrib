<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();
require_once __DIR__ . '/theme_init.php';
if (!isset($pdo))
  require_once __DIR__ . '/../config/db.php';

$role = $_SESSION['role'] ?? '';
$notificationsCount = 0;
$calendarRemindersCount = 0;

/* إشعارات التدريب للمتدرب */
if (isset($_SESSION['user_id']) && $role === 'trainee') {
  $userId = (int) $_SESSION['user_id'];

  $stmtNotif = $pdo->prepare("SELECT trainee_id FROM trainees WHERE user_id = ? LIMIT 1");
  $stmtNotif->execute([$userId]);
  $traineeRow = $stmtNotif->fetch(PDO::FETCH_ASSOC);

  if ($traineeRow) {
    $traineeId = (int) $traineeRow['trainee_id'];

    $stmtCount = $pdo->prepare("
      SELECT COUNT(*)
      FROM training_applications
      WHERE trainee_id = ?
        AND status IN ('accepted','rejected','completed')
        AND trainee_seen = 0
    ");
    $stmtCount->execute([$traineeId]);
    $notificationsCount = (int) $stmtCount->fetchColumn();
  }
}

/* تذكيرات التقويم */
if (isset($_SESSION['user_id'])) {
  $userId = (int) $_SESSION['user_id'];

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
  $calendarRemindersCount = (int) $stmtRem->fetchColumn();
}

$role_ar = ($role === 'lawyer') ? 'مزاول' : (($role === 'trainee') ? 'متدرب' : (($role === 'syndicate_admin') ? 'موظف النقابة' : 'مدير'));
$redirect_uri = urlencode($_SERVER['REQUEST_URI'] ?? '/mutadarrib/index.php');
?>

<link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
<link rel="stylesheet" href="/mutadarrib/assets/css/lawyers.css">

<style>
  .nav-notifications {
    position: relative;
    margin: 0 8px;
  }

  .nav-notifications a {
    position: relative;
    display: inline-block;
    text-decoration: none;
  }

  .notif-badge {
    position: absolute;
    top: -8px;
    right: -12px;
    background: red;
    color: #fff;
    font-size: 11px;
    border-radius: 999px;
    padding: 2px 6px;
    min-width: 18px;
    text-align: center;
  }
</style>

<nav class="navbar">
  <a href="/mutadarrib/index.php">
    <div class="logo">متدرب</div>
  </a>

  <ul class="nav-links">
    <li><a href="/mutadarrib/index.php">الرئيسية</a></li>
    <li><a href="/mutadarrib/contact.php">تواصل معنا</a></li>
    <li><a href="/mutadarrib/index.php#services">الخدمات</a></li>

    <li><a href="/mutadarrib/membership/request_membership.php">طلب انتساب</a></li>

    <?php if (isset($_SESSION['user_id'])): ?>

      <?php if ($role === 'trainee'): ?>
        <li><a href="/mutadarrib/trainee/training_progress.php">مدة التدريب</a></li>
      <?php endif; ?>

      <li><a href="/mutadarrib/calendar/calendar.php">التقويم</a></li>


        <li class="nav-notifications">
          <a href="/mutadarrib/calendar/upcoming_reminders.php#reminders" title="تذكيرات التقويم">
            تذكيرات
            <?php if ($calendarRemindersCount > 0): ?>
              <span class="notif-badge"><?= $calendarRemindersCount ?></span>
            <?php endif; ?>
          </a>
        </li>

      <li><a class="theme-toggle-icon" href="/mutadarrib/toggle_theme.php?redirect=<?= $redirect_uri ?>" aria-label="تبديل الوضع" title="تبديل الوضع"><?= ($theme === 'dark') ? '☀️' : '🌙' ?></a></li>
      <li class="user-dropdown">
        <button class="user-toggle" id="userToggle">
          <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?> (<?= $role_ar ?>) ▾
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
      <li><a class="theme-toggle-icon" href="/mutadarrib/toggle_theme.php?redirect=<?= $redirect_uri ?>" aria-label="تبديل الوضع" title="تبديل الوضع"><?= ($theme === 'dark') ? '☀️' : '🌙' ?></a></li>
      <li><a href="/mutadarrib/auth/login.php" class="login-btn">تسجيل الدخول</a></li>
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

<!-- Front-end only: Apply Training popup (prevents full navigation when user already has active application) -->
<script src="/mutadarrib/assets/js/apply-training-modal.js" defer></script>