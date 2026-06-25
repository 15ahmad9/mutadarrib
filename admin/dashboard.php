<?php
require_once __DIR__ . '/../includes/theme_init.php';

require_once("../config/db.php");
include("includes/auth_check.php");

// إحصائيات سريعة
$usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$lawyersCount = $pdo->query("SELECT COUNT(*) FROM lawyers")->fetchColumn();
$verifiedLawyers = $pdo->query("SELECT COUNT(*) FROM lawyers WHERE verified = 1")->fetchColumn();

// توزيع المستخدمين حسب الدور
$roleCounts = [];
$stmtRoles = $pdo->query("SELECT role, COUNT(*) AS c FROM users GROUP BY role");
while ($row = $stmtRoles->fetch(PDO::FETCH_ASSOC)) {
  $roleCounts[$row['role']] = (int) $row['c'];
}
$adminsCount = (int) ($roleCounts['admin'] ?? 0);
$syndicateAdminsCount = (int) ($roleCounts['syndicate_admin'] ?? 0);
$traineesCount = (int) ($roleCounts['trainee'] ?? 0);
$lawyerUsersCount = (int) ($roleCounts['lawyer'] ?? 0);
$unverifiedLawyers = max(0, (int) $lawyersCount - (int) $verifiedLawyers);

$pendingApps = $pdo->query("SELECT COUNT(*) FROM training_applications WHERE status = 'pending'")->fetchColumn();
$completedApps = $pdo->query("SELECT COUNT(*) FROM training_applications WHERE status = 'completed'")->fetchColumn();
$trainingAll = $pdo->query("SELECT COUNT(*) FROM training_applications")->fetchColumn();

$waitingExam = $pdo->query("SELECT COUNT(*) FROM syndicate_exam_requests WHERE status = 'waiting_exam'")->fetchColumn();
$scheduledExam = $pdo->query("SELECT COUNT(*) FROM syndicate_exam_requests WHERE status = 'scheduled'")->fetchColumn();
$passedExam = $pdo->query("SELECT COUNT(*) FROM syndicate_exam_requests WHERE status = 'passed'")->fetchColumn();
$failedExam = $pdo->query("SELECT COUNT(*) FROM syndicate_exam_requests WHERE status = 'failed'")->fetchColumn();
$examAll = $pdo->query("SELECT COUNT(*) FROM syndicate_exam_requests")->fetchColumn();

$membershipPending = $pdo->query("SELECT COUNT(*) FROM membership_requests WHERE status='pending'")->fetchColumn();
$membershipApproved = $pdo->query("SELECT COUNT(*) FROM membership_requests WHERE status='approved'")->fetchColumn();
$membershipRejected = $pdo->query("SELECT COUNT(*) FROM membership_requests WHERE status='rejected'")->fetchColumn();
$membershipAll = $pdo->query("SELECT COUNT(*) FROM membership_requests")->fetchColumn();

// ===========================
// فرص تدريب التخصصات
// ===========================
$specInternshipsAll = 0;
$specInternshipsOpen = 0;
$specInternshipsClosed = 0;
$specApplicationsAll = 0;

try {
  $specInternshipsAll = (int)$pdo->query("SELECT COUNT(*) FROM specialization_internships")->fetchColumn();
  $specInternshipsOpen = (int)$pdo->query("SELECT COUNT(*) FROM specialization_internships WHERE status='open'")->fetchColumn();
  $specInternshipsClosed = (int)$pdo->query("SELECT COUNT(*) FROM specialization_internships WHERE status='closed'")->fetchColumn();
  $specApplicationsAll = (int)$pdo->query("SELECT COUNT(*) FROM specialization_applications")->fetchColumn();
} catch (Exception $e) {
  $specInternshipsAll = 0;
  $specInternshipsOpen = 0;
  $specInternshipsClosed = 0;
  $specApplicationsAll = 0;
}

// ===========================
// رسائل تواصل معنا
// ===========================
// غيّر اسم الجدول إذا كان مختلفاً عندك
$contactTable = "contact_messages";

$contactNew = 0;
$contactRead = 0;
$contactClosed = 0;
$contactAll = 0;

try {
  $stmtC = $pdo->query("SELECT status, COUNT(*) AS c FROM {$contactTable} GROUP BY status");
  while ($r = $stmtC->fetch(PDO::FETCH_ASSOC)) {
    if ($r['status'] === 'new') $contactNew = (int)$r['c'];
    elseif ($r['status'] === 'read') $contactRead = (int)$r['c'];
    elseif ($r['status'] === 'closed') $contactClosed = (int)$r['c'];
  }
  $contactAll = (int)$pdo->query("SELECT COUNT(*) FROM {$contactTable}")->fetchColumn();
} catch (Exception $e) {
  // الجدول غير موجود / الاسم مختلف
  $contactAll = 0;
}
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

    <div class="container dashboard-container">
      <h1>لوحة تحكم المدير</h1>

      <div class="dash-grid">
        <!-- Users -->
        <section class="dash-card">
          <div class="dash-card-head">
            <div class="dash-card-title"><span class="dash-icon" data-icon="users" aria-hidden="true"></span> المستخدمون</div>
            <div class="dash-card-total"><?= (int) $usersCount ?></div>
          </div>

          <div class="dash-breakdown">
            <div class="dash-row"><span>المدراء</span><strong><?= (int) $adminsCount ?></strong></div>
            <div class="dash-row"><span>موظفو النقابة</span><strong><?= (int) $syndicateAdminsCount ?></strong></div>
            <div class="dash-row"><span>المحامون</span><strong><?= (int) $lawyerUsersCount ?></strong></div>
            <div class="dash-row"><span>محامون موثقون</span><strong><?= (int) $verifiedLawyers ?></strong></div>
            <div class="dash-row"><span>محامون غير موثقين</span><strong><?= (int) $unverifiedLawyers ?></strong></div>
            <div class="dash-row"><span>المتدربون</span><strong><?= (int) $traineesCount ?></strong></div>
          </div>
        </section>

        <!-- Training applications -->
        <section class="dash-card">
          <div class="dash-card-head">
            <div class="dash-card-title"><span class="dash-icon" data-icon="training" aria-hidden="true"></span> طلبات التدريب</div>
            <div class="dash-card-total"><?= (int) $trainingAll ?></div>
          </div>

          <div class="dash-breakdown">
            <div class="dash-row"><span>قيد المراجعة</span><strong><?= (int) $pendingApps ?></strong></div>
            <div class="dash-row"><span>مكتملة</span><strong><?= (int) $completedApps ?></strong></div>
          </div>
        </section>

        <!-- Exams -->
        <section class="dash-card">
          <div class="dash-card-head">
            <div class="dash-card-title"><span class="dash-icon" data-icon="exam" aria-hidden="true"></span> طلبات الامتحان</div>
            <div class="dash-card-total"><?= (int) $examAll ?></div>
          </div>

          <div class="dash-breakdown">
            <div class="dash-row"><span>جاهز</span><strong><?= (int) $waitingExam ?></strong></div>
            <div class="dash-row"><span>مجدول</span><strong><?= (int) $scheduledExam ?></strong></div>
            <div class="dash-row"><span>ناجح</span><strong><?= (int) $passedExam ?></strong></div>
            <div class="dash-row"><span>راسب</span><strong><?= (int) $failedExam ?></strong></div>
          </div>
        </section>

        <!-- Membership -->
        <section class="dash-card">
          <div class="dash-card-head">
            <div class="dash-card-title"><span class="dash-icon" data-icon="membership" aria-hidden="true"></span> طلبات الانتساب</div>
            <div class="dash-card-total"><?= (int) $membershipAll ?></div>
          </div>

          <div class="dash-breakdown">
            <div class="dash-row"><span>قيد المراجعة</span><strong><?= (int) $membershipPending ?></strong></div>
            <div class="dash-row"><span>مقبولة</span><strong><?= (int) $membershipApproved ?></strong></div>
            <div class="dash-row"><span>مرفوضة</span><strong><?= (int) $membershipRejected ?></strong></div>
          </div>
        </section>

<!-- Specialization Internships -->
<section class="dash-card">
  <div class="dash-card-head">
    <div class="dash-card-title">
      <span class="dash-icon" data-icon="training" aria-hidden="true"></span>
      فرص تدريب التخصصات
    </div>
    <div class="dash-card-total"><?= (int) $specInternshipsAll ?></div>
  </div>

  <div class="dash-breakdown">
    <div class="dash-row">
      <span>مفتوحة</span>
      <strong><?= (int) $specInternshipsOpen ?></strong>
    </div>
    <div class="dash-row">
      <span>مغلقة</span>
      <strong><?= (int) $specInternshipsClosed ?></strong>
    </div>
    <div class="dash-row">
      <span>طلبات التقديم</span>
      <strong><?= (int) $specApplicationsAll ?></strong>
    </div>
  </div>

  <div style="margin-top:14px;">
    <a href="/mutadarrib/admin/internship/internships.php" class="btn">
      إدارة فرص التدريب
    </a>
  </div>
</section>

        <!-- Contact Messages -->
        <section class="dash-card ">
          <div class="dash-card-head">
            <div class="dash-card-title"><span class="dash-icon" data-icon="contact" aria-hidden="true"></span> رسائل تواصل معنا</div>
            <div class="dash-card-total"><?= (int) $contactAll ?></div>
          </div>

          <div class="dash-breakdown">
            <div class="dash-row"><span>جديدة</span><strong><?= (int) $contactNew ?></strong></div>
            <div class="dash-row"><span>مقروءة</span><strong><?= (int) $contactRead ?></strong></div>
            <div class="dash-row"><span>مغلقة</span><strong><?= (int) $contactClosed ?></strong></div>
          </div>
        </section>

      </div>
    </div>
  </div>

  <?php include("includes/footer.php"); ?>
</body>

</html>
