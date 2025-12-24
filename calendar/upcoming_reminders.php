<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['user_id'])) {
  header("Location: ../auth/login.php");
  exit;
}

$userId = (int)$_SESSION['user_id'];

/**
 * 1) تذكيرات الآن: داخل نافذة التذكير
 */
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

/**
 * 2) قادم خلال 24 ساعة (اختياري لكنه مفيد)
 */
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

function formatRemaining($startAt) {
  try {
    $now = new DateTime();
    $start = new DateTime($startAt);
    $diffSeconds = $start->getTimestamp() - $now->getTimestamp();
    if ($diffSeconds <= 0) return "الآن أو مضى";

    $minutes = (int)floor($diffSeconds / 60);
    if ($minutes < 60) return $minutes . " دقيقة";

    $hours = (int)floor($minutes / 60);
    $remMin = $minutes % 60;
    if ($hours < 24) return $hours . " ساعة " . ($remMin ? ($remMin . " دقيقة") : "");

    $days = (int)floor($hours / 24);
    $remHours = $hours % 24;
    return $days . " يوم " . ($remHours ? ($remHours . " ساعة") : "");
  } catch (Exception $e) {
    return "-";
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تذكيرات التقويم</title>
  <link rel="stylesheet" href="../assets/css/style.css"></head>
<body>

<?php include("../includes/header.php"); ?>

<div class="wrap">
  <h2>تذكيرات التقويم</h2>
  <div class="muted">
    هذه الصفحة تعرض التذكيرات القريبة بحسب (Reminder Minutes) لكل مهمة.
  </div>

  <div class="section">
    <h3>تذكيرات الآن</h3>

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
  </div>

  <div class="section">
    <h3>قادم خلال 24 ساعة</h3>

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

</div>
</body>
</html>
