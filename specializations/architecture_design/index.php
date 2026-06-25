<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>قسم العمارة والتصميم | متدرب</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
  <link rel="stylesheet" href="/mutadarrib/assets/css/specialization.css">
</head>
<body>

<?php include("../../includes/header.php"); ?>

<section class="spec-page">
  <div class="spec-header">
    <h1>🏛️ قسم العمارة والتصميم</h1>
    <p>بوابتك إلى فرص التدريب في مكاتب التصميم، العمارة، التصميم الداخلي، والجرافيك.</p>
  </div>

  <div class="spec-cards">

    <div class="spec-card">
      <div class="spec-icon">🎓</div>
      <span class="spec-badge">التسجيل كمتدرب تصميم</span>
      <h3>ابدأ مسيرتك الإبداعية</h3>
      <p>سجل كمتدرب، أضف أعمالك ومهاراتك، وتقدم لفرص تدريب في مكاتب التصميم والعمارة.</p>
      <a href="/mutadarrib/auth/register_architecture_design.php" class="spec-btn">تسجيل متدرب تصميم</a>
    </div>

    <div class="spec-card">
      <div class="spec-icon">🏢</div>
      <span class="spec-badge">التسجيل كمزود تدريب</span>
      <h3>استقبل متدربين مبدعين</h3>
      <p>سجل كمكتب معماري أو استوديو تصميم، وانشر فرص تدريب للمتدربين.</p>
      <a href="/mutadarrib/auth/register_architecture_design.php" class="spec-btn outline">تسجيل مزود تصميم</a>
    </div>

    <div class="spec-card">
      <div class="spec-icon">📋</div>
      <span class="spec-badge">فرص تدريب التصميم</span>
      <h3>استعرض فرص التدريب</h3>
      <p>تصفح فرص التدريب في العمارة، التصميم الداخلي، الجرافيك، والرسم الهندسي.</p>
      <a href="/mutadarrib/specializations/architecture_design/internships_list.php" class="spec-btn">عرض الفرص</a>
    </div>

  </div>
</section>

<?php include("../../includes/footer.php"); ?>

</body>
</html>