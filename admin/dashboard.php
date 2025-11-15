<?php
require_once("../config/db.php");
include("includes/auth_check.php");

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>لوحة التحكم | الإدارة</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<?php include("includes/header.php"); ?>
<?php include("includes/sidebar.php"); ?>

<div class="admin-container">
  <h1>👨‍💼 لوحة تحكم المدير</h1>

  <div class="stats">
    <?php
    // إحصائيات سريعة
    $usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $lawyersCount = $pdo->query("SELECT COUNT(*) FROM lawyers")->fetchColumn();
    $verifiedLawyers = $pdo->query("SELECT COUNT(*) FROM lawyers WHERE verified = 1")->fetchColumn();
    ?>
    <div class="card">👥 المستخدمين: <strong><?= $usersCount ?></strong></div>
    <div class="card">⚖️ المحامين: <strong><?= $lawyersCount ?></strong></div>
    <div class="card">✅ المحامين الموثقين: <strong><?= $verifiedLawyers ?></strong></div>
  </div>

  <div class="quick-links">
    <a href="users/users.php" class="btn">إدارة المستخدمين</a>
    <a href="lawyers/lawyers.php" class="btn">إدارة المحامين</a>
    <a href="lawyers/master_lawyers.php" class="btn">سجل المزاولين</a>
  </div>
</div>

<?php include("includes/footer.php"); ?>

</body>
</html>
