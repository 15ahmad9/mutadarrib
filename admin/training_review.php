<?php
require_once __DIR__ . '/../includes/theme_init.php';

require_once("../config/db.php");
include("includes/auth_check.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("غير مصرح");
}

$app_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($app_id <= 0) die("رقم طلب غير صالح");

$stmt = $pdo->prepare("
    SELECT 
        ta.*,
        tr.full_name AS trainee_name,
        tr.national_id AS trainee_national_id,
        t.title AS training_title,
        lw.full_name AS lawyer_name
    FROM training_applications ta
    JOIN trainees tr ON ta.trainee_id = tr.trainee_id
    JOIN trainings t ON ta.training_id = t.training_id
    JOIN lawyers  lw ON t.lawyer_id = lw.lawyer_id
    WHERE ta.application_id = ?
    LIMIT 1
");
$stmt->execute([$app_id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) die("الطلب غير موجود");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $notes  = $_POST['notes'] ?? null;

    if (!in_array($status, ['pending','accepted','rejected','completed'], true)) {
        die("حالة غير صحيحة");
    }

    $up = $pdo->prepare("
        UPDATE training_applications
        SET status = ?, notes = ?, reviewed_at = NOW(), trainee_seen = 0
        WHERE application_id = ?
    ");
    $up->execute([$status, $notes, $app_id]);

    header("Location: training_applications.php?updated=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>مراجعة طلب تدريب | الإدارة</title>
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<?php include("includes/header.php"); ?>
<?php include("includes/sidebar.php"); ?>

<div class="admin-container">
    <h2>مراجعة طلب تدريب</h2>

    <div class="card">
        <p><strong>المتدرب:</strong> <?= htmlspecialchars($app['trainee_name']) ?> (<?= htmlspecialchars($app['trainee_national_id']) ?>)</p>
        <p><strong>التدريب:</strong> <?= htmlspecialchars($app['training_title']) ?></p>
        <p><strong>المكتب/المحامي:</strong> <?= htmlspecialchars($app['lawyer_name']) ?></p>
        <p><strong>الحالة الحالية:</strong> <?= htmlspecialchars($app['status']) ?></p>
        <p><strong>تاريخ التقديم:</strong> <?= htmlspecialchars($app['applied_at']) ?></p>
    </div>

    <form method="POST" style="padding: 15px;">
        <label>تحديث الحالة:</label>
        <select name="status" required>
            <option value="pending"   <?= $app['status']==='pending'?'selected':'' ?>>pending</option>
            <option value="accepted"  <?= $app['status']==='accepted'?'selected':'' ?>>accepted</option>
            <option value="rejected"  <?= $app['status']==='rejected'?'selected':'' ?>>rejected</option>
            <option value="completed" <?= $app['status']==='completed'?'selected':'' ?>>completed</option>
        </select>

        <br><br>

        <label>ملاحظات:</label>
        <textarea name="notes" rows="4"><?= htmlspecialchars($app['notes'] ?? '') ?></textarea>

        <br><br>

        <button class="btn">حفظ</button>
        <a class="btn" href="training_applications.php">رجوع</a>
    </form>
</div>

<?php include("includes/footer.php"); ?>
</body>
</html>
