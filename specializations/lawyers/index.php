<?php
require_once __DIR__ . '/../../includes/theme_init.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>قسم المحامين | منصة متدرب</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="../../assets/css/lawyers.css">
</head>

<body data-theme="<?= htmlspecialchars($theme) ?>">
  <?php include("../../includes/header.php"); ?>

  <main class="lawyers-landing">
    <div class="lawyers-landing__inner">

      <div class="lawyers-landing__head">
        <h1 class="lawyers-landing__title">⚖️ قسم المحامين</h1>
        <p class="lawyers-landing__subtitle">
          بوابتك إلى التدريب القانوني المهني المعتمد، بإشراف نخبة من كبار المحامين.
        </p>
      </div>

      <section class="lawyers-landing__grid" aria-label="خدمات قسم المحامين">
        <article class="law-card">
          <div class="law-card__top">
            <span class="law-card__icon" aria-hidden="true">🏢</span>
            <span class="law-card__badge">مكاتب المحامين</span>
          </div>
          <h3 class="law-card__title">استعرض المكاتب المعتمدة</h3>
          <p class="law-card__desc">تصفح جميع المكاتب المعتمدة التي تستقبل متدربين، واطّلع على التفاصيل والتدريبات المتاحة.</p>
          <div class="law-card__actions">
            <a class="law-card__btn" href="lawyers_offices.php">عرض المكاتب</a>
          </div>
        </article>

        <article class="law-card">
          <div class="law-card__top">
            <span class="law-card__icon" aria-hidden="true">🧑‍⚖️</span>
            <span class="law-card__badge">التسجيل كمحامي</span>
          </div>
          <h3 class="law-card__title">هل تريد استقبال متدربين؟</h3>
          <p class="law-card__desc">قدّم طلب اعتماد مكتبك وابدأ بطرح تدريبات مهنية للمتدربين ضمن نظام واضح وسهل.</p>
          <div class="law-card__actions">
            <a class="law-card__btn law-card__btn--ghost" href="../../auth/register.php">تسجيل محام</a>
          </div>
        </article>

        <article class="law-card">
          <div class="law-card__top">
            <span class="law-card__icon" aria-hidden="true">🎓</span>
            <span class="law-card__badge">التسجيل كمتدرب</span>
          </div>
          <h3 class="law-card__title">ابدأ مسيرتك المهنية</h3>
          <p class="law-card__desc">اختر تخصصك وقدّم طلب التدريب بسهولة، وتابع حالتك وخطواتك القادمة من لوحة المتدرب.</p>
          <div class="law-card__actions">
            <a class="law-card__btn" href="../../auth/choose_specialization.php">تسجيل متدرب</a>
          </div>
        </article>
      </section>

    </div>
  </main>

  <?php include("../../includes/footer.php"); ?>
</body>

</html>
