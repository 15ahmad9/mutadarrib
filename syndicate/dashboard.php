<?php
require_once __DIR__ . '/../includes/theme_init.php';

require_once("includes/auth_check.php");
require_once("../config/db.php");

// ===========================
// 1) إحصائيات طلبات الامتحان
// ===========================
$stmt = $pdo->query("
    SELECT status, COUNT(*) AS cnt
    FROM syndicate_exam_requests
    GROUP BY status
");

$examStats = [
    'waiting_exam' => 0,
    'scheduled'    => 0,
    'passed'       => 0,
    'failed'       => 0
];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $st = $row['status'];
    if (isset($examStats[$st])) {
        $examStats[$st] = (int)$row['cnt'];
    }
}

$examAll = (int)$pdo->query("SELECT COUNT(*) FROM syndicate_exam_requests")->fetchColumn();


// ===========================
// 2) إحصائيات طلبات الانتساب
// ===========================
$membershipStats = [
    'pending'  => 0,
    'approved' => 0,
    'rejected' => 0
];

$stmtM = $pdo->query("
    SELECT status, COUNT(*) AS cnt
    FROM membership_requests
    GROUP BY status
");
while ($row = $stmtM->fetch(PDO::FETCH_ASSOC)) {
    $st = $row['status'];
    if (isset($membershipStats[$st])) {
        $membershipStats[$st] = (int)$row['cnt'];
    }
}
$membershipAll = (int)$pdo->query("SELECT COUNT(*) FROM membership_requests")->fetchColumn();


// ===========================
// 3) إحصائيات رسائل تواصل معنا
// ===========================
// غيّر اسم الجدول إذا كان مختلفاً عندك
$contactTable = "contact_messages";

$contactStats = [
    'new'    => 0,
    'read'   => 0,
    'closed' => 0
];

try {
    $stmtC = $pdo->query("
        SELECT status, COUNT(*) AS cnt
        FROM {$contactTable}
        GROUP BY status
    ");
    while ($row = $stmtC->fetch(PDO::FETCH_ASSOC)) {
        $st = $row['status'];
        if (isset($contactStats[$st])) {
            $contactStats[$st] = (int)$row['cnt'];
        }
    }
    $contactAll = (int)$pdo->query("SELECT COUNT(*) FROM {$contactTable}")->fetchColumn();
} catch (Exception $e) {
    // إذا الجدول غير موجود أو الاسم مختلف
    $contactAll = 0;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>لوحة نقابة المحامين</title>
<link rel="stylesheet" href="../assets/css/lawyers.css">
<style>
  .stats { display:flex; gap:12px; flex-wrap:wrap; }
  .card { min-width:200px; }
  .card small { display:block; margin-top:8px; color:#666; line-height:1.7; }
</style>
</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<?php include("includes/header.php"); ?>

<div class="layout">
    <?php include("includes/sidebar.php"); ?>

    <main class="content">

        <h2>لوحة تحكم النقابة</h2>

        <div class="stats">
            <!-- Exams -->
            <div class="card">
                <h3>طلبات امتحان المزاولة</h3>
                <p><?= $examAll ?></p>
                <small>
                  جاهز: <?= $examStats['waiting_exam'] ?><br>
                  مجدول: <?= $examStats['scheduled'] ?><br>
                  ناجح: <?= $examStats['passed'] ?><br>
                  راسب: <?= $examStats['failed'] ?>
                </small>
            </div>

            <!-- Membership -->
            <div class="card">
                <h3>طلبات الانتساب</h3>
                <p><?= $membershipAll ?></p>
                <small>
                  قيد المراجعة: <?= $membershipStats['pending'] ?><br>
                  مقبول: <?= $membershipStats['approved'] ?><br>
                  مرفوض: <?= $membershipStats['rejected'] ?>
                </small>
            </div>

            <!-- Contact Messages -->
            <div class="card">
                <h3>رسائل تواصل معنا</h3>
                <p><?= (int)$contactAll ?></p>
                <small>
                  جديدة: <?= $contactStats['new'] ?><br>
                  مقروءة: <?= $contactStats['read'] ?><br>
                  مغلقة: <?= $contactStats['closed'] ?>
                </small>
            </div>
        </div>

    </main>
</div>

<?php include("includes/footer.php"); ?>

</body>
</html>
