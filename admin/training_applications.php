<?php
require_once("../config/db.php");
include("includes/auth_check.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("غير مصرح");
}

$stmt = $pdo->prepare("
    SELECT 
        ta.application_id,
        ta.status,
        ta.applied_at,
        ta.reviewed_at,
        ta.syndicate_notified,

        tr.full_name   AS trainee_name,
        tr.national_id AS trainee_national_id,
        tr.phone       AS trainee_phone,

        t.title        AS training_title,
        t.location     AS training_location,
        t.status       AS training_status,

        lw.full_name   AS lawyer_name
    FROM training_applications ta
    JOIN trainees tr  ON ta.trainee_id = tr.trainee_id
    JOIN trainings t  ON ta.training_id = t.training_id
    JOIN lawyers  lw  ON t.lawyer_id = lw.lawyer_id
    ORDER BY ta.applied_at DESC
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>طلبات التدريب | الإدارة</title>
<link rel="stylesheet" href="../assets/css/admin.css">
<style>
table { width:100%; border-collapse:collapse; margin-top:20px; font-size:14px; }
th, td { padding:8px; border:1px solid #ccc; text-align:center; }
th { background:#0077b6; color:white; }
.btn { padding:6px 10px; border-radius:6px; color:white; text-decoration:none; }
.edit-btn { background:#00b4d8; }
.delete-btn { background:#d62828; }
</style>
</head>
<body>

<?php include("includes/header.php"); ?>
<?php include("includes/sidebar.php"); ?>

<div class="admin-container">
    <h2>طلبات التدريب</h2>

    <?php if (empty($rows)): ?>
        <p>لا توجد طلبات.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>المتدرب</th>
                    <th>الرقم الوطني</th>
                    <th>الهاتف</th>
                    <th>التدريب</th>
                    <th>الموقع</th>
                    <th>المكتب/المحامي</th>
                    <th>الحالة</th>
                    <th>تاريخ الطلب</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= (int)$r['application_id'] ?></td>
                    <td><?= htmlspecialchars($r['trainee_name']) ?></td>
                    <td><?= htmlspecialchars($r['trainee_national_id']) ?></td>
                    <td><?= htmlspecialchars($r['trainee_phone']) ?></td>
                    <td><?= htmlspecialchars($r['training_title']) ?></td>
                    <td><?= htmlspecialchars($r['training_location']) ?></td>
                    <td><?= htmlspecialchars($r['lawyer_name']) ?></td>
                    <td><?= htmlspecialchars($r['status']) ?></td>
                    <td><?= htmlspecialchars($r['applied_at']) ?></td>
                    <td>
                        <a class="btn edit-btn" href="training_review.php?id=<?= (int)$r['application_id'] ?>">عرض/تعديل</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include("includes/footer.php"); ?>
</body>
</html>
