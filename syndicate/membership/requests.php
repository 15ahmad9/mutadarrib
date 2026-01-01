<?php
require_once __DIR__ . '/../../includes/theme_init.php';

session_start();

require_once __DIR__ . "/../../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'syndicate_admin') {
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
  <title>طلبات الانتساب</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/admin.css">
  <link rel="stylesheet" href="/mutadarrib/assets/css/lawyers.css">
  <style>
    table { width:100%; border-collapse:collapse; margin-top:15px; }
    th,td { border:1px solid #ddd; padding:8px; text-align:center; }
    th { background:#0077b6; color:#fff; }
    .btn { padding:6px 10px; border-radius:8px; text-decoration:none; color:#fff; display:inline-block; }
    .btn-view { background:#4c6ef5; }
    .btn-ok { background:#2d6a4f; }
    .btn-no { background:#d62828; }
    .badge { padding:3px 10px; border-radius:999px; color:#fff; font-size:12px; display:inline-block; }
    .b-pending { background:#6c757d; }
    .b-approved { background:#2d6a4f; }
    .b-rejected { background:#d62828; }
  </style>
</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<?php include(__DIR__ . "/../includes/header.php"); ?>
<div class="admin-container">
  <?php include(__DIR__ . "/../includes/sidebar.php"); ?>

  <div class="container">
    <h2>طلبات الانتساب</h2>

    <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
      <input type="text" name="search" placeholder="بحث بالاسم/الرقم الوطني/الهاتف/البريد" value="<?= htmlspecialchars($search) ?>">
      <select name="status">
        <option value="pending" <?= $status==='pending'?'selected':'' ?>>قيد المراجعة</option>
        <option value="approved" <?= $status==='approved'?'selected':'' ?>>مقبول</option>
        <option value="rejected" <?= $status==='rejected'?'selected':'' ?>>مرفوض</option>
        <option value="all" <?= $status==='all'?'selected':'' ?>>الكل</option>
      </select>
      <button type="submit">تطبيق</button>
    </form>

    <?php if (!$rows): ?>
      <p style="margin-top:15px;">لا توجد طلبات.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>الاسم</th>
            <th>النوع</th>
            <th>الرقم الوطني</th>
            <th>الهاتف</th>
            <th>الحالة</th>
            <th>تاريخ الإرسال</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $i => $r): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><?= htmlspecialchars($r['full_name'] ?? '-') ?></td>
              <td><?= $r['role']==='trainee' ? 'متدرب' : 'مزاول' ?></td>
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

                <?php if ($r['status']==='pending'): ?>
                  <form method="POST" action="/mutadarrib/syndicate/membership/approve.php" style="display:inline;">
                    <input type="hidden" name="id" value="<?= (int)$r['request_id'] ?>">
                    <button class="btn btn-ok" type="submit" onclick="return confirm('تأكيد قبول الطلب؟')">قبول</button>
                  </form>

                  <form method="POST" action="/mutadarrib/syndicate/membership/reject.php" style="display:inline;">
                    <input type="hidden" name="id" value="<?= (int)$r['request_id'] ?>">
                    <input type="hidden" name="reason" value="لم يتم استيفاء المتطلبات">
                    <button class="btn btn-no" type="submit" onclick="return confirm('تأكيد رفض الطلب؟')">رفض</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p style="margin-top:10px; color:#666;">ملاحظة: يمكنك تغيير سبب الرفض من صفحة العرض.</p>
    <?php endif; ?>

  </div>
</div>

<?php include(__DIR__ . "/../includes/footer.php"); ?>
</body>
</html>
