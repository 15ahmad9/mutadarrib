<?php
session_start();
require_once("../../config/db.php");
include("../../includes/header.php");

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

// التحقق من دور المستخدم
if ($_SESSION['role'] !== 'trainee') {
    die("❌ فقط المتدربين يمكنهم التقديم على التدريب.");
}

$userId     = (int)$_SESSION['user_id'];
$trainingId = (int)($_GET['training_id'] ?? 0);

if ($trainingId <= 0) {
    die("❌ رقم التدريب غير صالح.");
}

// جلب trainee_id
$stmt = $pdo->prepare("SELECT trainee_id FROM trainees WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$trainee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trainee) {
    die("❌ حسابك غير مربوط بسجل متدرب. يرجى مراجعة إدارة النظام لتفعيل حسابك.");
}

$traineeId = (int)$trainee['trainee_id'];

// منع المتدرب من التقديم على أكثر من تدريب في نفس الوقت
// Active = pending أو accepted
$stmtActive = $pdo->prepare("
    SELECT 
        a.application_id,
        a.status,
        a.training_id,
        t.title AS training_title,
        t.lawyer_id,
        l.full_name AS lawyer_name
    FROM training_applications a
    JOIN trainings t ON t.training_id = a.training_id
    JOIN lawyers l ON l.lawyer_id = t.lawyer_id
    WHERE a.trainee_id = ?
      AND a.status IN ('pending','accepted')
    ORDER BY a.applied_at DESC
    LIMIT 1
");
$stmtActive->execute([$traineeId]);
$activeApp = $stmtActive->fetch(PDO::FETCH_ASSOC);

if ($activeApp) {
    // إذا كان نفس التدريب الذي يحاول التقديم عليه الآن
    if ((int)$activeApp['training_id'] === $trainingId) {
        die("⚠ لقد قمت بالتقديم مسبقًا على هذا التدريب، وحالة طلبك الحالية: " . htmlspecialchars($activeApp['status']));
    }

    // طلب قائم على تدريب آخر: امنع التقديم
    $msg = "
    ❌ لا يمكنك التقديم على تدريب جديد الآن.
    لديك طلب قائم لدى مكتب: {$activeApp['lawyer_name']}
    بعنوان تدريب: {$activeApp['training_title']}
    وحالة الطلب: {$activeApp['status']}
    ";
    die(nl2br(htmlspecialchars($msg)));
}


// جلب التدريب والتأكد أنه تابع لمحامي موثق وأنه مفتوح
$stmt = $pdo->prepare("
    SELECT
        t.training_id,
        t.lawyer_id,
        t.title,
        t.seats,
        t.status,
        l.verified
    FROM trainings t
    JOIN lawyers l ON l.lawyer_id = t.lawyer_id
    WHERE t.training_id = ?
    LIMIT 1
");
$stmt->execute([$trainingId]);
$training = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$training) {
    die("❌ التدريب غير موجود.");
}

if ((int)$training['verified'] !== 1) {
    die("❌ لا يمكن التقديم: هذا المكتب غير موثق.");
}

if ($training['status'] !== 'open') {
    die("❗ هذا التدريب غير مفتوح حالياً.");
}

// حساب المقاعد المشغولة (pending/accepted) لنفس التدريب
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM training_applications
    WHERE training_id = ?
      AND status IN ('pending','accepted')
");
$stmt->execute([$trainingId]);
$occupied = (int)$stmt->fetchColumn();

$seats = (int)$training['seats'];
if ($occupied >= $seats) {
    die("❗ لا توجد مقاعد متاحة حاليًا لهذا التدريب.");
}

// منع التقديم المكرر
$stmt = $pdo->prepare("
    SELECT application_id
    FROM training_applications
    WHERE trainee_id = ?
      AND training_id = ?
    LIMIT 1
");
$stmt->execute([$traineeId, $trainingId]);

if ($stmt->fetch()) {
    die("⚠ لقد قمت بالتقديم مسبقًا على هذا التدريب.");
}

// تسجيل الطلب
$stmt = $pdo->prepare("
    INSERT INTO training_applications (trainee_id, training_id)
    VALUES (?, ?)
");
$stmt->execute([$traineeId, $trainingId]);

$officeId = (int)$training['lawyer_id'];
?>

<div class="container">
    <h2>تم تقديم طلبك بنجاح</h2>

    <p>
        تم إرسال طلب التدريب بعنوان: <strong><?= htmlspecialchars($training['title']) ?></strong>
        وهو الآن قيد المراجعة من قبل المكتب. سيتم إشعارك فور صدور القرار.
    </p>

    <a class="btn" href="lawyer_profile.php?id=<?= $officeId ?>">
        العودة إلى صفحة المكتب
    </a>

    <a class="btn" href="lawyers_offices.php" style="margin-right:10px;">
        العودة إلى قائمة المكاتب
    </a>
</div>

<?php include("../../includes/footer.php"); ?>
