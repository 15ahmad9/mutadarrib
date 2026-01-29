<?php
require_once __DIR__ . '/../includes/theme_init.php';

session_start();
require_once("../config/db.php");

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// حماية: فقط IT_Provider
if (($_SESSION['role'] ?? null) !== 'IT_Provider') {
  header("Location: /mutadarrib/auth/login.php");
  exit;
}

$provider_user_id = (int)($_SESSION['user_id'] ?? 0);

// ===== جلب بيانات المزود =====
$stmtP = $pdo->prepare("SELECT * FROM it_providers WHERE user_id = ? LIMIT 1");
$stmtP->execute([$provider_user_id]);
$provider = $stmtP->fetch(PDO::FETCH_ASSOC);

if (!$provider) {
  header("Location: /mutadarrib/profile.php");
  exit;
}

// ====== إحصائيات ======
$stmtCnt1 = $pdo->prepare("SELECT COUNT(*) FROM it_internships WHERE provider_user_id = ?");
$stmtCnt1->execute([$provider_user_id]);
$totalInternships = (int)$stmtCnt1->fetchColumn();

$stmtCnt2 = $pdo->prepare("
  SELECT COUNT(*)
  FROM it_applications a
  JOIN it_internships i ON i.internship_id = a.internship_id
  WHERE i.provider_user_id = ?
");
$stmtCnt2->execute([$provider_user_id]);
$totalApplications = (int)$stmtCnt2->fetchColumn();

// ===== Flash message =====
$flash = "";
if (isset($_GET['created']) && $_GET['created'] == 1) {
  $flash = "<p class='success'>✅ تم إنشاء فرصة التدريب بنجاح!</p>";
}
if (isset($_GET['updated']) && $_GET['updated'] == 1) {
  $flash = "<p class='success'>✅ تم تحديث العملية بنجاح!</p>";
}

// ===== output =====
include __DIR__ . "/includes/it_provider_layout.php";
?>

<div class="it-main-head">
  <div>
    <h1>لوحة مزود IT</h1>
    <p>
      مرحباً <?= h($_SESSION['full_name'] ?? $provider['company_name']) ?>
      — <strong><?= h($provider['company_name']) ?></strong>
    </p>
  </div>

  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a class="btn btn-primary" href="/mutadarrib/it/it_internship_create.php">+ إضافة فرصة تدريب</a>
    <a class="btn btn-outline" href="/mutadarrib/it/provider_internships.php">إدارة الفرص</a>
    <a class="btn btn-outline" href="/mutadarrib/it/provider_internships.php?status=published">الفرص المنشورة</a>
    <a class="btn btn-ghost" href="/mutadarrib/auth/logout.php">تسجيل الخروج</a>
  </div>
</div>

<?php if ($flash) echo $flash; ?>

<section class="stats-grid">
  <div class="stat-card">
    <div class="stat-num"><?= $totalInternships ?></div>
    <div class="stat-label">عدد فرص التدريب</div>
  </div>

  <div class="stat-card">
    <div class="stat-num"><?= $totalApplications ?></div>
    <div class="stat-label">إجمالي الطلبات</div>
  </div>

  <div class="stat-card">
    <div class="stat-num"><?= h($provider['city'] ?? '-') ?></div>
    <div class="stat-label">المدينة</div>
  </div>
</section>

<style>
.btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 16px;border-radius:14px;text-decoration:none;font-weight:800;border:0;cursor:pointer}
.btn-primary{background:#4154d0;color:#fff}
.btn-outline{background:#fff;color:#4154d0;border:2px solid #4154d0}
.btn-ghost{background:#f4f6ff;color:#1b2a7a}

.stats-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin:14px 0}
@media(max-width:980px){.stats-grid{grid-template-columns:1fr}}
.stat-card{background:#fff;border:1px solid rgba(15,23,42,.06);border-radius:18px;box-shadow:0 10px 26px rgba(0,0,0,.06);padding:16px}
.stat-num{font-size:28px;font-weight:900;color:#0b0f5c;margin-bottom:6px}
.stat-label{color:#5b5f85;font-weight:700}
</style>

<?php include __DIR__ . "/includes/it_provider_layout_footer.php"; ?>
