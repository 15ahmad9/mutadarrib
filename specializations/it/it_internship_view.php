<?php
require_once __DIR__ . '/../../includes/theme_init.php';

session_start();
require_once("../../config/db.php");
  include("../../includes/header.php");

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  echo "<p class='error' style='padding:20px'>معرّف الفرصة غير صحيح.</p>";
  include("../includes/footer.php");
  exit;
}

// جلب الفرصة + معلومات الشركة
$stmt = $pdo->prepare("
  SELECT
    i.*,
    p.company_name,
    p.website AS provider_website,
    p.city AS provider_city,
    p.country AS provider_country
  FROM it_internships i
  JOIN it_providers p ON p.user_id = i.provider_user_id
  WHERE i.internship_id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$internship = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$internship) {
  echo "<p class='error' style='padding:20px'>الفرصة غير موجودة.</p>";
  include("../includes/footer.php");
  exit;
}

// السماح بعرض تفاصيل المنشور فقط (إلا إذا كنت admin مثلاً)
$role = $_SESSION['role'] ?? null;
if ($internship['status'] !== 'published' && $role !== 'admin') {
  echo "<p class='error' style='padding:20px'>هذه الفرصة غير متاحة حالياً.</p>";
  include("../includes/footer.php");
  exit;
}

// تحويل نوع التدريب لنص عربي
$typeAr = ($internship['internship_type'] === 'remote') ? 'عن بُعد' : (($internship['internship_type'] === 'hybrid') ? 'هجين' : 'حضوري');

// هل المستخدم IT_Trainee؟
$isITTra = ($role === 'IT_Trainee');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title><?= h($internship['title']) ?> - تفاصيل الفرصة</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-theme="<?= h($theme) ?>">

<main class="page-shell">
  <a href="it_internships_list.php" class="btn btn-ghost" style="margin-bottom:12px; display:inline-flex;">← الرجوع للفرص</a>

  <section class="detail-card">
    <div class="detail-top">
      <div>
        <h1 class="detail-title"><?= h($internship['title']) ?></h1>
        <p class="detail-sub">
          <span class="pill"><?= h($internship['company_name']) ?></span>
          <span class="pill pill-soft"><?= h($typeAr) ?></span>
          <?php if (!empty($internship['field'])): ?>
            <span class="pill pill-soft">المجال: <?= h($internship['field']) ?></span>
          <?php endif; ?>
        </p>
      </div>

      <div class="detail-actions">
        <?php if ($isITTra): ?>
          <a class="btn btn-primary" href="it_apply.php?internship_id=<?= (int)$internship['internship_id'] ?>">قدّم الآن</a>
        <?php else: ?>
          <a class="btn btn-outline" href="/mutadarrib/auth/login.php">سجّل الدخول كمتدرب IT للتقديم</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="detail-grid">
      <div class="detail-box">
        <h3>الوصف</h3>
        <p><?= nl2br(h($internship['description'])) ?></p>
      </div>

      <div class="detail-box">
        <h3>تفاصيل سريعة</h3>
        <ul class="detail-list">
          <?php if (!empty($internship['city'])): ?>
            <li>المدينة: <strong><?= h($internship['city']) ?></strong></li>
          <?php endif; ?>
          <?php if (!empty($internship['country'])): ?>
            <li>الدولة: <strong><?= h($internship['country']) ?></strong></li>
          <?php endif; ?>
          <?php if (!empty($internship['duration_weeks'])): ?>
            <li>المدة: <strong><?= (int)$internship['duration_weeks'] ?> أسبوع</strong></li>
          <?php endif; ?>
          <?php if (!empty($internship['start_date'])): ?>
            <li>تاريخ البدء: <strong><?= h($internship['start_date']) ?></strong></li>
          <?php endif; ?>
          <?php if (!empty($internship['end_date'])): ?>
            <li>تاريخ الانتهاء: <strong><?= h($internship['end_date']) ?></strong></li>
          <?php endif; ?>
          <?php if (!empty($internship['seats'])): ?>
            <li>عدد المقاعد: <strong><?= (int)$internship['seats'] ?></strong></li>
          <?php endif; ?>
          <li>تاريخ النشر: <strong><?= h($internship['published_at']) ?></strong></li>
        </ul>
      </div>

      <?php if (!empty($internship['required_skills'])): ?>
        <div class="detail-box">
          <h3>المهارات المطلوبة</h3>
          <p><?= nl2br(h($internship['required_skills'])) ?></p>
        </div>
      <?php endif; ?>

      <div class="detail-box">
        <h3>عن الشركة</h3>
        <p><strong><?= h($internship['company_name']) ?></strong></p>
        <p>
          <?php if (!empty($internship['provider_city']) || !empty($internship['provider_country'])): ?>
            الموقع: <?= h(trim(($internship['provider_city'] ?? '') . ' ' . ($internship['provider_country'] ?? ''))) ?><br>
          <?php endif; ?>

          <?php if (!empty($internship['provider_website'])): ?>
            الموقع الإلكتروني:
            <a href="<?= h($internship['provider_website']) ?>" target="_blank" rel="noopener noreferrer">
              <?= h($internship['provider_website']) ?>
            </a>
          <?php endif; ?>
        </p>
      </div>
    </div>
  </section>
</main>

<style>
/* fallback styles */
.page-shell{max-width:1100px;margin:0 auto;padding:28px 16px 70px}
.btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 16px;border-radius:14px;text-decoration:none;font-weight:800;border:0;cursor:pointer}
.btn-primary{background:#4154d0;color:#fff}
.btn-outline{background:#fff;color:#4154d0;border:2px solid #4154d0}
.btn-ghost{background:#f4f6ff;color:#1b2a7a}
.detail-card{background:#fff;border:1px solid rgba(15,23,42,.06);border-radius:18px;box-shadow:0 10px 26px rgba(0,0,0,.06);padding:18px}
.detail-top{display:flex;gap:16px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap}
.detail-title{margin:0 0 10px;color:#0b0f5c;font-size:28px}
.detail-sub{margin:0;display:flex;gap:8px;flex-wrap:wrap}
.pill{padding:8px 12px;border-radius:999px;background:#f4f6ff;color:#1b2a7a;font-weight:800;font-size:13px}
.pill-soft{background:#eef1ff;color:#0b0f5c}
.detail-grid{display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-top:14px}
@media(max-width:980px){.detail-grid{grid-template-columns:1fr}}
.detail-box{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:14px;padding:14px}
.detail-box h3{margin:0 0 10px;color:#0b0f5c}
.detail-box p{margin:0;color:#5b5f85;line-height:1.8}
.detail-list{margin:0;padding:0 18px;color:#5b5f85;line-height:1.9}
</style>

<?php include("../../includes/footer.php"); ?>
</body>
</html>
