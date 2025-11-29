<?php
session_start();
require_once("../../config/db.php");

$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT *
    FROM lawyers
    WHERE lawyer_id = ?
      AND verified = 1
");
$stmt->execute([$id]);
$lawyer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lawyer) {
    die("❌ هذا المكتب غير موجود أو غير مفعّل.");
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($lawyer['full_name']) ?> | مكتب محاماة</title>

<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/lawyers.css">
</head>
<body>

<?php include("../../includes/header.php"); ?>

<div class="container profile-container">

  <div class="profile-card">

      <div class="avatar large">⚖️</div>

      <h2><?= htmlspecialchars($lawyer['full_name']) ?></h2>

      <p><strong>رقم السجل:</strong> <?= htmlspecialchars($lawyer['syndicate_id']) ?></p>
      <p><strong>📞 الهاتف:</strong> <?= htmlspecialchars($lawyer['phone']) ?></p>
      <p><strong>📧 البريد:</strong> <?= htmlspecialchars($lawyer['email']) ?></p>
      <p><strong>📍 عنوان المكتب:</strong> <?= htmlspecialchars($lawyer['office_address']) ?></p>

      <hr>

      <h3>🎓 استقبال متدربين</h3>
      <p>
        هذا المكتب يستقبل طلبات تدريب لخريجي كليات الحقوق.
      </p>

      <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'trainee'): ?>

          <a class="apply-btn"
             href="apply_training.php?lawyer_id=<?= $lawyer['lawyer_id'] ?>">
             ✅ التقديم للتدريب
          </a>

      <?php elseif(!isset($_SESSION['user_id'])): ?>

        <p class="warning">
          ⚠ يجب تسجيل الدخول كمتدرب للتقديم.
        </p>
        <a href="../../auth/login.php" class="btn">
            تسجيل الدخول
        </a>

      <?php else: ?>

        <p class="warning">
          ❌ التقديم متاح فقط لحسابات المتدربين.
        </p>

      <?php endif; ?>

  </div>

</div>

<?php include("../../includes/footer.php"); ?>
</body>
</html>
