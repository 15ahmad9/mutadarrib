<?php
require_once("includes/auth_check.php");
require_once("../config/db.php");

// السماح فقط للمحامي
if ($_SESSION['role'] !== 'lawyer') {
    die("❌ غير مصرح لك بالدخول إلى هذه الصفحة.");
}

// user_id من جلسة الدخول
$user_id = (int) $_SESSION['user_id'];

/*
===========================================
  جلب رقم المحامي lawyer_id من جدول lawyers
===========================================
*/
$stmt = $pdo->prepare("
    SELECT lawyer_id
    FROM lawyers
    WHERE user_id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$lawyer_id = (int) $stmt->fetchColumn();

if (!$lawyer_id) {
    die("❌ لم يتم العثور على حساب محامٍ مرتبط بهذا المستخدم.");
}

// جلب رقم الطلب من الرابط
$app_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($app_id <= 0) {
    die("❌ رقم الطلب غير صالح (لم يتم تمرير ID صحيح).");
}

// يمكن أن تأتي قيمة قرار مبدئي من الرابط (?action=accept/reject)
$action = isset($_GET['action']) ? $_GET['action'] : null;

/*
==================================================
جلب الطلب والتأكد أنه يخص هذا المحامي فقط
==================================================
*/
$stmt = $pdo->prepare("
    SELECT 
        ta.application_id,
        ta.status,
        ta.training_id,
        tr.full_name AS trainee_name,
        t.seats,
        t.status AS training_status
    FROM training_applications ta
    JOIN trainees  tr ON ta.trainee_id = tr.trainee_id
    JOIN trainings t  ON ta.training_id = t.training_id
    WHERE ta.application_id = ?
      AND t.lawyer_id = ?
    LIMIT 1
");
$stmt->execute([$app_id, $lawyer_id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    die("❌ الطلب غير موجود أو لا يخص هذا المكتب.");
}

/**
 * دالة صغيرة لتنفيذ القرار (قبول/رفض) مع تحديث المقاعد
 */
function processDecision(PDO $pdo, array $app, int $app_id, string $decision) {
    if (!in_array($decision, ['accepted', 'rejected'], true)) {
        die("قرار غير صالح.");
    }

    $pdo->beginTransaction();

// ===========================
// تحديث حالة الطلب
// ===========================
$update = $pdo->prepare("
    UPDATE training_applications
    SET status = ?, reviewed_at = NOW(), trainee_seen = 0
    WHERE application_id = ?
");
$update->execute([$decision, $app_id]);

    // في حالة القبول فقط
    if ($decision === 'accepted') {

        // إنقاص عدد المقاعد بمقدار 1 إن وجد مقاعد متاحة
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
        $seats = (int) $check->fetchColumn();

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
}

// ==================================================
// 1) حالة وجود action في الرابط => تنفيذ مباشر (GET)
// ==================================================
if ($action && $app['status'] === 'pending' && $app['training_status'] === 'open') {
    $decision = ($action === 'accept') ? 'accepted' : (($action === 'reject') ? 'rejected' : null);

    if ($decision === null) {
        die("قرار غير صالح.");
    }

    processDecision($pdo, $app, $app_id, $decision);

    header("Location: applications.php?done=1");
    exit;
}

// ==================================================
// 2) حالة الإرسال من الفورم (POST)
// ==================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $decision = $_POST['status'] ?? '';

    processDecision($pdo, $app, $app_id, $decision);

    header("Location: applications.php?done=1");
    exit;
}

// لو وصلنا هنا يعني لا يوجد action ولا POST => عرض صفحة المراجعة فقط
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
            <span class="tag <?= htmlspecialchars($app['status']); ?>">
                <?= htmlspecialchars($app['status']); ?>
            </span>
        </p>

        <p><strong>المقاعد المتبقية:</strong> <?= (int)$app['seats']; ?></p>

        <p>
            <strong>حالة التدريب:</strong>
            <?= ($app['training_status'] === 'open') ? "مفتوح ✅" : "مغلق ❌"; ?>
        </p>

        <hr>

        <?php if ($app['status'] === 'pending' && $app['training_status'] === 'open'): ?>

            <form method="POST">

                <label>اختر القرار:</label>
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
                لا يمكن تعديل هذا الطلب (إما أنه تم مراجعته سابقًا أو أن التدريب مغلق).
            </p>

        <?php endif; ?>

    </div>

</main>

<?php include("includes/footer.php"); ?>

</body>
</html>
