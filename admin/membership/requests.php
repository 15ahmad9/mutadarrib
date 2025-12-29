<?php
require_once __DIR__ . '/../../includes/theme_init.php';

session_start();
require_once __DIR__ . "/../../config/db.php";

// المدير فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: /mutadarrib/auth/login.php");
  exit;
}

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'pending';
if (!in_array($status, ['pending','approved','rejected','all'], true)) $status = 'pending';

$params = [];
$sql = "SELECT * FROM membership_requests WHERE 1=1 ";

if ($status !== 'all') {
  $sql .= " AND status = ? ";
  $params[] = $status;
}

if ($search !== '') {
  $sql .= " AND (full_name LIKE ? OR national_id LIKE ? OR email LIKE ? OR phone LIKE ?) ";
  $params[] = "%$search%";
  $params[] = "%$search%";
  $params[] = "%$search%";
  $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>طلبات الانتساب | الإدارة</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/admin.css">
  <style>
    table { width:100%; border-collapse:collapse; margin-top:15px; }
    th,td { border:1px solid #ddd; padding:8px; text-align:center; }
    th { background:#0077b6; color:#fff; }
    .badge { padding:3px 10px; border-radius:999px; color:#fff; font-size:12px; display:inline-block; }
    .b-pending { background:#6c757d; }
    .b-approved { background:#2d6a4f; }
    .b-rejected { background:#d62828; }
    .btn { padding:6px 10px; border-radius:8px; text-decoration:none; color:#fff; display:inline-block; }
    .btn-view { background:#4c6ef5; }
  </style>
</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<?php include(__DIR__ . "/../includes/header.php"); ?>
<div class="admin-container">
  <?php include(__DIR__ . "/../includes/sidebar.php"); ?>

  <div class="container">
    <div class="admin-page-head">
  <h2>طلبات الانتساب</h2>
  <form class="search-form" method="GET">
  <div class="search-input">
    <input type="text" name="search" placeholder="بحث بالاسم/الرقم الوطني/الهاتف/البريد" value="<?= htmlspecialchars($search) ?>" class="search-field">
    <svg class="in-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4a6 6 0 1 1 0 12A6 6 0 0 1 10 4m0-2a8 8 0 1 0 4.9 14.3l4.4 4.4a1 1 0 0 0 1.4-1.4l-4.4-4.4A8 8 0 0 0 10 2Z"/></svg>
  </div>
  <button type="submit" class="btn btn-soft"><svg class="btn-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4a6 6 0 1 1 0 12A6 6 0 0 1 10 4m0-2a8 8 0 1 0 4.9 14.3l4.4 4.4a1 1 0 0 0 1.4-1.4l-4.4-4.4A8 8 0 0 0 10 2Z"/></svg><span>بحث</span></button>
</form>
</div>

    <?php if (!$rows): ?>
      <p style="margin-top:15px;">لا توجد طلبات.</p>
    <?php else: ?>
      <div class="table-card"><div class="table-wrap">
<table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>الاسم</th>
            <th>النوع</th>
            <th>الرقم الوطني</th>
            <th>الهاتف</th>
            <th>الحالة</th>
            <th>تاريخ الإرسال</th>
            <th>عرض</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $i => $r): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><?= htmlspecialchars($r['full_name'] ?? '-') ?></td>
              <td><?= $r['applicant_type']==='trainee' ? 'متدرب' : 'مزاول' ?></td>
              <td><?= htmlspecialchars($r['national_id'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['phone'] ?? '-') ?></td>
              <td>
                <?php if ($r['status']==='pending'): ?>
                  <span class="badge b-pending">قيد المراجعة</span>
                <?php elseif ($r['status']==='approved'): ?>
                  <span class="badge b-approved">مقبول</span>
                <?php else: ?>
                  <span class="badge b-rejected">مرفوض</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($r['created_at']) ?></td>
              <td>
                <a class="btn btn-view" href="/mutadarrib/syndicate/membership/view.php?id=<?= (int)$r['request_id'] ?>">عرض</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
</div></div>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
