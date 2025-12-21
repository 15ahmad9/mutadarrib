<?php
session_start();
require_once("../config/db.php");   // عدّل المسار حسب مكان ملفك
// include("includes/auth_check.php"); // إن كان لديك auth_check للمتدرب استخدمه

// حماية الدخول: متدرب فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainee') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// جلب trainee_id والتأكد أنه غير مؤرشف
$stmtT = $pdo->prepare("SELECT trainee_id, is_archived FROM trainees WHERE user_id = ? LIMIT 1");
$stmtT->execute([$user_id]);
$trainee = $stmtT->fetch(PDO::FETCH_ASSOC);

if (!$trainee) {
    die("لا يوجد سجل متدرب مرتبط بهذا الحساب.");
}
if ((int)$trainee['is_archived'] === 1) {
    die("هذا الحساب مؤرشف ولا يمكن عرض صفحة التدريب.");
}

$trainee_id = (int)$trainee['trainee_id'];

/**
 * نجلب كل الطلبات المقبولة/المكتملة للمتدرب مع بيانات التدريب والمحامي
 * ملاحظة: إذا أردت فقط التدريب الحالي، يمكنك إضافة شرط على status='accepted'
 */
$stmt = $pdo->prepare("
    SELECT
        ta.application_id,
        ta.status AS app_status,
        ta.applied_at,
        ta.reviewed_at,

        t.training_id,
        t.title,
        t.description,
        t.duration_months,
        t.location,
        t.start_date,
        t.end_date,
        t.status AS training_status,

        lw.full_name AS lawyer_name
    FROM training_applications ta
    JOIN trainings t ON ta.training_id = t.training_id
    JOIN lawyers  lw ON t.lawyer_id = lw.lawyer_id
    WHERE ta.trainee_id = ?
      AND ta.status IN ('accepted','completed')
    ORDER BY 
      CASE ta.status WHEN 'accepted' THEN 0 ELSE 1 END,
      COALESCE(t.start_date, DATE(ta.reviewed_at), DATE(ta.applied_at)) DESC
");
$stmt->execute([$trainee_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function safeDate(?string $d): ?DateTime {
    if (!$d) return null;
    try { return new DateTime($d); } catch (Exception $e) { return null; }
}

function computeDates(array $r): array {
    // start_date: من trainings.start_date، وإلا من reviewed_at، وإلا applied_at
    $start = safeDate($r['start_date']);
    if (!$start) $start = safeDate(substr((string)$r['reviewed_at'], 0, 10));
    if (!$start) $start = safeDate(substr((string)$r['applied_at'], 0, 10));

    // end_date: من trainings.end_date، وإلا نحسبها من duration_months إن وجدت
    $end = safeDate($r['end_date']);
    if (!$end && $start && !empty($r['duration_months'])) {
        $end = (clone $start);
        $end->modify('+' . (int)$r['duration_months'] . ' months');
    }

    return [$start, $end];
}

function diffDays(?DateTime $a, ?DateTime $b): ?int {
    if (!$a || !$b) return null;
    $diff = $a->diff($b);
    return (int)$diff->format('%r%a'); // ممكن تكون سالبة
}

$today = new DateTime('today');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>مدة التدريب والمتبقي</title>
<link rel="stylesheet" href="../assets/css/admin.css"> <!-- أو trainee.css حسب مشروعك -->
<style>
.container { max-width: 1100px; margin: 20px auto; padding: 15px; background:#fff; border:1px solid #ddd; border-radius:10px; }
.card { border:1px solid #eee; border-radius:10px; padding:15px; margin-top:15px; background:#fafafa; }
.grid { display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
.item { background:#fff; border:1px solid #eee; border-radius:8px; padding:10px; }
.label { font-weight:700; color:#333; margin-bottom:4px; }
.value { color:#111; }
.progress-wrap { background:#e9ecef; border-radius:999px; overflow:hidden; height:14px; }
.progress-bar { height:14px; width:0%; background:#0d6efd; }
.badge { display:inline-block; padding:3px 10px; border-radius:12px; color:#fff; font-size:12px; }
.badge-accepted { background:#2d6a4f; }
.badge-completed { background:#0b7285; }
.note { color:#666; font-size:13px; margin-top:8px; }
table { width:100%; border-collapse:collapse; margin-top:12px; font-size:14px; }
th, td { border:1px solid #ddd; padding:8px; text-align:center; }
th { background:#0077b6; color:#fff; }
</style>
</head>
<body>

<div class="container">
    <h2>مدة التدريب والمتبقي</h2>

    <?php if (empty($rows)): ?>
        <p>لا يوجد لديك تدريب مقبول أو مكتمل حتى الآن.</p>
    <?php else: ?>

        <?php
        // نعتبر أول سجل (مرتّب بحيث accepted أولاً) هو "التدريب الحالي" إن وجد
        $current = $rows[0];
        [$start, $end] = computeDates($current);

        $totalDays   = ($start && $end) ? abs(diffDays($start, $end)) : null;
        $elapsedDays = ($start) ? max(0, abs(diffDays($start, $today))) : null;
        $remainingDays = ($end) ? max(0, diffDays($today, $end)) : null;

        $percent = 0;
        if ($totalDays && $totalDays > 0 && $elapsedDays !== null) {
            $percent = (int)round(min(100, max(0, ($elapsedDays / $totalDays) * 100)));
        }

        $statusBadge = ($current['app_status'] === 'accepted')
            ? '<span class="badge badge-accepted">مقبول</span>'
            : '<span class="badge badge-completed">مكتمل</span>';
        ?>

        <div class="card">
            <h3>التدريب الحالي/الأحدث</h3>
            <div class="grid">
                <div class="item">
                    <div class="label">عنوان التدريب</div>
                    <div class="value"><?= htmlspecialchars($current['title']) ?></div>
                </div>
                <div class="item">
                    <div class="label">المكتب/المحامي</div>
                    <div class="value"><?= htmlspecialchars($current['lawyer_name'] ?? '-') ?></div>
                </div>
                <div class="item">
                    <div class="label">الموقع</div>
                    <div class="value"><?= htmlspecialchars($current['location'] ?? '-') ?></div>
                </div>
                <div class="item">
                    <div class="label">حالة الطلب</div>
                    <div class="value"><?= $statusBadge ?></div>
                </div>

                <div class="item">
                    <div class="label">تاريخ البداية</div>
                    <div class="value"><?= $start ? htmlspecialchars($start->format('Y-m-d')) : '-' ?></div>
                </div>
                <div class="item">
                    <div class="label">تاريخ النهاية</div>
                    <div class="value"><?= $end ? htmlspecialchars($end->format('Y-m-d')) : '-' ?></div>
                </div>

                <div class="item">
                    <div class="label">مدة التدريب (بالأشهر)</div>
                    <div class="value"><?= htmlspecialchars($current['duration_months'] ?? '-') ?></div>
                </div>
                <div class="item">
                    <div class="label">إجمالي المدة (أيام)</div>
                    <div class="value"><?= $totalDays !== null ? (int)$totalDays : '-' ?></div>
                </div>

                <div class="item">
                    <div class="label">المدة المنقضية (أيام)</div>
                    <div class="value"><?= $elapsedDays !== null ? (int)$elapsedDays : '-' ?></div>
                </div>
                <div class="item">
                    <div class="label">المدة المتبقية (أيام)</div>
                    <div class="value"><?= $remainingDays !== null ? (int)$remainingDays : '-' ?></div>
                </div>
            </div>

            <div style="margin-top:12px;">
                <div class="label">نسبة الإنجاز</div>
                <div class="progress-wrap">
                    <div class="progress-bar" style="width: <?= (int)$percent ?>%;"></div>
                </div>
                <div class="note">
                    <?= (int)$percent ?>%
                    <?php if (!$start || !$end): ?>
                        (لا يمكن حساب النسبة بدقة لأن تاريخ البداية أو النهاية غير متوفر)
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>كل التدريبات المقبولة/المكتملة</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>عنوان التدريب</th>
                        <th>الحالة</th>
                        <th>البداية</th>
                        <th>النهاية</th>
                        <th>المدة (أيام)</th>
                        <th>المتبقي (أيام)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $i => $r): ?>
                    <?php
                    [$s, $e] = computeDates($r);
                    $tot = ($s && $e) ? abs(diffDays($s, $e)) : null;
                    $rem = ($e) ? max(0, diffDays($today, $e)) : null;
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($r['title']) ?></td>
                        <td><?= htmlspecialchars($r['app_status']) ?></td>
                        <td><?= $s ? htmlspecialchars($s->format('Y-m-d')) : '-' ?></td>
                        <td><?= $e ? htmlspecialchars($e->format('Y-m-d')) : '-' ?></td>
                        <td><?= $tot !== null ? (int)$tot : '-' ?></td>
                        <td><?= $rem !== null ? (int)$rem : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>

</body>
</html>
