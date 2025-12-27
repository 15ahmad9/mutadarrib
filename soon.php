<?php
require_once __DIR__ . '/includes/theme_init.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>قريباً | متدرب</title>

  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css" />
  <link rel="stylesheet" href="/mutadarrib/assets/css/lawyers.css" />

  <style>
  </style>
</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<?php include __DIR__ . "/includes/header.php"; ?>

<div class="soon-wrap">
  <div class="soon-card">
    <div class="soon-illus">⏳</div>
    <h1 class="soon-title">سيتم إضافة هذا القسم قريباً</h1>
    <p class="soon-sub">
      نعمل حالياً على تطوير هذا القسم وتوفير تجربة متكاملة بنفس معايير منصة متدرب.
      يمكنك العودة للصفحة الرئيسية أو التواصل معنا لأي استفسار.
    </p>

    <div class="soon-actions">
      <a class="btnx" href="/mutadarrib/index.php">العودة للرئيسية</a>
      <a class="btnx secondary" href="/mutadarrib/contact.php">تواصل معنا</a>
    </div>
  </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>

</body>
</html>
