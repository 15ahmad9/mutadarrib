<?php
require_once __DIR__ . '/../includes/theme_init.php';
require_once __DIR__ . "/includes/auth_check.php";

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$provider_user_id = (int)($_SESSION['user_id'] ?? 0);

/* =========================================================
   Toggle status قبل أي output
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status_id'])) {
  $internship_id = (int)$_POST['toggle_status_id'];

  $stmtOwn = $pdo->prepare("
    SELECT internship_id, status
    FROM it_internships
    WHERE internship_id = ? AND provider_user_id = ?
    LIMIT 1
  ");
  $stmtOwn->execute([$internship_id, $provider_user_id]);
  $own = $stmtOwn->fetch(PDO::FETCH_ASSOC);

  if ($own) {
    $newStatus = ($own['status'] === 'published') ? 'closed' : 'published';
    $stmtUpd = $pdo->prepare("
      UPDATE it_internships
      SET status = ?, published_at = IF(?='published', NOW(), published_at), updated_at = NOW()
      WHERE internship_id = ? AND provider_user_id = ?
      LIMIT 1
    ");
    $stmtUpd->execute([$newStatus, $newStatus, $internship_id, $provider_user_id]);
  }

  // ارجع لنفس الصفحة مع الحفاظ على الفلاتر
  $qs = $_SERVER['QUERY_STRING'] ?? '';
  $back = "/mutadarrib/it/provider_internships.php" . ($qs ? "?$qs&updated=1" : "?updated=1");
  header("Location: $back");
  exit;
}

/* =========================================================
   Filters
========================================================= */
$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? 'all');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$allowedStatus = ['all','published','draft','closed'];
if (!in_array($status, $allowedStatus, true)) $status = 'all';

// ===== Build WHERE =====
$where = " WHERE i.provider_user_id = ? ";
$params = [$provider_user_id];

if ($status !== 'all') {
  $where .= " AND i.status = ? ";
  $params[] = $status;
}

if ($q !== '') {
  $where .= " AND (i.title LIKE ? OR i.description LIKE ?) ";
  $like = "%$q%";
  $params[] = $like;
  $params[] = $like;
}

// ===== Count total =====
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM it_internships i $where");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// ===== Fetch list =====
$stmt = $pdo->prepare("
  SELECT
    i.internship_id,
    i.title,
    i.city,
    i.internship_type,
    i.status,
    i.published_at,
    i.created_at,
    (SELECT COUNT(*) FROM it_applications a WHERE a.internship_id = i.internship_id) AS applicants_count
  FROM it_internships i
  $where
  ORDER BY i.internship_id DESC
  LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$typeAr = function($t){
  return ($t === 'remote') ? 'عن بُعد' : (($t === 'hybrid') ? 'هجين' : 'حضوري');
};
$statusAr = function($s){
  return ($s === 'published') ? 'منشورة' : (($s === 'closed') ? 'مغلقة' : (($s === 'draft') ? 'مسودة' : $s));
};

$flash = "";
if (isset($_GET['updated']) && $_GET['updated'] == 1) {
  $flash = "<p class='success'>✅ تم تحديث حالة الفرصة بنجاح.</p>";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>فرصي التدريبية</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
  <link rel="stylesheet" href="/mutadarrib/assets/css/admin.css">
  <link rel="stylesheet" href="/mutadarrib/assets/css/it.css">
</head>

<body data-theme="<?= h($theme) ?>">
<div class="it-shell" id="itShell">

  <?php include __DIR__ . "/includes/header.php"; ?>

  <div class="it-body">
    <?php include __DIR__ . "/includes/sidebar.php"; ?>

    <main class="it-main">

      <div class="it-main-head">
        <div>
          <h1>فرصي التدريبية</h1>
          <p>إدارة كل فرص التدريب الخاصة بك (بحث + فلترة + تعديل + المتقدمين).</p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap">
          <a class="btn btn-primary" href="/mutadarrib/it/it_internship_create.php">+ إضافة فرصة تدريب</a>
          <a class="btn btn-ghost" href="/mutadarrib/it/dashboard.php">لوحة المزود</a>
        </div>
      </div>

      <?php if ($flash) echo $flash; ?>

      <form method="GET" class="filters" style="margin:10px 0 14px; display:flex; gap:10px; flex-wrap:wrap; align-items:center">
        <input type="text" name="q" placeholder="ابحث بالعنوان أو الوصف..." value="<?= h($q) ?>" class="inp">
        <select name="status" class="inp">
          <option value="all" <?= ($status==='all'?'selected':'') ?>>كل الحالات</option>
          <option value="published" <?= ($status==='published'?'selected':'') ?>>منشورة</option>
          <option value="draft" <?= ($status==='draft'?'selected':'') ?>>مسودة</option>
          <option value="closed" <?= ($status==='closed'?'selected':'') ?>>مغلقة</option>
        </select>
        <button class="btn btn-outline" type="submit">بحث</button>
        <a class="btn btn-ghost" href="/mutadarrib/it/provider_internships.php">إعادة ضبط</a>
      </form>

      <div class="panel">
        <div class="panel-head" style="display:flex;justify-content:space-between;align-items:flex-end;gap:10px;flex-wrap:wrap">
          <div>
            <h2 class="panel-title">النتائج</h2>
            <p class="panel-sub">عدد الفرص: <strong><?= $total ?></strong></p>
          </div>
          <div class="muted">صفحة <?= $page ?> من <?= $totalPages ?></div>
        </div>

        <?php if (!$rows): ?>
          <div class="empty-state">لا توجد فرص مطابقة للبحث/الفلترة.</div>
        <?php else: ?>

          <div class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>العنوان</th>
                  <th>المدينة</th>
                  <th>النوع</th>
                  <th>الحالة</th>
                  <th>المتقدمون</th>
                  <th>تاريخ النشر</th>
                  <th>إجراءات</th>
                </tr>
              </thead>

              <tbody>
                <?php foreach ($rows as $it): ?>
                  <tr>
                    <td>
                      <strong><?= h($it['title']) ?></strong>
                      <div class="muted">#<?= (int)$it['internship_id'] ?> — أضيفت: <?= h($it['created_at'] ?? '-') ?></div>
                    </td>

                    <td><?= h($it['city'] ?? '-') ?></td>

                    <td><?= h($typeAr($it['internship_type'])) ?></td>

                    <td>
                      <span class="badge <?= ($it['status']==='published'?'badge-green':($it['status']==='draft'?'badge-blue':'badge-gray')) ?>">
                        <?= h($statusAr($it['status'])) ?>
                      </span>
                    </td>

                    <td><span class="badge badge-blue"><?= (int)$it['applicants_count'] ?></span></td>

                    <td><?= h($it['published_at'] ?? '-') ?></td>

                    <td class="actions-cell">
                      <a class="btn btn-outline btn-sm" href="/mutadarrib/it/it_provider_applicants.php?internship_id=<?= (int)$it['internship_id'] ?>">
                        المتقدمون
                      </a>

                      <a class="btn btn-outline btn-sm" href="/mutadarrib/it/it_internship_edit.php?id=<?= (int)$it['internship_id'] ?>">
                        ✏️ تعديل
                      </a>

                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="toggle_status_id" value="<?= (int)$it['internship_id'] ?>">
                        <button type="submit" class="btn btn-ghost btn-sm">
                          <?= ($it['status']==='published') ? 'إغلاق' : 'نشر' ?>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
              <?php
                $base = "/mutadarrib/it/provider_internships.php?";
                $keep = $_GET;
                unset($keep['page'], $keep['updated']);
                foreach (['created','updated'] as $x) unset($keep[$x]);
                $qsBase = http_build_query($keep);
                $qsBase = $qsBase ? $qsBase . "&" : "";
              ?>

              <?php for ($p=1; $p<=$totalPages; $p++): ?>
                <a class="btn <?= ($p===$page?'btn-primary':'btn-ghost') ?> btn-sm"
                   href="<?= $base . $qsBase . "page=" . $p ?>">
                  <?= $p ?>
                </a>
              <?php endfor; ?>
            </div>
          <?php endif; ?>

        <?php endif; ?>
      </div>

    </main>
  </div>

  <?php include __DIR__ . "/includes/footer.php"; ?>
</div>
</body>
</html>
