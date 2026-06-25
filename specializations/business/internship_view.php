<?php
session_start();
require_once("../../config/db.php");

$SPECIALIZATION_SLUG = "business";

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
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($internship['title']) ?></title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
  <link rel="stylesheet" href="/mutadarrib/assets/css/specialization.css">
</head>
<body>

<?php include("../../includes/header.php"); ?>

<section class="spec-page">
  <div class="spec-header">
    <h1><?= htmlspecialchars($internship['title']) ?></h1>
    <p><?= htmlspecialchars($internship['provider_name']) ?></p>
  </div>

  <div class="spec-cards" style="grid-template-columns: 1fr; max-width: 850px;">
    <div class="spec-card">
      <span class="spec-badge"><?= htmlspecialchars($internship['training_type']) ?></span>

      <h3>تفاصيل فرصة التدريب</h3>

      <p><?= nl2br(htmlspecialchars($internship['description'] ?? 'لا يوجد وصف.')) ?></p>

      <p><strong>الموقع:</strong> <?= htmlspecialchars($internship['location'] ?? '-') ?></p>
      <p><strong>عدد المقاعد:</strong> <?= (int)$internship['seats'] ?></p>
      <p><strong>تاريخ البداية:</strong> <?= htmlspecialchars($internship['start_date'] ?? '-') ?></p>
      <p><strong>تاريخ النهاية:</strong> <?= htmlspecialchars($internship['end_date'] ?? '-') ?></p>
      <p><strong>الحالة:</strong> <?= $internship['status'] === 'open' ? 'متاح للتقديم' : 'مغلق' ?></p>

      <?php if ($internship['status'] === 'open'): ?>
        <a class="spec-btn" href="apply.php?id=<?= (int)$internship['internship_id'] ?>">
          التقديم على الفرصة
        </a>
      <?php else: ?>
        <span class="spec-btn outline">التقديم مغلق</span>
      <?php endif; ?>

      <br><br>
      <a href="internships_list.php">العودة للفرص</a>
    </div>
  </div>
</section>

<?php include("../../includes/footer.php"); ?>

</body>
</html>