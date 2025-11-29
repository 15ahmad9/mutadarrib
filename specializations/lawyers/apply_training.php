<?php
session_start();
require_once("../../config/db.php");
include("../../includes/header.php");

// ==============================
// التحقق من تسجيل الدخول
// ==============================
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

// ==============================
// التحقق من دور المستخدم
// ==============================
if ($_SESSION['role'] !== 'trainee') {
    die("❌ فقط المتدربين يمكنهم التقديم على التدريب.");
}

$user_id   = $_SESSION['user_id'];
$lawyer_id = intval($_GET['lawyer_id'] ?? 0);

if (!$lawyer_id) {
    die("❌ رقم المكتب غير صالح.");
}

// ==============================
// التأكد من وجود سجل متدرب
// ==============================
$stmt = $pdo->prepare("
    SELECT trainee_id
    FROM trainees
    WHERE user_id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$trainee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trainee) {
    die("❌ حسابك غير مربوط بسجل متدرب. يرجى مراجعة إدارة النظام لتفعيل حسابك.");
}

$trainee_id = $trainee['trainee_id'];


// ==============================
// التأكد من وجود تدريب مفتوح ومقاعد متاحة
// ==============================
$stmt = $pdo->prepare("
    SELECT 
        t.training_id,
        t.seats,
        COUNT(a.application_id) AS applied
    FROM trainings t
    LEFT JOIN training_applications a 
        ON a.training_id = t.training_id 
        AND a.status IN ('pending','accepted')
    WHERE t.lawyer_id = ?
      AND t.status = 'open'
    GROUP BY t.training_id
    HAVING applied < t.seats
    LIMIT 1
");

$stmt->execute([$lawyer_id]);
$training = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$training) {
    die("❗ هذا المكتب لا يوفّر مقاعد تدريب مفتوحة حاليًا.");
}

$training_id = $training['training_id'];


// ==============================
// منع التقديم المكرر
// ==============================
$stmt = $pdo->prepare("
    SELECT application_id
    FROM training_applications
    WHERE trainee_id = ?
      AND training_id = ?
");
$stmt->execute([$trainee_id, $training_id]);

if ($stmt->fetch()) {
    die("⚠ لقد قمت بالتقديم مسبقًا على هذا التدريب.");
}


// ==============================
// تسجيل الطلب
// ==============================
$stmt = $pdo->prepare("
    INSERT INTO training_applications
        (trainee_id, training_id)
    VALUES (?, ?)
");
$stmt->execute([$trainee_id, $training_id]);

?>

<div class="container">

    <h2>✅ تم تقديم طلبك بنجاح</h2>

    <p>
        تم إرسال طلب التدريب وهو الآن قيد المراجعة من قبل المكتب.
        سيتم إشعارك فور صدور القرار.
    </p>

    <a class="btn" href="lawyers_offices.php">
        العودة إلى قائمة المكاتب
    </a>

</div>

<?php include("../../includes/footer.php"); ?>
