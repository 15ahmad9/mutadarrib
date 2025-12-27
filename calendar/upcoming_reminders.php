<?php
require_once __DIR__ . '/../includes/theme_init.php';

session_start();
require_once("../config/db.php");

// يجب تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
  header("Location: ../auth/login.php");
  exit;
}

$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'guest';

/* =========================
   تذكيرات التقويم (لجميع المستخدمين)
========================= */

// 1) تذكيرات الآن: داخل نافذة التذكير
$stmtNow = $pdo->prepare("
  SELECT event_id, title, description, start_at, end_at, all_day, reminder_minutes, type
  FROM calendar_events
  WHERE user_id = ?
    AND reminder_minutes IS NOT NULL
    AND reminder_minutes > 0
    AND start_at IS NOT NULL
    AND NOW() >= DATE_SUB(start_at, INTERVAL reminder_minutes MINUTE)
    AND NOW() < start_at
  ORDER BY start_at ASC
");
$stmtNow->execute([$userId]);
$remindersNow = $stmtNow->fetchAll(PDO::FETCH_ASSOC);

// 2) قادم خلال 24 ساعة
$stmtNext = $pdo->prepare("
  SELECT event_id, title, description, start_at, end_at, all_day, reminder_minutes, type
  FROM calendar_events
  WHERE user_id = ?
    AND start_at IS NOT NULL
    AND start_at >= NOW()
    AND start_at < DATE_ADD(NOW(), INTERVAL 24 HOUR)
  ORDER BY start_at ASC
");
$stmtNext->execute([$userId]);
$next24h = $stmtNext->fetchAll(PDO::FETCH_ASSOC);

function formatRemaining($startAt)
{
  try {
    $now = new DateTime();
    $start = new DateTime($startAt);
    $diffSeconds = $start->getTimestamp() - $now->getTimestamp();
    if ($diffSeconds <= 0)
      return "الآن أو مضى";

    $minutes = (int) floor($diffSeconds / 60);
    if ($minutes < 60)
      return $minutes . " دقيقة";

    $hours = (int) floor($minutes / 60);
    $remMin = $minutes % 60;
    if ($hours < 24)
      return $hours . " ساعة" . ($remMin ? (" " . $remMin . " دقيقة") : "");

    $days = (int) floor($hours / 24);
    $remHours = $hours % 24;
    return $days . " يوم" . ($remHours ? (" " . $remHours . " ساعة") : "");
  } catch (Exception $e) {
    return "-";
  }
}

/* =========================
   إشعارات التدريب (للمتدرب فقط)
========================= */

$applications = [];
$hasCompleted = false;
$lastExam = false;
$canRequestExam = false;

if ($role === 'trainee') {

  // جلب trainee_id
  $stmt = $pdo->prepare("SELECT trainee_id FROM trainees WHERE user_id = ? LIMIT 1");
  $stmt->execute([$userId]);
  $trainee = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$trainee) {
    // لا نكسر الصفحة، فقط نعرض رسالة داخل قسم التدريب
    $trainee_id = null;
  } else {
    $trainee_id = (int) $trainee['trainee_id'];

    // 1) جلب طلبات التدريب
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

    // 2) وضع جميع الطلبات كـ “مقروءة” بعد فتح الصفحة
    $updateSeen = $pdo->prepare("
            UPDATE training_applications
            SET trainee_seen = 1
            WHERE trainee_id = ?
              AND trainee_seen = 0
        ");
    $updateSeen->execute([$trainee_id]);

    // 3) هل يوجد تدريب مكتمل؟
    foreach ($applications as $row) {
      if ($row['status'] === 'completed') {
        $hasCompleted = true;
        break;
      }
    }

    // 4) آخر حالة لطلب امتحان النقابة
    $examStmt = $pdo->prepare("
            SELECT status, exam_date, created_at
            FROM syndicate_exam_requests
            WHERE trainee_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
    $examStmt->execute([$trainee_id]);
    $lastExam = $examStmt->fetch(PDO::FETCH_ASSOC);

    // السماح بطلب امتحان جديد:
    // - إذا لديه تدريب مكتمل
    // - ولا يوجد طلب نشط (waiting_exam/scheduled/passed)
    // - نسمح إذا لا يوجد طلب أو آخر طلب failed
    if ($hasCompleted) {
      if (!$lastExam) {
        $canRequestExam = true;
      } else {
        if (in_array($lastExam['status'], ['failed'], true)) {
          $canRequestExam = true;
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <title>مركز الإشعارات</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">

</head>

<body data-theme="<?= htmlspecialchars($theme) ?>">

  <?php include("../includes/header.php"); ?>

  <div class="wrap">
    <h2 class="page-title">مركز الإشعارات</h2>

    <div class="tabs">
      <a href="#reminders">تذكيرات التقويم</a>
      <?php if ($role === 'trainee'): ?>
        <a href="#training">إشعارات التدريب</a>
      <?php endif; ?>
    </div>

    <!-- ================== Reminders ================== -->
    <div class="section" id="reminders">
      <h3>تذكيرات التقويم</h3>

      <h4 style="margin-top:12px;">تذكيرات الآن</h4>
      <?php if (empty($remindersNow)): ?>
        <p class="muted">لا توجد تذكيرات قريبة الآن.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>العنوان</th>
              <th>النوع</th>
              <th>وقت البدء</th>
              <th>المتبقي</th>
              <th>التذكير (دقيقة)</th>
              <th>حالة</th>
              <th>إجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($remindersNow as $i => $r): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= htmlspecialchars($r['type'] ?? 'task') ?></td>
                <td><?= htmlspecialchars($r['start_at']) ?></td>
                <td><?= htmlspecialchars(formatRemaining($r['start_at'])) ?></td>
                <td><?= htmlspecialchars($r['reminder_minutes']) ?></td>
                <td><span class="badge badge-now">حان وقت التذكير</span></td>
                <td class="actions">
                  <a href="/mutadarrib/calendar/calendar.php">فتح التقويم</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <h4 style="margin-top:16px;">قادم خلال 24 ساعة</h4>
      <?php if (empty($next24h)): ?>
        <p class="muted">لا توجد مهام خلال 24 ساعة القادمة.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>العنوان</th>
              <th>وقت البدء</th>
              <th>المتبقي</th>
              <th>التذكير (دقيقة)</th>
              <th>إجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($next24h as $i => $r): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= htmlspecialchars($r['start_at']) ?></td>
                <td><?= htmlspecialchars(formatRemaining($r['start_at'])) ?></td>
                <td><?= htmlspecialchars($r['reminder_minutes'] ?? '-') ?></td>
                <td class="actions">
                  <a href="/mutadarrib/calendar/calendar.php">فتح التقويم</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- ================== Training Notifications (Trainee only) ================== -->
    <?php if ($role === 'trainee'): ?>
      <div class="section" id="training">
        <h3>إشعارات التدريب</h3>

        <?php if (!isset($trainee_id) || !$trainee_id): ?>
          <div class="alert-exam-danger">
            حسابك متدرب لكن غير مربوط بسجل متدرب في جدول trainees.
          </div>
        <?php else: ?>

          <?php if ($hasCompleted): ?>
            <div class="alert-exam">
              لقد أنهيت فترة التدريب، وأنت الآن مؤهل للتقدم لامتحان المزاولة لدى النقابة.
              <?php if ($canRequestExam): ?>
                <br>
                <a href="request_exam.php" class="btn-inline">تقديم طلب امتحان المزاولة</a>
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
              </div>
            <?php elseif ($lastExam['status'] === 'passed'): ?>
              <div class="alert-exam">
                لقد اجتزت امتحان المزاولة بنجاح. سيتم اعتمادك وفق إجراءات النقابة.
              </div>
            <?php elseif ($lastExam['status'] === 'failed'): ?>
              <div class="alert-exam-danger">
                لم تجتز امتحان المزاولة. يمكنك مراجعة النقابة لمعرفة إمكانية إعادة التقديم.
              </div>
            <?php endif; ?>
          <?php endif; ?>

          <?php if (empty($applications)): ?>
            <p class="muted">لا يوجد طلبات تدريب حتى الآن.</p>
          <?php else: ?>
            <table>
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
                <?php foreach ($applications as $row): ?>
                  <tr class="<?= ((int) $row['trainee_seen'] === 0 && $row['status'] !== 'pending') ? 'unread-row' : '' ?>">
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                    <td>
                      <?php if ($row['status'] === 'pending'): ?>
                        قيد المراجعة
                      <?php elseif ($row['status'] === 'accepted'): ?>
                        تم القبول — بانتظار إنهاء فترة التدريب
                      <?php elseif ($row['status'] === 'rejected'): ?>
                        تم الرفض
                      <?php elseif ($row['status'] === 'completed'): ?>
                        أنهيت فترة التدريب وأنت جاهز لامتحان المزاولة
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

        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div>

  <?php include("../includes/footer.php"); ?>
</body>

</html>