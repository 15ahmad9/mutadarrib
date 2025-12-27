<?php
require_once __DIR__ . '/../includes/theme_init.php';

require_once("../config/db.php");
include("includes/auth_check.php");

// إحصائيات سريعة
$usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$lawyersCount = $pdo->query("SELECT COUNT(*) FROM lawyers")->fetchColumn();
$verifiedLawyers = $pdo->query("SELECT COUNT(*) FROM lawyers WHERE verified = 1")->fetchColumn();

$pendingApps = $pdo->query("SELECT COUNT(*) FROM training_applications WHERE status = 'pending'")->fetchColumn();
$completedApps = $pdo->query("SELECT COUNT(*) FROM training_applications WHERE status = 'completed'")->fetchColumn();

$waitingExam = $pdo->query("SELECT COUNT(*) FROM syndicate_exam_requests WHERE status = 'waiting_exam'")->fetchColumn();
$scheduledExam = $pdo->query("SELECT COUNT(*) FROM syndicate_exam_requests WHERE status = 'scheduled'")->fetchColumn();
$passedExam = $pdo->query("SELECT COUNT(*) FROM syndicate_exam_requests WHERE status = 'passed'")->fetchColumn();
$failedExam = $pdo->query("SELECT COUNT(*) FROM syndicate_exam_requests WHERE status = 'failed'")->fetchColumn();

$membershipPending = $pdo->query("SELECT COUNT(*) FROM membership_requests WHERE status='pending'")->fetchColumn();
$membershipApproved = $pdo->query("SELECT COUNT(*) FROM membership_requests WHERE status='approved'")->fetchColumn();
$membershipRejected = $pdo->query("SELECT COUNT(*) FROM membership_requests WHERE status='rejected'")->fetchColumn();
$membershipAll = $pdo->query("SELECT COUNT(*) FROM membership_requests")->fetchColumn();

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <title>لوحة التحكم | الإدارة</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body data-theme="<?= htmlspecialchars($theme) ?>">

  <?php include("includes/header.php"); ?>
  <?php include("includes/sidebar.php"); ?>

  <div class="admin-container">

    <div class="container">
      <h1>لوحة تحكم المدير</h1>

      <div class="stats">
        <div class="card">المستخدمين: <strong><?= (int) $usersCount ?></strong></div>
        <div class="card">المحامين: <strong><?= (int) $lawyersCount ?></strong></div>
        <div class="card">المحامين الموثقين: <strong><?= (int) $verifiedLawyers ?></strong></div>

        <div class="card">طلبات تدريب (قيد المراجعة): <strong><?= (int) $pendingApps ?></strong></div>
        <div class="card">طلبات تدريب (مكتملة): <strong><?= (int) $completedApps ?></strong></div>

        <div class="card">طلبات امتحان (جاهز): <strong><?= (int) $waitingExam ?></strong></div>
        <div class="card">طلبات امتحان (مجدول): <strong><?= (int) $scheduledExam ?></strong></div>
        <div class="card">طلبات امتحان (ناجح): <strong><?= (int) $passedExam ?></strong></div>
        <div class="card">طلبات امتحان (راسب): <strong><?= (int) $failedExam ?></strong></div>


        <div class="card">طلبات انتساب (الكل): <strong><?= (int) $membershipAll ?></strong></div>
        <div class="card">طلبات انتساب (قيد المراجعة): <strong><?= (int) $membershipPending ?></strong></div>
        <div class="card">طلبات انتساب (مقبولة): <strong><?= (int) $membershipApproved ?></strong></div>
        <div class="card">طلبات انتساب (مرفوضة): <strong><?= (int) $membershipRejected ?></strong></div>
      </div>

      <div class="quick-links">
        <a href="users/users.php" class="btn">إدارة المستخدمين</a>
        <a href="lawyers/lawyers.php" class="btn">إدارة المحامين</a>
        <a href="lawyers/syndicate_lawyers.php" class="btn">سجل المزاولين</a>

        <a href="training_applications.php" class="btn">طلبات التدريب</a>
        <a href="exams.php" class="btn">طلبات الامتحان</a>
        <a href="membership/requests.php" class="btn">طلبات الانتساب</a>
      </div>
    </div>
  </div>
  <?php include("includes/footer.php"); ?>
</body>

</html>