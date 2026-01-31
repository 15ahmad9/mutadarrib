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

<style>
/* ====== Layout ====== */
:root{
  --it-blue-900:#0b0f5c;
  --it-blue-800:#141a73;
  --it-blue-700:#1a2390;
  --it-blue-600:#2d3bb8;
  --it-bg:#f6f7ff;
  --it-card:#ffffff;
  --it-text:#0f172a;
  --it-muted:#6b7280;
  --it-border: rgba(15,23,42,.10);
  --it-shadow: 0 10px 26px rgba(0,0,0,.07);
}

.it-shell{min-height:100vh;background:var(--it-bg);}

/* Header */
.it-topbar{
  position:sticky; top:0; z-index:50;
  height:64px;
  background: linear-gradient(90deg, #1a1f8a 0%, #2e3dbd 60%, #3f51d8 100%);
  color:#fff;
  display:flex; align-items:center; justify-content:space-between;
  padding:0 16px;
  box-shadow: 0 10px 18px rgba(0,0,0,.12);
}

.it-topbar .left{
  display:flex; align-items:center; gap:10px;
}
.it-topbar .right{
  display:flex; align-items:center; gap:10px;
}

.it-brand{
  display:flex; align-items:center; gap:10px;
  font-weight:900; letter-spacing:.2px;
}
.it-brand .logo{
  width:38px; height:38px; border-radius:14px;
  background: rgba(255,255,255,.14);
  display:flex; align-items:center; justify-content:center;
  font-size:18px;
  border:1px solid rgba(255,255,255,.22);
}
.it-brand .title{
  display:flex; flex-direction:column; line-height:1.1;
}
.it-brand .title b{font-size:14px}
.it-brand .title span{font-size:12px; opacity:.9}

.it-iconbtn{
  border:1px solid rgba(255,255,255,.18);
  background: rgba(255,255,255,.12);
  color:#fff;
  border-radius:12px;
  padding:10px 12px;
  cursor:pointer;
  font-weight:900;
}
.it-iconbtn:hover{ background: rgba(255,255,255,.18); }

.it-user{
  position:relative;
}
.it-user-btn{
  border:1px solid rgba(255,255,255,.18);
  background: rgba(255,255,255,.12);
  color:#fff;
  border-radius:12px;
  padding:10px 12px;
  cursor:pointer;
  font-weight:900;
  display:flex; align-items:center; gap:8px;
}
.it-user-menu{
  position:absolute;
  top:56px;
  left:0;
  width:220px;
  background:#fff;
  border:1px solid var(--it-border);
  border-radius:14px;
  box-shadow: var(--it-shadow);
  padding:8px;
  display:none;
}
.it-user-menu.show{ display:block; }
.it-user-menu a{
  display:flex; gap:10px; align-items:center;
  padding:10px 10px;
  border-radius:12px;
  text-decoration:none;
  color:var(--it-text);
  font-weight:800;
}
.it-user-menu a:hover{ background:#f3f5ff; }
.it-user-menu .danger{ color:#b42318; }

/* Sidebar */
.it-body{
  display:grid;
  grid-template-columns: 280px 1fr;
  gap:16px;
  padding:16px;
}

.it-sidebar{
  position:sticky; top:80px;
  height: calc(100vh - 96px);
  background: linear-gradient(180deg, #1a1f8a 0%, #2d3bb8 55%, #0b0f5c 100%);
  border-radius:18px;
  box-shadow: var(--it-shadow);
  padding:14px;
  color:#fff;
  overflow:auto;
}

.it-sidecard{
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.18);
  border-radius:16px;
  padding:12px;
  margin-bottom:12px;
}
.it-sidecard h3{margin:0 0 6px;font-size:14px}
.it-sidecard p{margin:0;font-size:12px;opacity:.92;line-height:1.7}

.it-nav{
  list-style:none; padding:0; margin:0;
}
.it-nav .group-title{
  margin:14px 10px 8px;
  font-size:12px;
  opacity:.85;
  font-weight:900;
}
.it-nav a{
  display:flex; align-items:center; justify-content:space-between;
  gap:10px;
  padding:12px 12px;
  margin:6px 0;
  border-radius:14px;
  text-decoration:none;
  color:#fff;
  font-weight:900;
  background: rgba(255,255,255,.10);
  border:1px solid rgba(255,255,255,.12);
}
.it-nav a:hover{ background: rgba(255,255,255,.16); }
.it-nav a.active{ background: rgba(255,255,255,.22); border-color: rgba(255,255,255,.22); }

.it-nav .label{
  display:flex; align-items:center; gap:10px;
}
.it-nav .badge{
  background: rgba(255,255,255,.18);
  border:1px solid rgba(255,255,255,.22);
  border-radius:999px;
  padding:4px 10px;
  font-size:12px;
}

/* Main */
.it-main{
  background: var(--it-card);
  border:1px solid var(--it-border);
  border-radius:18px;
  box-shadow: var(--it-shadow);
  padding:16px;
  min-height: 520px;
}

.it-main-head{
  display:flex; align-items:flex-start; justify-content:space-between;
  gap:12px; flex-wrap:wrap;
  margin-bottom:12px;
}
.it-main-head h1{margin:0;color:var(--it-blue-900);font-size:26px}
.it-main-head p{margin:6px 0 0;color:var(--it-muted)}

/* Collapsed sidebar */
.it-shell.sidebar-collapsed .it-body{ grid-template-columns: 88px 1fr; }
.it-shell.sidebar-collapsed .it-sidebar{ padding:10px; }
.it-shell.sidebar-collapsed .it-sidecard{ display:none; }
.it-shell.sidebar-collapsed .it-nav .text{ display:none; }
.it-shell.sidebar-collapsed .it-nav a{ justify-content:center; }
.it-shell.sidebar-collapsed .it-nav .badge{ display:none; }

/* Responsive */
@media(max-width: 980px){
  .it-body{ grid-template-columns: 1fr; }
  .it-sidebar{ position:relative; top:auto; height:auto; }
}

/* =======================================================================
   Admin-like restyle for IT Provider panel (STYLE ONLY)
   ======================================================================= */

:root{
  --it-bg: var(--bg);
  --it-card: var(--surface);
  --it-text: var(--text);
  --it-muted: var(--muted);
  --it-border: var(--border);
  --it-shadow: var(--shadow);
  --it-grad-a: #070031;
  --it-grad-b: #556ab1;
  --it-accent: var(--primary);
}

.it-shell{background:var(--it-bg);}

.it-topbar{
  background: linear-gradient(250deg,var(--it-grad-a),var(--it-grad-b));
  border-bottom: 1px solid rgba(255,255,255,.14);
  box-shadow: 0 12px 30px rgba(0,0,0,.18);
}

.it-topnav a{
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.18);
  color:#fff;
  border-radius: 22px;
  padding: 10px 16px;
  transition: .2s ease;
  text-decoration: none;
}

.it-topnav a:hover{background:rgba(255,255,255,.18);}
.it-topnav a.active{background:rgba(255,255,255,.22);border-color:rgba(255,255,255,.28);}

.it-userbox,
.it-iconbtn{
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.18);
  color:#fff;
}

.it-iconbtn:hover{background: rgba(255,255,255,.18);}

.it-body{padding:24px;gap:24px;}

.it-sidebar{
  background: linear-gradient(210deg, rgba(7,0,49,.98), rgba(85,106,177,.96));
  box-shadow: 0 18px 40px rgba(0,0,0,.25);
  border: 1px solid rgba(255,255,255,.12);
}

.it-side-top{
  background: rgba(255,255,255,.10);
  border: 1px solid rgba(255,255,255,.14);
}

.it-side-link{
  background: rgba(255,255,255,.10);
  border: 1px solid rgba(255,255,255,.14);
  border-radius: 18px;
  padding: 14px 16px;
}

.it-side-link:hover{background: rgba(255,255,255,.16);}
.it-side-link.active{background: rgba(255,255,255,.20);border-color: rgba(255,255,255,.22);}

.it-side-ico{
  width: 34px;
  height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255,255,255,.14);
  border: 1px solid rgba(255,255,255,.18);
  border-radius: 12px;
  font-size: 18px;
  line-height: 1;
}

.it-main{
  background: var(--it-card);
  border: 1px solid var(--it-border);
  box-shadow: 0 18px 45px rgba(0,0,0,.10);
  border-radius: 26px;
}

/* Hide placeholder heading shipped inside layout (all IT pages already render their own heading) */
.it-main > .it-main-head:first-child{display:none;}

.it-main-head{
  margin-bottom: 18px;
  padding: 8px 0 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 12px;
}

.it-main-head > div:last-child{
  justify-content: center !important;
}

.it-main-head h1{
  margin: 0;
  font-size: 38px;
  letter-spacing: .2px;
  color: var(--it-text);
}

.it-main-head p{
  margin: 0;
  color: var(--it-muted);
  max-width: 760px;
}

.it-main .btn,
.it-main a.btn{
  border-radius: 16px;
  padding: 12px 18px;
  font-weight: 800;
  border: 1px solid transparent;
  box-shadow: 0 10px 20px rgba(0,0,0,.08);
}

.it-main .btn-primary{
  background: linear-gradient(135deg, rgba(7,0,49,.95), rgba(85,106,177,.95));
  color: #fff;
  border-color: rgba(255,255,255,.16);
}

.it-main .btn-primary:hover{filter: brightness(1.05);}

.it-main .btn-outline{
  background: rgba(255,255,255,.70);
  color: var(--it-grad-a);
  border-color: rgba(7,0,49,.25);
}

[data-theme="dark"] .it-main .btn-outline{
  background: rgba(255,255,255,.08);
  color: var(--it-text);
  border-color: rgba(255,255,255,.18);
}

.it-main .btn-ghost{
  background: rgba(255,255,255,.55);
  color: var(--it-grad-a);
  border-color: rgba(7,0,49,.12);
}

[data-theme="dark"] .it-main .btn-ghost{
  background: rgba(255,255,255,.06);
  color: var(--it-text);
  border-color: rgba(255,255,255,.12);
}

/* Dashboard cards (IT) – match Admin dashboard feeling */
.it-main .stats-grid{
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px;
  margin-top: 10px;
}

@media (max-width: 1100px){
  .it-main .stats-grid{grid-template-columns: 1fr;}
}

.it-main .stat-card{
  background: linear-gradient(180deg, rgba(255,255,255,.75), rgba(255,255,255,.62));
  border: 1px solid rgba(7,0,49,.10);
  border-radius: 22px;
  padding: 18px 18px;
  display: flex;
  flex-direction: row-reverse; /* put big number on the left like Admin dashboard */
  align-items: center;
  justify-content: space-between;
  min-height: 112px;
}

[data-theme="dark"] .it-main .stat-card{
  background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
  border-color: rgba(255,255,255,.10);
}

.it-main .stat-num{
  font-size: 40px;
  font-weight: 900;
  color: var(--it-text);
  min-width: 48px;
  text-align: left;
}

.it-main .stat-label{
  font-size: 14px;
  color: var(--it-muted);
  font-weight: 800;
}

</style>

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
