<?php
session_start();
require_once("../../config/db.php");

$SPECIALIZATION_SLUG  = "business";
$SPECIALIZATION_TITLE = "الأعمال";

$stmt = $pdo->prepare("
  SELECT *
  FROM specialization_internships
  WHERE specialization_slug = ?
  ORDER BY created_at DESC
");
$stmt->execute([$SPECIALIZATION_SLUG]);
$internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>فرص تدريب <?= htmlspecialchars($SPECIALIZATION_TITLE) ?></title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
  <link rel="stylesheet" href="/mutadarrib/assets/css/specialization.css">
</head>
<body>

<?php include("../../includes/header.php"); ?>

<section class="spec-page">
  <div class="spec-header">
    <h1>فرص تدريب <?= htmlspecialchars($SPECIALIZATION_TITLE) ?></h1>
    <p>استعرض فرص التدريب المتاحة لهذا التخصص وتقدم للفرصة المناسبة.</p>
  </div>

  <div class="spec-cards">
    <?php if (!$internships): ?>
      <div class="spec-card">
        <h3>لا توجد فرص حالياً</h3>
        <p>سيتم إضافة فرص تدريب لهذا القسم قريباً.</p>
        <a href="/mutadarrib/soon.php" class="spec-btn">قريباً</a>
      </div>
    <?php else: ?>
      <?php foreach ($internships as $item): ?>
        <div class="spec-card">
          <div class="spec-icon">📋</div>
          <span class="spec-badge"><?= htmlspecialchars($item['training_type']) ?></span>

          <h3><?= htmlspecialchars($item['title']) ?></h3>

          <p>
            <strong>مزود التدريب:</strong>
            <?= htmlspecialchars($item['provider_name']) ?>
          </p>

          <p>
            <strong>الموقع:</strong>
            <?= htmlspecialchars($item['location'] ?? '-') ?>
          </p>

          <p>
            <strong>الحالة:</strong>
            <?= $item['status'] === 'open' ? 'متاح' : 'مغلق' ?>
          </p>

          <a class="spec-btn" href="internship_view.php?id=<?= (int)$item['internship_id'] ?>">
            عرض التفاصيل
          </a>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<?php include("../../includes/footer.php"); ?>

</body>
</html>