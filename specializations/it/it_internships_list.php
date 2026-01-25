<?php
require_once __DIR__ . '/../../includes/theme_init.php';
require_once("../../config/db.php");
  include("../../includes/header.php"); 

// ===== Filters =====
$q    = trim($_GET['q'] ?? '');
$city = trim($_GET['city'] ?? '');
$type = trim($_GET['type'] ?? ''); // onsite|remote|hybrid
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

// ===== Build WHERE =====
$where = [];
$params = [];

$where[] = "i.status = 'published'";

if ($q !== '') {
    $where[] = "(i.title LIKE ? OR i.description LIKE ? OR i.field LIKE ? OR i.required_skills LIKE ? OR p.company_name LIKE ?)";
    $like = "%{$q}%";
    array_push($params, $like, $like, $like, $like, $like);
}

if ($city !== '') {
    $where[] = "i.city = ?";
    $params[] = $city;
}

if ($type !== '' && in_array($type, ['onsite','remote','hybrid'], true)) {
    $where[] = "i.internship_type = ?";
    $params[] = $type;
}

$whereSql = "WHERE " . implode(" AND ", $where);

// ===== Total count =====
$stmtCount = $pdo->prepare("
    SELECT COUNT(*) 
    FROM it_internships i
    JOIN it_providers p ON p.user_id = i.provider_user_id
    $whereSql
");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// ===== Fetch rows =====
$stmt = $pdo->prepare("
    SELECT
      i.internship_id,
      i.title,
      i.field,
      i.internship_type,
      i.city,
      i.country,
      i.duration_weeks,
      i.start_date,
      i.end_date,
      i.required_skills,
      i.published_at,
      p.company_name
    FROM it_internships i
    JOIN it_providers p ON p.user_id = i.provider_user_id
    $whereSql
    ORDER BY i.published_at DESC, i.internship_id DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== Cities for filter (dropdown) =====
$stmtCities = $pdo->query("
    SELECT DISTINCT city 
    FROM it_internships
    WHERE status='published' AND city IS NOT NULL AND city <> ''
    ORDER BY city ASC
");
$cities = $stmtCities->fetchAll(PDO::FETCH_COLUMN);

// ===== Simple auth context (optional) =====
// إذا عندك نظام جلسات: خليه زي ما عندك. هذا فقط مثال.
$loggedInRole = $_SESSION['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>فرص التدريب - قسم تكنولوجيا المعلومات</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body data-theme="<?= htmlspecialchars($theme) ?>">

<main class="page-shell">
  <header class="page-head">
    <h1 class="page-title">فرص التدريب التقنية</h1>
    <p class="page-subtitle">تصفّح فرص التدريب المتاحة حسب المجال، المدينة، ونوع التدريب.</p>
  </header>

  <!-- Filters -->
  <section class="filters-card">
    <form method="GET" class="filters-grid">
      <div class="filters-field">
        <label for="q">بحث</label>
        <input type="text" id="q" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="ابحث بالعنوان/المجال/المهارات...">
      </div>

      <div class="filters-field">
        <label for="city">المدينة</label>
        <select id="city" name="city">
          <option value="">الكل</option>
          <?php foreach ($cities as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= ($city === $c ? 'selected' : '') ?>>
              <?= htmlspecialchars($c) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="filters-field">
        <label for="type">نوع التدريب</label>
        <select id="type" name="type">
          <option value="">الكل</option>
          <option value="onsite" <?= ($type === 'onsite' ? 'selected' : '') ?>>حضوري</option>
          <option value="remote" <?= ($type === 'remote' ? 'selected' : '') ?>>عن بُعد</option>
          <option value="hybrid" <?= ($type === 'hybrid' ? 'selected' : '') ?>>هجين</option>
        </select>
      </div>

      <div class="filters-actions">
        <button class="btn btn-primary" type="submit">تطبيق</button>
        <a class="btn btn-ghost" href="it_internships_list.php">إعادة تعيين</a>
      </div>
    </form>
  </section>

  <!-- Results meta -->
  <div class="results-meta">
    <span>عدد النتائج: <strong><?= $total ?></strong></span>
    <span>الصفحة: <strong><?= $page ?></strong> / <?= $totalPages ?></span>
  </div>

  <!-- Cards -->
  <section class="cards-grid">
    <?php if (empty($rows)): ?>
      <div class="empty-state">
        لا توجد فرص مطابقة للبحث الحالي.
      </div>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <article class="card">
          <div class="card-top">
            <span class="pill"><?= htmlspecialchars($r['company_name']) ?></span>
            <span class="type-badge">
              <?php
                $t = $r['internship_type'];
                echo ($t === 'remote' ? 'عن بُعد' : ($t === 'hybrid' ? 'هجين' : 'حضوري'));
              ?>
            </span>
          </div>

          <h3 class="card-title"><?= htmlspecialchars($r['title']) ?></h3>

          <p class="card-meta">
            <?php if (!empty($r['field'])): ?>
              <span>المجال: <?= htmlspecialchars($r['field']) ?></span>
            <?php endif; ?>
            <?php if (!empty($r['city'])): ?>
              <span>• المدينة: <?= htmlspecialchars($r['city']) ?></span>
            <?php endif; ?>
            <?php if (!empty($r['duration_weeks'])): ?>
              <span>• المدة: <?= (int)$r['duration_weeks'] ?> أسبوع</span>
            <?php endif; ?>
          </p>

          <?php if (!empty($r['required_skills'])): ?>
            <p class="card-skills">
              <strong>مهارات مطلوبة:</strong>
              <?= htmlspecialchars(mb_strimwidth($r['required_skills'], 0, 120, '...')) ?>
            </p>
          <?php endif; ?>

          <div class="card-actions">
            <a class="btn btn-outline" href="it_internship_view.php?id=<?= (int)$r['internship_id'] ?>">عرض التفاصيل</a>

            <?php if ($loggedInRole === 'IT_Trainee'): ?>
              <a class="btn btn-primary" href="it_apply.php?internship_id=<?= (int)$r['internship_id'] ?>">قدّم الآن</a>
            <?php endif; ?>
          </div>

          <div class="card-foot">
            <small>تاريخ النشر: <?= htmlspecialchars($r['published_at']) ?></small>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>

  <!-- Pagination -->
  <?php
    // بناء رابط مع الحفاظ على الفلاتر
    $base = $_GET;
    unset($base['page']);
    $qsBase = http_build_query($base);
    $qsBase = $qsBase ? ($qsBase . '&') : '';
  ?>
  <?php if ($totalPages > 1): ?>
    <nav class="pagination">
      <?php if ($page > 1): ?>
        <a class="pg" href="?<?= $qsBase ?>page=<?= $page-1 ?>">السابق</a>
      <?php endif; ?>

      <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
        <a class="pg <?= ($i === $page ? 'active' : '') ?>" href="?<?= $qsBase ?>page=<?= $i ?>"><?= $i ?></a>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a class="pg" href="?<?= $qsBase ?>page=<?= $page+1 ?>">التالي</a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</main>

<style>
/* fallback styles إذا ما عندك ready في style.css */
.page-shell{max-width:1150px;margin:0 auto;padding:28px 16px 70px}
.page-head{text-align:center;margin:10px 0 22px}
.page-title{font-size:34px;margin:0 0 8px;color:#0b0f5c}
.page-subtitle{margin:0;color:#5b5f85}

.filters-card{background:#fff;border:1px solid rgba(15,23,42,.06);box-shadow:0 10px 26px rgba(0,0,0,.06);border-radius:18px;padding:16px;margin-bottom:14px}
.filters-grid{display:grid;grid-template-columns:1.4fr 1fr 1fr auto;gap:12px;align-items:end}
@media(max-width:980px){.filters-grid{grid-template-columns:1fr;}}
.filters-field label{display:block;font-weight:700;margin-bottom:6px;color:#1b2a7a}
.filters-field input,.filters-field select{width:100%;padding:12px 12px;border-radius:12px;border:1px solid rgba(15,23,42,.12)}
.filters-actions{display:flex;gap:10px;justify-content:flex-start}
.btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 16px;border-radius:14px;text-decoration:none;font-weight:800;border:0;cursor:pointer}
.btn-primary{background:#4154d0;color:#fff}
.btn-outline{background:#fff;color:#4154d0;border:2px solid #4154d0}
.btn-ghost{background:#f4f6ff;color:#1b2a7a}

.results-meta{display:flex;justify-content:space-between;color:#5b5f85;margin:10px 2px 14px}
.cards-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
@media(max-width:980px){.cards-grid{grid-template-columns:1fr}}
.card{background:#fff;border:1px solid rgba(15,23,42,.06);border-radius:18px;box-shadow:0 10px 26px rgba(0,0,0,.06);padding:16px}
.card-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.pill{padding:8px 12px;border-radius:999px;background:#f4f6ff;color:#1b2a7a;font-weight:800;font-size:13px}
.type-badge{padding:8px 12px;border-radius:999px;background:#eef1ff;color:#0b0f5c;font-weight:800;font-size:13px}
.card-title{margin:0 0 8px;color:#0b0f5c;font-size:18px;line-height:1.5}
.card-meta{margin:0 0 10px;color:#5b5f85}
.card-meta span{margin-left:8px}
.card-skills{margin:0 0 14px;color:#5b5f85;line-height:1.7}
.card-actions{display:flex;gap:10px;flex-wrap:wrap}
.card-foot{margin-top:10px;color:#8890b4}
.empty-state{padding:26px;background:#fff;border:1px dashed rgba(15,23,42,.18);border-radius:18px;color:#5b5f85}

.pagination{display:flex;gap:8px;justify-content:center;margin-top:18px;flex-wrap:wrap}
.pg{padding:10px 14px;border-radius:12px;border:1px solid rgba(15,23,42,.12);text-decoration:none;color:#0b0f5c;background:#fff;font-weight:800}
.pg.active{background:#4154d0;color:#fff;border-color:#4154d0}
</style>

<?php include("../../includes/footer.php"); ?>
</body>
</html>
