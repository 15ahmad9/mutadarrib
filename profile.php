<?php
session_start();
require_once("config/db.php");

// التحقق من أن المستخدم مسجل دخول
if (!isset($_SESSION['user_id'])) {
    header("Location: ./auth/login.php");
    exit;
}

// جلب بيانات المستخدم من قاعدة البيانات
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// إذا كان محامي مزاول، اجلب بيانات إضافية
$lawyer = null;
if ($user && $user['role'] === 'lawyer') {
    $stmt2 = $pdo->prepare("SELECT * FROM lawyers WHERE user_id = ?");
    $stmt2->execute([$user_id]);
    $lawyer = $stmt2->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>الملف الشخصي | <?= htmlspecialchars($user['full_name']) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include("includes/header.php"); ?>

<div class="container profile-page">
  <h2>👤 الملف الشخصي</h2>

  <div class="profile-card">
    <p><strong>الاسم الكامل:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
    <p><strong>الرقم الوطني:</strong> <?= htmlspecialchars($user['national_id']) ?></p>
    <p><strong>رقم الهاتف:</strong> <?= htmlspecialchars($user['phone']) ?></p>
    <p><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <p><strong>العنوان:</strong> <?= htmlspecialchars($user['address']) ?></p>
    <p><strong>نوع الحساب:</strong> <?= ($user['role'] === 'lawyer') ? 'محامي مزاول' : 'طالب' ?></p>

    <a href="edit_profile.php" class="edit-btn">تعديل الملف الشخصي</a>

    <?php if ($lawyer): ?>
      <hr>
      <h3>⚖️ معلومات المزاول</h3>
      <p><strong>رقم السجل:</strong> <?= htmlspecialchars($lawyer['master_id']) ?></p>
      <p><strong>عنوان المكتب:</strong> <?= htmlspecialchars($lawyer['office_address']) ?></p>
      <p><strong>الحالة:</strong> <?= ($lawyer['verified']) ? '✅ موثّق' : '⏳ قيد التحقق' ?></p>
    <?php endif; ?>

    <a href="./auth/logout.php" class="logout-btn">تسجيل الخروج</a>
  </div>
</div>

<?php include("includes/footer.php"); ?>

</body>
</html>
