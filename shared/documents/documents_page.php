<?php
require_once __DIR__ . '/../../includes/theme_init.php';

/**
 * Shared Documents Page
 * Wrappers must define:
 *  - $layout_header_path  (absolute filesystem path)
 *  - $layout_sidebar_path (absolute filesystem path)
 *  - $allowed_roles       (array of roles allowed)
 *  - $page_title          (string)
 */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . "/../../config/db.php";

// Defaults (لو لم يرسلها الـ wrapper)
$layout_header_path  = $layout_header_path  ?? null;
$layout_sidebar_path = $layout_sidebar_path ?? null;
$allowed_roles       = $allowed_roles       ?? ['admin','syndicate_admin'];
$page_title          = $page_title          ?? 'وثائق المستخدمين';

// صلاحيات
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowed_roles, true)) {
  header("Location: /mutadarrib/auth/login.php");
  exit;
}

$tab = $_GET['tab'] ?? 'trainees'; // trainees | lawyers
if (!in_array($tab, ['trainees','lawyers'], true)) $tab = 'trainees';

$search = trim($_GET['search'] ?? "");

// فلتر: غير مكتملين فقط
$onlyIncomplete = isset($_GET['only_incomplete']) ? 1 : 0;

// لتثبيت الفلاتر عند التنقل بين التبويبات
$commonQS = [];
if ($search !== "") $commonQS['search'] = $search;
if ($onlyIncomplete === 1) $commonQS['only_incomplete'] = 1;

// جلب البيانات
$params = [];

if ($tab === 'trainees') {

  $sql = "
    SELECT
      t.trainee_id,
      u.full_name,
      u.national_id,
      u.phone,
      u.email,

      u.profile_completed,
      u.profile_completed_at,

      t.no_conviction_doc,
      t.good_conduct_doc,
      t.updated_at,
      t.created_at
    FROM trainees t
    JOIN users u ON t.user_id = u.user_id
    WHERE t.is_archived = 0
  ";

  if ($onlyIncomplete === 1) {
    $sql .= " AND u.profile_completed = 0 ";
  }

  if ($search !== "") {
    $sql .= " AND (u.full_name LIKE :s OR u.national_id LIKE :s OR u.phone LIKE :s OR u.email LIKE :s) ";
    $params[":s"] = "%$search%";
  }

  $sql .= " ORDER BY u.full_name ASC ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {

  $sql = "
    SELECT
      l.lawyer_id,
      u.full_name,
      u.national_id,
      u.phone,
      u.email,

      u.profile_completed,
      u.profile_completed_at,

      l.syndicate_id,
      l.verified,
      l.no_conviction_doc,
      l.good_conduct_doc,
      l.updated_at,
      l.created_at
    FROM lawyers l
    JOIN users u ON l.user_id = u.user_id
    WHERE 1=1
  ";

  if ($onlyIncomplete === 1) {
    $sql .= " AND u.profile_completed = 0 ";
  }

  if ($search !== "") {
    $sql .= " AND (u.full_name LIKE :s OR u.national_id LIKE :s OR u.phone LIKE :s OR u.email LIKE :s) ";
    $params[":s"] = "%$search%";
  }

  $sql .= " ORDER BY u.full_name ASC ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/admin.css">
    <!-- <link rel="stylesheet" href="/mutadarrib/assets/css/lawyers.css"></head> -->
<body data-theme="<?= htmlspecialchars($theme) ?>">

<?php
// header من الـ wrapper
if ($layout_header_path && file_exists($layout_header_path)) {
  include $layout_header_path;
}
?>

<div class="admin-container">
  <div class="container">
  <?php
  // sidebar من الـ wrapper
  if ($layout_sidebar_path && file_exists($layout_sidebar_path)) {
    include $layout_sidebar_path;
  }
  ?>

  <div class="wrap">
    <div class="topbar">
      <div>
        <h2><?= htmlspecialchars($page_title) ?></h2>
      </div>

      <form method="GET" class="filter-row">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">

        <input type="text" name="search" placeholder="بحث بالاسم/الرقم الوطني/الهاتف/البريد..." value="<?= htmlspecialchars($search) ?>">

        <label class="check">
          <input type="checkbox" name="only_incomplete" value="1" <?= ($onlyIncomplete===1)?'checked':''; ?>>
          غير مكتملين فقط
        </label>

        <button type="submit">تطبيق</button>

        <?php if ($search !== "" || $onlyIncomplete===1): ?>
          <a class="btn btn-muted" href="?tab=<?= htmlspecialchars($tab) ?>">مسح الفلاتر</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="tabs" style="margin-top:10px;">
      <a href="?<?= htmlspecialchars(http_build_query(array_merge(['tab'=>'trainees'], $commonQS))) ?>"
         class="<?= $tab==='trainees'?'active':'' ?>">وثائق المتدربين</a>

      <a href="?<?= htmlspecialchars(http_build_query(array_merge(['tab'=>'lawyers'], $commonQS))) ?>"
         class="<?= $tab==='lawyers'?'active':'' ?>">وثائق المحامين</a>
    </div>

    <?php if (empty($rows)): ?>
      <p style="margin-top:15px;">لا توجد نتائج.</p>
    <?php else: ?>

      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>الاسم</th>
            <th>الرقم الوطني</th>
            <th>الهاتف</th>
            <th>البريد</th>

            <th>إكمال الملف</th>
            <th>تاريخ الإكمال</th>

            <?php if ($tab === 'lawyers'): ?>
              <th>رقم النقابة</th>
              <th>موثق</th>
            <?php endif; ?>

            <th>عدم محكومية</th>
            <th>حسن سيرة</th>
            <th>آخر تحديث</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $i => $r): ?>
          <?php
            $id = ($tab === 'trainees') ? (int)$r['trainee_id'] : (int)$r['lawyer_id'];
            $entity = ($tab === 'trainees') ? 'trainee' : 'lawyer';

            $hasNo   = !empty($r['no_conviction_doc']);
            $hasGood = !empty($r['good_conduct_doc']);

            $noBadge   = $hasNo   ? '<span class="badge ok">مرفوع</span>' : '<span class="badge no">غير مرفوع</span>';
            $goodBadge = $hasGood ? '<span class="badge ok">مرفوع</span>' : '<span class="badge no">غير مرفوع</span>';

            $completed = (int)($r['profile_completed'] ?? 0) === 1;
            $profBadge = $completed ? '<span class="badge ok">مكتمل</span>' : '<span class="badge no">غير مكتمل</span>';
            $completedAt = $r['profile_completed_at'] ?? null;

            $base = "/mutadarrib/secure_download.php?entity={$entity}&id={$id}";
          ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($r['full_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['national_id'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['phone'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['email'] ?? '-') ?></td>

            <td><?= $profBadge ?></td>
            <td><?= htmlspecialchars($completedAt ?: '-') ?></td>

            <?php if ($tab === 'lawyers'): ?>
              <td><?= htmlspecialchars($r['syndicate_id'] ?? '-') ?></td>
              <td><?= ((int)($r['verified'] ?? 0)===1) ? '<span class="badge ok">نعم</span>' : '<span class="badge no">لا</span>' ?></td>
            <?php endif; ?>

            <td><?= $noBadge ?></td>
            <td><?= $goodBadge ?></td>

            <td><?= htmlspecialchars($r['updated_at'] ?? $r['created_at'] ?? '-') ?></td>

            <td>
              <?php if ($hasNo): ?>
                <a class="btn btn-view" target="_blank" href="<?= $base ?>&doc=no_conviction&disp=inline">عرض عدم محكومية</a>
                <a class="btn btn-dl" href="<?= $base ?>&doc=no_conviction&disp=attachment">تنزيل</a>
              <?php else: ?>
                <span class="btn btn-muted" style="cursor:default;">لا يوجد</span>
              <?php endif; ?>

              <?php if ($hasGood): ?>
                <a class="btn btn-view" target="_blank" href="<?= $base ?>&doc=good_conduct&disp=inline">عرض حسن سيرة</a>
                <a class="btn btn-dl" href="<?= $base ?>&doc=good_conduct&disp=attachment">تنزيل</a>
              <?php else: ?>
                <span class="btn btn-muted" style="cursor:default;">لا يوجد</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

    <?php endif; ?>

  </div>
</div>
</div>
<?php include("../../includes/footer.php"); ?>
</body>

</html>
