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

/*
==========================================
  1) جلب كل طلبات التدريب الخاصة بالمتدرب
==========================================
*/
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

/*
==========================================
  2) وضع جميع الطلبات كـ "مقروءة" بعد فتح الصفحة
==========================================
*/
$updateSeen = $pdo->prepare("
    UPDATE training_applications
    SET trainee_seen = 1
    WHERE trainee_id = ?
      AND trainee_seen = 0
");
$updateSeen->execute([$trainee_id]);

/*
==========================================
  3) هل يوجد طلب مكتمل (جاهز للامتحان)؟
==========================================
*/
$hasCompleted = false;
foreach ($applications as $row) {
    if ($row['status'] === 'completed') {
        $hasCompleted = true;
        break;
    }
}

/*
==========================================
  4) جلب آخر حالة لطلب امتحان النقابة (إن وجدت)
==========================================
*/
$examStmt = $pdo->prepare("
    SELECT 
        status,
        exam_date,
        created_at
    FROM syndicate_exam_requests
    WHERE trainee_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$examStmt->execute([$trainee_id]);
$lastExam = $examStmt->fetch(PDO::FETCH_ASSOC);  // قد تكون false إذا لا يوجد طلب امتحان

// هل يُسمح له بإرسال طلب امتحان جديد؟
// نسمح إذا:
//  - لديه تدريب مكتمل
//  - ولا يوجد طلب امتحان بحالة waiting_exam أو scheduled أو passed
$canRequestExam = false;
if ($hasCompleted) {
    if (!$lastExam) {
        $canRequestExam = true;
    } else {
        if (in_array($lastExam['status'], ['failed'], true)) {
            $canRequestExam = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إشعاراتي</title>
<link rel="stylesheet" href="../assets/css/style.css"></head>
<body>

<?php include("../includes/header.php"); ?>

<div class="container">
    <h2>🔔 إشعارات التدريب</h2>

    <?php if ($hasCompleted): ?>
        <div class="alert-exam">
            لقد أنهيت فترة التدريب، وأنت الآن مؤهل للتقدم لامتحان المزاولة لدى النقابة.
            <?php if ($canRequestExam): ?>
                <br>
                <a href="request_exam.php" class="btn-inline">
                    تقديم طلب امتحان المزاولة
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($lastExam): ?>
        <?php if ($lastExam['status'] === 'waiting_exam'): ?>
            <div class="alert-exam-warning">
                تم إرسال طلبك إلى نقابة المحامين، بانتظار تحديد موعد امتحان المزاولة.
            </div>
        <?php elseif ($lastExam['status'] === 'scheduled'): ?>
            <div class="alert-exam-warning">
                تم تحديد موعد امتحان المزاولة لك بتاريخ 
                <strong><?= htmlspecialchars($lastExam['exam_date'] ?? '-') ?></strong>. 
                يرجى الالتزام بالتعليمات الصادرة عن النقابة.
            </div>
        <?php elseif ($lastExam['status'] === 'passed'): ?>
            <div class="alert-exam">
                ✅ تهانينا! لقد اجتزت امتحان المزاولة بنجاح.  
                سيتم اعتمادك كمحامٍ مزاول من قبل النقابة وفق إجراءاتها الداخلية.
            </div>
        <?php elseif ($lastExam['status'] === 'failed'): ?>
            <div class="alert-exam-danger">
                ❌ لم تجتز امتحان المزاولة.  
                يمكنك مراجعة نقابة المحامين لمعرفة إمكانية إعادة التقديم في دورة لاحقة.
            </div>
        <?php endif; ?>
    <?php endif; ?>

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
                        ✅ تم القبول — بانتظار إنهاء فترة التدريب
                    <?php elseif ($row['status'] == 'rejected'): ?>
                        ❌ تم الرفض
                    <?php elseif ($row['status'] == 'completed'): ?>
                        🎓 لقد أنهيت فترة التدريب وأنت جاهز لامتحان المزاولة
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
