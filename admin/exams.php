<?php
require_once __DIR__ . '/../includes/theme_init.php';

require_once("../config/db.php");
include("includes/auth_check.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("غير مصرح");
}

$stmt = $pdo->prepare("
    SELECT
        r.request_id,
        r.status,
        r.exam_date,
        r.created_at,

        tr.full_name AS trainee_name,
        tr.national_id AS trainee_national_id,

        lw.full_name AS lawyer_name,
        ta.application_id

    FROM syndicate_exam_requests r
    JOIN trainees tr ON r.trainee_id = tr.trainee_id
    JOIN lawyers  lw ON r.lawyer_id  = lw.lawyer_id
    JOIN training_applications ta ON r.application_id = ta.application_id
    ORDER BY r.created_at DESC
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>طلبات الامتحان | الإدارة</title>
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<?php include("includes/header.php"); ?>
<?php include("includes/sidebar.php"); ?>

<div class="admin-container">
    <div class="container">
    <h2>طلبات امتحان المزاولة</h2>

    <?php if (empty($rows)): ?>
        <p>لا توجد طلبات امتحان.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>المتدرب</th>
                    <th>الرقم الوطني</th>
                    <th>المكتب/المحامي</th>
                    <th>الحالة</th>
                    <th>تاريخ الامتحان</th>
                    <th>تاريخ الإنشاء</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= (int)$r['request_id'] ?></td>
                    <td><?= htmlspecialchars($r['trainee_name']) ?></td>
                    <td><?= htmlspecialchars($r['trainee_national_id']) ?></td>
                    <td><?= htmlspecialchars($r['lawyer_name']) ?></td>
                    <td><?= htmlspecialchars($r['status']) ?></td>
                    <td><?= $r['exam_date'] ? htmlspecialchars($r['exam_date']) : '-' ?></td>
                    <td><?= htmlspecialchars($r['created_at']) ?></td>
                    <td>
                        <a class="btn" href="exam_review.php?id=<?= (int)$r['request_id'] ?>">مراجعة</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</div>
<?php include("includes/footer.php"); ?>
</body>
</html>
