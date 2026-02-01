<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../includes/theme_init.php';
require_once __DIR__ . '/../../config/db.php';

$role = $_SESSION['role'] ?? '';
if ($role !== 'IT_Provider') {
  header("Location: /mutadarrib/auth/login.php");
  exit;
}

$redirect_uri = urlencode($_SERVER['REQUEST_URI'] ?? '/mutadarrib/index.php');

// اسم المستخدم
$displayName = $_SESSION['full_name'] ?? 'IT Provider';

// (اختياري) اسم الشركة من جدول it_providers
$companyName = null;
if (!empty($_SESSION['user_id'])) {
  $stmtP = $pdo->prepare("SELECT company_name FROM it_providers WHERE user_id = ? LIMIT 1");
  $stmtP->execute([(int)$_SESSION['user_id']]);
  $companyName = $stmtP->fetchColumn() ?: null;
}
?>
<link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
<link rel="stylesheet" href="/mutadarrib/assets/css/admin.css">
<link rel="stylesheet" href="/mutadarrib/assets/css/it.css">



<div class="it-shell" id="itShell">

  <!-- ===== Topbar ===== -->
  <header class="it-topbar">
    <div class="left">
      <button class="it-iconbtn" id="itToggleSidebar" title="إظهار/إخفاء القائمة">☰</button>

      <a href="/mutadarrib/index.php" style="text-decoration:none;color:#fff">
        <div class="it-brand">
          <div class="logo">💻</div>
          <div class="title">
            <b>لوحة مزود IT</b>
            <span><?= htmlspecialchars($companyName ?: 'إدارة فرص التدريب') ?></span>
          </div>
        </div>
      </a>
    </div>

    <div class="right">
      <a class="it-iconbtn" href="/mutadarrib/toggle_theme.php?redirect=<?= $redirect_uri ?>" title="تبديل الوضع">
        <?= ($theme === 'dark') ? '☀️' : '🌙' ?>
      </a>

      <div class="it-user">
        <button class="it-user-btn" id="itUserBtn">
          <span><?= htmlspecialchars($displayName) ?></span>
          <span style="opacity:.9">(مزود IT)</span>
          ▾
        </button>

        <div class="it-user-menu" id="itUserMenu">
          <a href="/mutadarrib/profile.php">👤 <span>الملف الشخصي</span></a>
          <a href="/mutadarrib/it/dashboard.php">📊 <span>لوحة مزود IT</span></a>
          <a class="danger" href="/mutadarrib/auth/logout.php">🚪 <span>تسجيل الخروج</span></a>
        </div>
      </div>
    </div>
  </header>

  <!-- ===== Body ===== -->
  <div class="it-body">

    <!-- Sidebar -->
    <aside class="it-sidebar">
      <div class="it-sidecard">
        <h3>لوحة التحكم</h3>
        <p>التحكم الكامل بفرص التدريب والمتقدمين وإدارة الشركة.</p>
      </div>

      <ul class="it-nav">
        <div class="group-title">الرئيسية</div>
        <li>
          <a href="/mutadarrib/it/dashboard.php" class="<?= (strpos($_SERVER['REQUEST_URI'] ?? '', 'dashboard.php') !== false) ? 'active' : '' ?>">
            <span class="label">🏠 <span class="text">الرئيسية</span></span>
          </a>
        </li>

        <div class="group-title">إدارة</div>
        <li>
          <a href="/mutadarrib/it/it_internship_create.php" class="<?= (strpos($_SERVER['REQUEST_URI'] ?? '', 'it_internship_create.php') !== false) ? 'active' : '' ?>">
            <span class="label">➕ <span class="text">إضافة فرصة تدريب</span></span>
          </a>
        </li>

        <li>
          <a href="/mutadarrib/it/provider_internships.php" class="<?= (strpos($_SERVER['REQUEST_URI'] ?? '', 'provider_internships.php') !== false) ? 'active' : '' ?>">
            <span class="label">📋 <span class="text">فرصي التدريبية</span></span>
          </a>
        </li>

        <li>
          <a href="/mutadarrib/it/it_provider_applicants.php" class="<?= (strpos($_SERVER['REQUEST_URI'] ?? '', 'it_provider_applicants.php') !== false) ? 'active' : '' ?>">
            <span class="label">🧾 <span class="text">طلبات المتقدمين</span></span>
          </a>
        </li>

        <div class="group-title">أخرى</div>
        <li>
          <a href="/mutadarrib/calendar/calendar.php" class="<?= (strpos($_SERVER['REQUEST_URI'] ?? '', '/calendar/') !== false) ? 'active' : '' ?>">
            <span class="label">🗓️ <span class="text">التقويم</span></span>
          </a>
        </li>

        

        <li>
          <a href="/mutadarrib/index.php">
            <span class="label">↩️ <span class="text">العودة للموقع</span></span>
          </a>
        </li>
      </ul>
    </aside>

    <!-- Main content wrapper -->
    <!-- في صفحاتك: ضع المحتوى داخل <main class="it-main"> ... </main> -->
    <!-- إذا أنت بتستخدم include لهذا الملف، اكتب محتوى الصفحة بعد include مباشرة داخل it-main -->
    <main class="it-main">
      <div class="it-main-head">
        <div>
          <h1>لوحة مزود IT</h1>
          <p>اختر من القائمة لإدارة فرص التدريب والمتقدمين.</p>
        </div>
      </div>

      <!-- مثال: ضع هنا محتوى dashboard أو أي صفحة -->
      <!-- محتوى الصفحة الفعلي يُفضّل يجي من الصفحة نفسها -->
