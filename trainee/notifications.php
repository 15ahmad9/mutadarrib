<?php
session_start();
require_once("../config/db.php");

// فقط المتدرب يستطيع الوصول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainee') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// جلب trainee_id من جدول trainees
$stmt = $pdo->prepare("SELECT trainee_id FROM trainees WHERE user_id = ?");
$stmt->execute([$user_id]);
$trainee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trainee) {
    die("❌ حسابك غير مربوط بسجل متدرب.");
}

$trainee_id = $trainee['trainee_id'];

// جلب كل الطلبات الخاصة بالمتدرب
$stmt = $pdo->prepare("
    SELECT 
        ta.application_id,
        ta.status,
        ta.applied_at,
        ta.reviewed_at,
        ta.trainee_seen,
        t.title,
        t.location
    FROM training_applications ta
    JOIN trainings t ON ta.training_id = t.training_id
    WHERE ta.trainee_id = ?
    ORDER BY ta.applied_at DESC
");
$stmt->execute([$trainee_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// بعد فتح صفحة الإشعارات اعتبرها مقروءة
$updateSeen = $pdo->prepare("
    UPDATE training_applications
    SET trainee_seen = 1
    WHERE trainee_id = ?
      AND trainee_seen = 0
");
$updateSeen->execute([$trainee_id]);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إشعاراتي</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include("../includes/header.php"); ?>

<div class="container">
    <h2>🔔 إشعارات التدريب</h2>

    <?php if (empty($applications)): ?>
        <p>لا يوجد طلبات تدريب حتى الآن.</p>
    <?php else: ?>

    <table class="table">
        <thead>
            <tr>
                <th>التدريب</th>
                <th>الموقع</th>
                <th>حالة الطلب</th>
                <th>تاريخ التقديم</th>
                <th>تاريخ المراجعة</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($applications as $row): ?>
            <tr class="<?= ($row['trainee_seen'] == 0 && $row['status'] != 'pending') ? 'unread-row' : '' ?>">
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['location']) ?></td>
                <td>
                    <?php if ($row['status'] == 'pending'): ?>
                        ⏳ قيد المراجعة
                    <?php elseif ($row['status'] == 'accepted'): ?>
                        ✅ تم القبول
                    <?php elseif ($row['status'] == 'rejected'): ?>
                        ❌ تم الرفض
                    <?php else: ?>
                        <?= htmlspecialchars($row['status']) ?>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['applied_at']) ?></td>
                <td><?= $row['reviewed_at'] ? htmlspecialchars($row['reviewed_at']) : '-' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>
</div>

<?php include("../includes/footer.php"); ?>

</body>
</html>
