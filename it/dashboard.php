<?php
require_once __DIR__ . "/includes/auth_check.php";

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$provider_user_id = (int)($_SESSION['user_id'] ?? 0);

// ===== جلب بيانات المزود =====
$stmtP = $pdo->prepare("SELECT * FROM it_providers WHERE user_id = ? LIMIT 1");
$stmtP->execute([$provider_user_id]);
$provider = $stmtP->fetch(PDO::FETCH_ASSOC);

if (!$provider) {
  header("Location: /mutadarrib/profile.php");
  exit;
}

// ===== Flash message =====
$flash = "";
if (isset($_GET['created']) && $_GET['created'] == 1) $flash = "✅ تم إنشاء فرصة التدريب بنجاح!";
if (isset($_GET['updated']) && $_GET['updated'] == 1) $flash = "✅ تم تحديث العملية بنجاح!";

// =====================================================
// 1) إحصائيات فرص التدريب (إجمالي + حسب الحالة)
// =====================================================
$stmtIntern = $pdo->prepare("
  SELECT
    COUNT(*) AS total,
    SUM(status='published') AS published,
    SUM(status='closed')    AS closed,
    SUM(status='draft')     AS draft
  FROM it_internships
  WHERE provider_user_id = ?
");
$stmtIntern->execute([$provider_user_id]);
$internStats = $stmtIntern->fetch(PDO::FETCH_ASSOC) ?: ['total'=>0,'published'=>0,'closed'=>0,'draft'=>0];

// =====================================================
// 2) إحصائيات الطلبات (إجمالي + حسب الحالة)
// =====================================================
$stmtApps = $pdo->prepare("
  SELECT
    COUNT(*) AS total,
    SUM(a.status='submitted')    AS submitted,
    SUM(a.status='under_review') AS under_review,
    SUM(a.status='accepted')     AS accepted,
    SUM(a.status='rejected')     AS rejected
  FROM it_applications a
  JOIN it_internships i ON i.internship_id = a.internship_id
  WHERE i.provider_user_id = ?
");
$stmtApps->execute([$provider_user_id]);
$appStats = $stmtApps->fetch(PDO::FETCH_ASSOC) ?: ['total'=>0,'submitted'=>0,'under_review'=>0,'accepted'=>0,'rejected'=>0];

// =====================================================
// 3) طلبات جديدة آخر 7 أيام (للكرت الخامس)
// =====================================================
$stmtNew7 = $pdo->prepare("
  SELECT COUNT(*)
  FROM it_applications a
  JOIN it_internships i ON i.internship_id = a.internship_id
  WHERE i.provider_user_id = ?
    AND a.applied_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$stmtNew7->execute([$provider_user_id]);
$newLast7Days = (int)$stmtNew7->fetchColumn();

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>لوحة مزود IT</title>

  <!-- ستايل الموقع -->
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
  <!-- ستايل الأدمن (يعطي شكل الصورة) -->
  <link rel="stylesheet" href="/mutadarrib/assets/css/admin.css">

  <style>
    /* تحسينات بسيطة للبطاقات لتطلع مثل الصورة أكثر */
    .dash-grid{gap:22px;}
    .dash-card{border-radius:18px;}
    .dash-card-total{font-size:44px;}
    .dash-breakdown .dash-row{padding:10px 0;border-bottom:1px solid rgba(0,0,0,.06);}
    .dash-breakdown .dash-row:last-child{border-bottom:0;}
    .dash-actions-row{
      display:flex; gap:10px; flex-wrap:wrap;
      justify-content:center; margin: 14px 0 6px;
    }
    .dash-actions-row a{
      text-decoration:none;
      padding:10px 14px;
      border-radius:14px;
      font-weight:800;
      border:1px solid rgba(0,0,0,.08);
      background: rgba(255,255,255,.7);
    }
    [data-theme="dark"] .dash-actions-row a{
      background: rgba(255,255,255,.06);
      border-color: rgba(255,255,255,.12);
    }
  </style>
</head>

<body data-theme="<?= h($theme) ?>">

<?php include __DIR__ . "/includes/header.php"; ?>
<?php include __DIR__ . "/includes/sidebar.php"; ?>

<div class="admin-container">
  <div class="dashboard-container">

    <h1>لوحة مزود IT</h1>
    <p style="text-align:center;margin:-6px 0 10px;color:rgba(0,0,0,.55);font-weight:700;">
      مرحباً <?= h($_SESSION['full_name'] ?? $provider['company_name']) ?> — <strong><?= h($provider['company_name']) ?></strong>
    </p>

    <?php if ($flash): ?>
      <div class="flash-ok" style="text-align:center;"><?= h($flash) ?></div>
    <?php endif; ?>

    <div class="dash-actions-row">
      <a href="/mutadarrib/it/it_internship_create.php">➕ إضافة فرصة تدريب</a>
      <a href="/mutadarrib/it/provider_internships.php">📋 إدارة الفرص</a>
      <a href="/mutadarrib/it/provider_internships.php?status=published">✅ الفرص المنشورة</a>
      <a href="/mutadarrib/it/it_provider_applicants.php">🧾 طلبات المتقدمين</a>
    </div>

    <div class="dash-grid">

      <!-- 1) كرت: فرص التدريب -->
      <div class="dash-card">
        <div class="dash-card-head">
          <div class="dash-card-title">
            <span class="dash-icon" data-icon="training"></span>
            فرص التدريب
          </div>
        </div>

        <div class="dash-card-total"><?= (int)$internStats['total'] ?></div>

        <div class="dash-breakdown">
          <div class="dash-row"><span>منشورة</span><strong><?= (int)$internStats['published'] ?></strong></div>
          <div class="dash-row"><span>مغلقة</span><strong><?= (int)$internStats['closed'] ?></strong></div>
          <div class="dash-row"><span>مسودة</span><strong><?= (int)$internStats['draft'] ?></strong></div>
        </div>
      </div>

      <!-- 2) كرت: طلبات المتقدمين -->
      <div class="dash-card">
        <div class="dash-card-head">
          <div class="dash-card-title">
            <span class="dash-icon" data-icon="users"></span>
            طلبات المتقدمين
          </div>
        </div>

        <div class="dash-card-total"><?= (int)$appStats['total'] ?></div>

        <div class="dash-breakdown">
          <div class="dash-row"><span>مُرسل</span><strong><?= (int)$appStats['submitted'] ?></strong></div>
          <div class="dash-row"><span>قيد المراجعة</span><strong><?= (int)$appStats['under_review'] ?></strong></div>
          <div class="dash-row"><span>مقبول</span><strong><?= (int)$appStats['accepted'] ?></strong></div>
          <div class="dash-row"><span>مرفوض</span><strong><?= (int)$appStats['rejected'] ?></strong></div>
        </div>
      </div>

      <!-- 3) كرت: المنشورة فقط -->
      <div class="dash-card">
        <div class="dash-card-head">
          <div class="dash-card-title">
            <span class="dash-icon" data-icon="dashboard"></span>
            الفرص المنشورة
          </div>
        </div>

        <div class="dash-card-total"><?= (int)$internStats['published'] ?></div>

        <div class="dash-breakdown">
          <div class="dash-row">
            <span>عرض المنشورة</span>
            <strong>
              <a href="/mutadarrib/it/provider_internships.php?status=published" style="text-decoration:none;">فتح</a>
            </strong>
          </div>
          <div class="dash-row">
            <span>إضافة فرصة</span>
            <strong>
              <a href="/mutadarrib/it/it_internship_create.php" style="text-decoration:none;">إضافة</a>
            </strong>
          </div>
        </div>
      </div>

      <!-- 4) كرت: قيد المراجعة (submitted + under_review) -->
      <?php $pendingApps = (int)$appStats['submitted'] + (int)$appStats['under_review']; ?>
      <div class="dash-card">
        <div class="dash-card-head">
          <div class="dash-card-title">
            <span class="dash-icon" data-icon="docs"></span>
            الطلبات قيد المراجعة
          </div>
        </div>

        <div class="dash-card-total"><?= $pendingApps ?></div>

        <div class="dash-breakdown">
          <div class="dash-row"><span>مُرسل</span><strong><?= (int)$appStats['submitted'] ?></strong></div>
          <div class="dash-row"><span>قيد المراجعة</span><strong><?= (int)$appStats['under_review'] ?></strong></div>
          <div class="dash-row">
            <span>فتح صفحة الطلبات</span>
            <strong>
              <a href="/mutadarrib/it/it_provider_applicants.php" style="text-decoration:none;">فتح</a>
            </strong>
          </div>
        </div>
      </div>

      <!-- 5) كرت: طلبات جديدة آخر 7 أيام -->
      <div class="dash-card">
        <div class="dash-card-head">
          <div class="dash-card-title">
            <span class="dash-icon" data-icon="users"></span>
            طلبات جديدة (7 أيام)
          </div>
        </div>

        <div class="dash-card-total"><?= (int)$newLast7Days ?></div>

        <div class="dash-breakdown">
          <div class="dash-row">
            <span>ملاحظة</span>
            <strong>آخر أسبوع</strong>
          </div>
          <div class="dash-row">
            <span>فتح الطلبات</span>
            <strong>
              <a href="/mutadarrib/it/it_provider_applicants.php" style="text-decoration:none;">فتح</a>
            </strong>
          </div>
        </div>
      </div>

    </div>

  </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>
</body>
</html>
