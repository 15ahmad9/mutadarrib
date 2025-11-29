<?php

require_once("includes/auth_check.php");
require_once("../config/db.php");

if ($_SESSION['role'] !== 'lawyer') {
    die("❌ غير مصرح");
}

// $lawyer_id = (int) $_SESSION['user_id'];
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT lawyer_id
    FROM lawyers
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$lawyer_id = $stmt->fetchColumn();

if(!$lawyer_id){
    die("لم يتم العثور على حساب محام مرتبط.");
}

$app_id    = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($app_id <= 0) {
    die("❌ رقم الطلب غير صالح (لم يتم تمرير ID صحيح)");
}

$stmt = $pdo->prepare("
    SELECT 
        ta.application_id,
        ta.status,
        tr.full_name AS trainee_name,
        t.training_id,
        t.seats,
        t.status AS training_status

    FROM training_applications ta
    JOIN trainees  tr ON ta.trainee_id = tr.trainee_id
    JOIN trainings t  ON ta.training_id = t.training_id

    WHERE ta.application_id = ?
      AND t.lawyer_id = ?
");

$stmt->execute([$app_id, $lawyer_id]);

$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    die("❌ الطلب غير موجود أو لا يخص هذا المكتب");
}


/*
==================================================
عند اتخاذ القرار
==================================================
*/
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $decision = $_POST['status'];

    if (!in_array($decision, ['accepted', 'rejected'])) {
        die("قرار غير صالح");
    }

    $pdo->beginTransaction();

    // ===========================
    // تحديث حالة الطلب
    // ===========================
    $update = $pdo->prepare("
        UPDATE training_applications
        SET status = ?, reviewed_at = NOW()
        WHERE application_id = ?
    ");
    $update->execute([$decision, $app_id]);

    // ===========================
    // في حالة القبول
    // ===========================
    if ($decision === 'accepted') {

        // إنقاص عدد المقاعد بمقدار 1
        $pdo->prepare("
            UPDATE trainings 
            SET seats = seats - 1 
            WHERE training_id = ? AND seats > 0
        ")->execute([$app['training_id']]);

        // جلب المقاعد بعد التخفيض
        $check = $pdo->prepare("
            SELECT seats
            FROM trainings
            WHERE training_id = ?
        ");
        $check->execute([$app['training_id']]);
        $seats = $check->fetchColumn();

        // إذا انتهت المقاعد يتم إغلاق التدريب
        if ($seats <= 0) {

            $pdo->prepare("
                UPDATE trainings
                SET status = 'closed'
                WHERE training_id = ?
            ")->execute([$app['training_id']]);
        }
    }

    $pdo->commit();

    header("Location: applications.php?done=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
<meta charset="UTF-8">
<title>مراجعة طلب التدريب</title>
<link rel="stylesheet" href="../assets/css/lawyers.css">
</head>

<body>

<?php include("includes/header.php"); ?>
<?php include("includes/sidebar.php"); ?>

<main class="content">

<h2>📋 مراجعة طلب تدريب</h2>

<div class="card">

<p><strong>اسم المتدرب:</strong> <?= htmlspecialchars($app['trainee_name']) ?></p>

<p>
    <strong>حالة الطلب:</strong> 
    <span class="tag <?= $app['status']; ?>">
        <?= $app['status']; ?>
    </span>
</p>

<p><strong>المقاعد المتبقية:</strong> <?= $app['seats']; ?></p>

<p>
    <strong>حالة التدريب:</strong> 
    <?= ($app['training_status']=='open') ? "مفتوح ✅" : "مغلق ❌"; ?>
</p>

<hr>

<?php if ($app['status'] == 'pending' && $app['training_status'] == 'open'): ?>

<form method="POST">

<select name="status" required>
    <option value="">اختر القرار</option>
    <option value="accepted">✅ قبول</option>
    <option value="rejected">❌ رفض</option>
</select>

<br><br>

<button class="btn">تنفيذ القرار</button>

</form>

<?php else: ?>

<p style="color:red;font-weight:bold">
    لا يمكن تعديل هذا الطلب.
</p>

<?php endif; ?>

</div>

</main>

<?php include("includes/footer.php"); ?>

</body>
</html>
