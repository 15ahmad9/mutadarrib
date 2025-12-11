<?php
require_once("includes/auth_check.php");
require_once("../config/db.php");

if ($_SESSION['role'] !== 'lawyer') {
    die("❌ غير مصرح");
}

// 1) جلب lawyer_id من خلال user_id المخزن في السيشن
$user_id = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT lawyer_id FROM lawyers WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$lawyer_id = $stmt->fetchColumn();

if (!$lawyer_id) {
    die("لم يتم العثور على حساب محامٍ مرتبط.");
}

// 2) جلب رقم الطلب
$app_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($app_id <= 0) {
    die("❌ رقم الطلب غير صالح.");
}

// 3) جلب بيانات الطلب + التدريب + المتدرب + المستخدم (مع كل الحقول المهمة)
$stmt = $pdo->prepare("
    SELECT 
        ta.application_id,
        ta.status       AS app_status,
        ta.trainee_id,

        t.training_id,
        t.lawyer_id,

        tr.user_id      AS trainee_user_id,
        tr.full_name    AS trainee_full_name,
        tr.first_name,
        tr.father_name,
        tr.grandfather_name,
        tr.family_name,
        tr.national_id,
        tr.phone,
        tr.email,
        tr.home_address,
        tr.no_conviction_doc,
        tr.good_conduct_doc,
        tr.social_security,
        tr.social_security_number,
        tr.highschool_certificate,
        tr.university_degree,

        u.password      AS user_password,

        lw.syndicate_id AS syndicate_id

    FROM training_applications ta
    JOIN trainings t   ON ta.training_id = t.training_id
    JOIN trainees  tr  ON ta.trainee_id = tr.trainee_id
    JOIN users    u    ON tr.user_id = u.user_id
    JOIN lawyers  lw   ON t.lawyer_id = lw.lawyer_id  
    WHERE ta.application_id = ?
      AND t.lawyer_id = ?
");
$stmt->execute([$app_id, $lawyer_id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    die("❌ الطلب غير موجود أو لا يخص هذا المكتب.");
}

// يجب أن يكون الطلب مقبول قبل إنهاء التدريب
if ($app['app_status'] !== 'accepted') {
    die("لا يمكن إنهاء التدريب لطلب غير مقبول.");
}

try {
    $pdo->beginTransaction();

    // ممكن نحتاجهم لاحقاً (لا مشكلة إن تُركوا)
    $trainee_user_id = (int) $app['trainee_user_id'];
    $trainee_id      = (int) $app['trainee_id'];

    // 4) تحديث حالة الطلب إلى completed + إعادة إشعار المتدرب
    //   ملاحظة: syndicate_notified = 0 لأن المتدرب هو من سيطلب الامتحان لاحقاً
    $up1 = $pdo->prepare("
        UPDATE training_applications
        SET status = 'completed',
            reviewed_at = NOW(),
            trainee_seen = 0,
            syndicate_notified = 0
        WHERE application_id = ?
    ");
    $up1->execute([$app_id]);

    $pdo->commit();

    header("Location: applications.php?completed=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("خطأ أثناء إنهاء التدريب: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إنهاء التدريب</title>
<link rel="stylesheet" href="../assets/css/lawyers.css">
</head>
<body>

<?php include("includes/header.php"); ?>
<?php include("includes/sidebar.php"); ?>

<main class="content">
    <h2>✅ تم إنهاء التدريب بنجاح</h2>

    <p>
        تم تغيير حالة الطلب إلى <strong>completed</strong>، 
        وسيظهر للمتدرب في صفحة الإشعارات أنه أنهى فترة التدريب
        وأصبح مؤهلاً لتقديم <strong>طلب امتحان المزاولة</strong> لدى النقابة.
    </p>

    <p>
        المتدرب يستطيع من خلال صفحة مخصصة (مثل <code>request_exam.php</code>) 
        إرسال طلب رسمي للنقابة لتحديد موعد امتحان المزاولة.
    </p>

    <a class="btn" href="applications.php">العودة إلى طلبات التدريب</a>
</main>

<?php include("includes/footer.php"); ?>

</body>
</html>
