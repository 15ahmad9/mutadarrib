<?php
session_start();
require_once("../../config/db.php");

$SPECIALIZATION_SLUG = "architecture_design";

if (!isset($_SESSION['user_id'])) {
  header("Location: /mutadarrib/auth/login.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  die("رقم الفرصة غير صالح.");
}

$stmt = $pdo->prepare("
  SELECT *
  FROM specialization_internships
  WHERE internship_id = ?
    AND specialization_slug = ?
  LIMIT 1
");
$stmt->execute([$id, $SPECIALIZATION_SLUG]);
$internship = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$internship) {
  die("الفرصة غير موجودة.");
}

if ($internship['status'] !== 'open') {
  die("هذه الفرصة مغلقة حالياً.");
}

$stmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM specialization_applications
  WHERE internship_id = ?
    AND status IN ('pending','accepted')
");
$stmt->execute([$id]);
$currentApplications = (int)$stmt->fetchColumn();

if ($currentApplications >= (int)$internship['seats']) {
  die("لا توجد مقاعد متاحة لهذه الفرصة.");
}

$stmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM specialization_applications
  WHERE internship_id = ?
    AND user_id = ?
");
$stmt->execute([$id, $user_id]);

if ((int)$stmt->fetchColumn() > 0) {
  die("لقد قمت بالتقديم على هذه الفرصة مسبقاً.");
}

$stmt = $pdo->prepare("
  INSERT INTO specialization_applications
  (internship_id, user_id, status)
  VALUES (?, ?, 'pending')
");
$stmt->execute([$id, $user_id]);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تم التقديم</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
  <link rel="stylesheet" href="/mutadarrib/assets/css/specialization.css">
</head>
<body>

<?php include("../../includes/header.php"); ?>

<section class="spec-page">
  <div class="spec-cards" style="grid-template-columns: 1fr; max-width: 700px;">
    <div class="spec-card">
      <h3>تم تقديم طلبك بنجاح</h3>
      <p>تم إرسال طلبك وهو الآن قيد المراجعة من قبل مزود التدريب.</p>

      <a class="spec-btn" href="internships_list.php">العودة إلى فرص التدريب</a>
    </div>
  </div>
</section>

<?php include("../../includes/footer.php"); ?>

</body>
</html>