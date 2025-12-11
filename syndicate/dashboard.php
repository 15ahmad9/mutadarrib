<?php
require_once("includes/auth_check.php");
require_once("../config/db.php");

// إحصائيات سريعة
$stmt = $pdo->query("
    SELECT status, COUNT(*) AS cnt
    FROM syndicate_exam_requests
    GROUP BY status
");

$stats = [
    'waiting_exam' => 0,
    'passed'       => 0,
    'failed'       => 0
];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats[$row['status']] = (int)$row['cnt'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>لوحة نقابة المحامين</title>
<link rel="stylesheet" href="../assets/css/lawyers.css">
</head>
<body>

<?php include("includes/header.php"); ?>

<div class="layout">
    <?php include("includes/sidebar.php"); ?>

    <main class="content">

        <h2>لوحة تحكم النقابة</h2>

        <div class="stats">
            <div class="card">
                <h3>جاهزون للامتحان</h3>
                <p><?= $stats['waiting_exam'] ?></p>
            </div>
            <div class="card">
                <h3>ناجحون في الامتحان</h3>
                <p><?= $stats['passed'] ?></p>
            </div>
            <div class="card">
                <h3>راسبون</h3>
                <p><?= $stats['failed'] ?></p>
            </div>
        </div>

        <p>
            يمكنك إدارة طلبات الامتحان من خلال صفحة
            <a href="exams.php">طلبات امتحان المزاولة</a>.
        </p>

    </main>
</div>

<?php include("includes/footer.php"); ?>

</body>
</html>
