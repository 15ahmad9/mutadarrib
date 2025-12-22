<?php
/**
 * Shared Contact Messages Page
 * Wrappers must define:
 *  - $layout_header_path  (absolute filesystem path)
 *  - $layout_sidebar_path (absolute filesystem path)
 *  - $allowed_roles       (array of allowed roles)
 *  - $page_title          (string)
 */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . "/../../config/db.php";

// Defaults
$layout_header_path  = $layout_header_path  ?? null;
$layout_sidebar_path = $layout_sidebar_path ?? null;
$allowed_roles       = $allowed_roles       ?? ['admin','syndicate_admin'];
$page_title          = $page_title          ?? 'رسائل تواصل معنا';

// Auth
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowed_roles, true)) {
  header("Location: /mutadarrib/auth/login.php");
  exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Handle POST actions (change status)
$flash = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf'] ?? '';
  if (!hash_equals($csrf, $token)) {
    $flash = "فشل التحقق الأمني (CSRF). أعد المحاولة.";
  } else {
    $action = $_POST['action'] ?? '';
    if ($action === 'set_status') {
      $messageId = (int)($_POST['message_id'] ?? 0);
      $newStatus = $_POST['status'] ?? '';

      $allowedStatus = ['new','read','closed'];
      if ($messageId <= 0 || !in_array($newStatus, $allowedStatus, true)) {
        $flash = "طلب غير صالح.";
      } else {
        $stmtUp = $pdo->prepare("UPDATE contact_messages SET status = ? WHERE message_id = ?");
        $stmtUp->execute([$newStatus, $messageId]);

        // Redirect to avoid resubmission (preserve query string)
        $qs = $_GET;
        $qs['ok'] = 1;
        header("Location: ?" . http_build_query($qs));
        exit;
      }
    }
  }
}

// Inputs (filters)
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$allowedFilters = ['all','new','read','closed'];
if (!in_array($statusFilter, $allowedFilters, true)) $statusFilter = 'all';

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// View single message (optional)
$viewId = (int)($_GET['view'] ?? 0);
$viewRow = null;
if ($viewId > 0) {
  $stmtV = $pdo->prepare("SELECT * FROM contact_messages WHERE message_id = ? LIMIT 1");
  $stmtV->execute([$viewId]);
  $viewRow = $stmtV->fetch(PDO::FETCH_ASSOC);
}

// Build WHERE
$where = " WHERE 1=1 ";
$params = [];

if ($statusFilter !== 'all') {
  $where .= " AND status = ? ";
  $params[] = $statusFilter;
}

if ($search !== '') {
  $where .= " AND (
      name LIKE ?
      OR email LIKE ?
      OR phone LIKE ?
      OR subject LIKE ?
      OR message LIKE ?
    ) ";
  $s = "%{$search}%";
  array_push($params, $s, $s, $s, $s, $s);
}

// Counts by status (summary)
$counts = ['new'=>0,'read'=>0,'closed'=>0];
$stmtC = $pdo->query("SELECT status, COUNT(*) AS c FROM contact_messages GROUP BY status");
foreach ($stmtC->fetchAll(PDO::FETCH_ASSOC) as $r) {
  if (isset($counts[$r['status']])) $counts[$r['status']] = (int)$r['c'];
}

// Total for pagination
$sqlTotal = "SELECT COUNT(*) FROM contact_messages " . $where;
$stmtT = $pdo->prepare($sqlTotal);
$stmtT->execute($params);
$total = (int)$stmtT->fetchColumn();
$totalPages = (int)ceil($total / $perPage);
if ($totalPages < 1) $totalPages = 1;
if ($page > $totalPages) $page = $totalPages;

// List query
$sqlList = "
  SELECT message_id, user_id, name, email, phone, subject, status, created_at
  FROM contact_messages
  {$where}
  ORDER BY created_at DESC
  LIMIT :limit OFFSET :offset
";
$stmtL = $pdo->prepare($sqlList);
$idx = 1;
foreach ($params as $p) {
  $stmtL->bindValue($idx, $p, PDO::PARAM_STR);
  $idx++;
}
$stmtL->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmtL->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtL->execute();
$rows = $stmtL->fetchAll(PDO::FETCH_ASSOC);

// Preserve filters in links
$commonQS = [];
if ($search !== '') $commonQS['search'] = $search;
if ($statusFilter !== 'all') $commonQS['status'] = $statusFilter;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title><?= h($page_title) ?></title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/admin.css">
      <link rel="stylesheet" href="/mutadarrib/assets/css/lawyers.css">
  <style>
    .wrap { padding: 15px; }
    .topbar { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
    .filters { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    input[type="text"], select { padding:8px; border:1px solid #ccc; border-radius:8px; min-width:240px; }
    button { padding:8px 12px; border:0; border-radius:8px; background:#0077b6; color:#fff; cursor:pointer; }

    table { width:100%; border-collapse:collapse; margin-top:15px; font-size:14px; }
    th, td { border:1px solid #ddd; padding:8px; text-align:center; vertical-align:middle; }
    th { background:#0077b6; color:#fff; }

    .badge { display:inline-block; padding:3px 10px; border-radius:12px; color:#fff; font-size:12px; }
    .b-new { background:#d62828; }
    .b-read { background:#6c757d; }
    .b-closed { background:#2d6a4f; }

    .btn { padding:6px 10px; border-radius:7px; color:#fff; text-decoration:none; display:inline-block; margin:2px; border:0; cursor:pointer; }
    .btn-view { background:#4c6ef5; }
    .btn-read { background:#6c757d; }
    .btn-close { background:#2d6a4f; }
    .btn-new { background:#d62828; }

    .summary { margin-top:10px; display:flex; gap:10px; flex-wrap:wrap; }
    .card { background:#fff; border:1px solid #e5e5e5; border-radius:10px; padding:10px 12px; }
    .muted { color:#666; font-size:13px; margin-top:5px; }

    .flash-ok { background:#e7f7ee; border:1px solid #bfe7cf; color:#1b5e20; padding:10px; border-radius:10px; margin-top:10px; }
    .flash-err { background:#fdeaea; border:1px solid #f5b5b5; color:#8a1c1c; padding:10px; border-radius:10px; margin-top:10px; }

    .view-box { margin-top:15px; background:#fff; border:1px solid #e5e5e5; border-radius:12px; padding:12px; }
    .view-grid { display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
    .view-grid > div { background:#fafafa; border:1px solid #eee; border-radius:10px; padding:10px; text-align:right; }
    .view-msg { margin-top:10px; background:#fafafa; border:1px solid #eee; border-radius:10px; padding:10px; white-space:pre-wrap; text-align:right; }
    .view-actions { margin-top:10px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
    .pager { margin-top:12px; display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .pager a { text-decoration:none; padding:6px 10px; border:1px solid #ddd; border-radius:8px; }
    .pager .active { background:#0077b6; color:#fff; border-color:#0077b6; }
  </style>
</head>
<body>

<?php
if ($layout_header_path && file_exists($layout_header_path)) include $layout_header_path;
?>

<div class="admin-container">
  <?php if ($layout_sidebar_path && file_exists($layout_sidebar_path)) include $layout_sidebar_path; ?>

  <div class="wrap">
    <div class="topbar">
      <div>
        <h2><?= h($page_title) ?></h2>
        <div class="muted">عرض الرسائل وتغيير الحالة إلى new/read/closed.</div>
      </div>

      <form method="GET" class="filters">
        <input type="text" name="search" placeholder="بحث بالاسم/الإيميل/الهاتف/الموضوع/الرسالة..." value="<?= h($search) ?>">
        <select name="status">
          <option value="all"   <?= $statusFilter==='all'?'selected':''; ?>>كل الحالات</option>
          <option value="new"   <?= $statusFilter==='new'?'selected':''; ?>>جديد</option>
          <option value="read"  <?= $statusFilter==='read'?'selected':''; ?>>مقروء</option>
          <option value="closed"<?= $statusFilter==='closed'?'selected':''; ?>>مغلق</option>
        </select>
        <button type="submit">تطبيق</button>

        <?php if ($search !== '' || $statusFilter !== 'all'): ?>
          <a class="btn btn-read" href="?">مسح الفلاتر</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="summary">
      <div class="card">جديد: <strong><?= (int)$counts['new'] ?></strong></div>
      <div class="card">مقروء: <strong><?= (int)$counts['read'] ?></strong></div>
      <div class="card">مغلق: <strong><?= (int)$counts['closed'] ?></strong></div>
      <div class="card">الإجمالي (حسب الفلاتر): <strong><?= (int)$total ?></strong></div>
    </div>

    <?php if (!empty($_GET['ok'])): ?>
      <div class="flash-ok">تم تحديث حالة الرسالة بنجاح.</div>
    <?php endif; ?>

    <?php if ($flash): ?>
      <div class="flash-err"><?= h($flash) ?></div>
    <?php endif; ?>

    <?php if ($viewRow): ?>
      <div class="view-box">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
          <h3 style="margin:0;">تفاصيل الرسالة #<?= (int)$viewRow['message_id'] ?></h3>
          <a class="btn btn-read" href="?<?= h(http_build_query($commonQS)) ?>">العودة للقائمة</a>
        </div>

        <div class="view-grid" style="margin-top:10px;">
          <div><strong>الاسم:</strong> <?= h($viewRow['name']) ?></div>
          <div><strong>البريد:</strong> <?= h($viewRow['email']) ?></div>
          <div><strong>الهاتف:</strong> <?= h($viewRow['phone'] ?? '-') ?></div>
          <div><strong>الموضوع:</strong> <?= h($viewRow['subject']) ?></div>
          <div><strong>الحالة:</strong> <?= h($viewRow['status']) ?></div>
          <div><strong>التاريخ:</strong> <?= h($viewRow['created_at']) ?></div>
        </div>

        <div class="view-msg"><?= h($viewRow['message']) ?></div>

        <div class="view-actions">
          <form method="POST" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="set_status">
            <input type="hidden" name="message_id" value="<?= (int)$viewRow['message_id'] ?>">

            <select name="status">
              <option value="new" <?= $viewRow['status']==='new'?'selected':''; ?>>new (جديد)</option>
              <option value="read" <?= $viewRow['status']==='read'?'selected':''; ?>>read (مقروء)</option>
              <option value="closed" <?= $viewRow['status']==='closed'?'selected':''; ?>>closed (مغلق)</option>
            </select>

            <button type="submit">تحديث الحالة</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
      <p style="margin-top:15px;">لا توجد رسائل.</p>
    <?php else: ?>

      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>التاريخ</th>
            <th>الاسم</th>
            <th>البريد</th>
            <th>الهاتف</th>
            <th>الموضوع</th>
            <th>الحالة</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $i => $r): ?>
          <?php
            $id = (int)$r['message_id'];
            $badge = $r['status']==='new'
              ? '<span class="badge b-new">new</span>'
              : ($r['status']==='read'
                ? '<span class="badge b-read">read</span>'
                : '<span class="badge b-closed">closed</span>'
              );

            $viewQS = array_merge($commonQS, ['view'=>$id, 'page'=>$page]);
          ?>
          <tr>
            <td><?= (int)($offset + $i + 1) ?></td>
            <td><?= h($r['created_at']) ?></td>
            <td><?= h($r['name']) ?></td>
            <td><?= h($r['email']) ?></td>
            <td><?= h($r['phone'] ?? '-') ?></td>
            <td><?= h($r['subject']) ?></td>
            <td><?= $badge ?></td>
            <td>
              <a class="btn btn-view" href="?<?= h(http_build_query($viewQS)) ?>">عرض</a>

              <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="set_status">
                <input type="hidden" name="message_id" value="<?= $id ?>">
                <input type="hidden" name="status" value="read">
                <button class="btn btn-read" type="submit">مقروء</button>
              </form>

              <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="set_status">
                <input type="hidden" name="message_id" value="<?= $id ?>">
                <input type="hidden" name="status" value="closed">
                <button class="btn btn-close" type="submit">إغلاق</button>
              </form>

              <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="set_status">
                <input type="hidden" name="message_id" value="<?= $id ?>">
                <input type="hidden" name="status" value="new">
                <button class="btn btn-new" type="submit">جديد</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <div class="pager">
        <?php
          $mk = function($p) use ($commonQS) {
            return "?" . http_build_query(array_merge($commonQS, ['page'=>$p]));
          };
        ?>
        <?php if ($page > 1): ?>
          <a href="<?= h($mk(1)) ?>">الأولى</a>
          <a href="<?= h($mk($page-1)) ?>">السابق</a>
        <?php endif; ?>

        <?php
          $start = max(1, $page - 2);
          $end   = min($totalPages, $page + 2);
          for ($p=$start; $p<=$end; $p++):
        ?>
          <a class="<?= $p===$page?'active':''; ?>" href="<?= h($mk($p)) ?>"><?= $p ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="<?= h($mk($page+1)) ?>">التالي</a>
          <a href="<?= h($mk($totalPages)) ?>">الأخيرة</a>
        <?php endif; ?>

        <span class="muted">صفحة <?= (int)$page ?> من <?= (int)$totalPages ?></span>
      </div>

    <?php endif; ?>

  </div>
</div>

</body>
</html>
