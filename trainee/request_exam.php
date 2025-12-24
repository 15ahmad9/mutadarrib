<?php
session_start();
require_once("../config/db.php");

// فقط المتدرب يستطيع الوصول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainee') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// جلب trainee_id من جدول trainees
$stmt = $pdo->prepare("SELECT trainee_id FROM trainees WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$trainee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trainee) {
    die("❌ حسابك غير مربوط بسجل متدرب.");
}

$trainee_id = (int) $trainee['trainee_id'];

$message = "";

/*
=============================================
  إذا تم إرسال الفورم لتقديم طلب امتحان
=============================================
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $application_id = isset($_POST['application_id']) ? (int) $_POST['application_id'] : 0;

    if ($application_id <= 0) {
        $message = "<p class='error'>رقم الطلب غير صالح.</p>";
    } else {

        // التأكد أن هذا الطلب مكتمل ويخص هذا المتدرب
        $stmt = $pdo->prepare("
            SELECT 
                ta.application_id,
                ta.status,
                ta.trainee_id,
                t.lawyer_id
            FROM training_applications ta
            JOIN trainings t ON ta.training_id = t.training_id
            WHERE ta.application_id = ?
              AND ta.trainee_id = ?
              AND ta.status = 'completed'
            LIMIT 1
        ");
        $stmt->execute([$application_id, $trainee_id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            $message = "<p class='error'>لا يمكن التقديم على هذا الطلب (إما أنه لا يخصك أو غير مكتمل).</p>";
        } else {

            // التأكد أنه لا يوجد طلب امتحان سابق لنفس application_id
            $chk = $pdo->prepare("
                SELECT request_id
                FROM syndicate_exam_requests
                WHERE application_id = ?
                LIMIT 1
            ");
            $chk->execute([$application_id]);
            $exists = $chk->fetch(PDO::FETCH_ASSOC);

            if ($exists) {
                $message = "<p class='error'>لقد قمت مسبقاً بتقديم طلب امتحان على هذا التدريب.</p>";
            } else {

                try {
                    $pdo->beginTransaction();

                    // إدخال طلب الامتحان
                    $ins = $pdo->prepare("
                        INSERT INTO syndicate_exam_requests (application_id, trainee_id, lawyer_id, status)
                        VALUES (?, ?, ?, 'waiting_exam')
                    ");
                    $ins->execute([
                        $application_id,
                        $trainee_id,
                        $app['lawyer_id']
                    ]);

                    // تحديث training_applications (إشعار النقابة)
                    $up = $pdo->prepare("
                        UPDATE training_applications
                        SET syndicate_notified = 1
                        WHERE application_id = ?
                    ");
                    $up->execute([$application_id]);

                    $pdo->commit();
                    $message = "<p class='success'>✅ تم إرسال طلب امتحان المزاولة إلى النقابة بنجاح.</p>";

                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $message = "<p class='error'>حدث خطأ أثناء إرسال الطلب: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
}

/*
=============================================
  جلب كل الطلبات المكتملة (completed)
  ليختار المتدرب أي تدريب يقدّم عليه الامتحان
=============================================
*/
$stmt = $pdo->prepare("
    SELECT 
        ta.application_id,
        ta.reviewed_at,
        t.title,
        t.location
    FROM training_applications ta
    JOIN trainings t ON ta.training_id = t.training_id
    WHERE ta.trainee_id = ?
      AND ta.status = 'completed'
    ORDER BY ta.reviewed_at DESC
");
$stmt->execute([$trainee_id]);
$completedApps = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>طلب امتحان المزاولة</title>
<link rel="stylesheet" href="../assets/css/style.css"></head>
<body>

<?php include("../includes/header.php"); ?>

<div class="container">
    <h2>🎓 طلب التقدم لامتحان المزاولة</h2>

    <?= $message ?>

    <?php if (empty($completedApps)): ?>
        <p>لا يمكنك تقديم طلب امتحان حالياً، يجب أن تُنهي فترة تدريب واحدة على الأقل.</p>
    <?php else: ?>

        <p>اختر التدريب الذي أنهَيتَه وتريد التقدم على أساسه لامتحان المزاولة:</p>

        <table class="table">
            <thead>
                <tr>
                    <th>التدريب</th>
                    <th>الموقع</th>
                    <th>تاريخ إنهاء التدريب</th>
                    <th>طلب الامتحان</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($completedApps as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                    <td><?= htmlspecialchars($row['reviewed_at']) ?></td>
                    <td>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="application_id" value="<?= (int)$row['application_id'] ?>">
                            <button type="submit" class="btn-small">
                                تقديم طلب امتحان على هذا التدريب
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

</div>

<?php include("../includes/footer.php"); ?>

</body>
</html>
