<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>قسم الآداب | متدرب</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
  <link rel="stylesheet" href="/mutadarrib/assets/css/specialization.css">
</head>
<body>

<?php include("../../includes/header.php"); ?>

<section class="spec-page">
  <div class="spec-header">
    <h1>📚 قسم الآداب</h1>
    <p>منصة تربط خريجي تخصصات الآداب واللغات والإعلام بفرص تدريب ميدانية ومهنية مناسبة.</p>
  </div>

  <div class="spec-cards">

    <div class="spec-card">
      <div class="spec-icon">🎓</div>
      <span class="spec-badge">التسجيل كمتدرب آداب</span>
      <h3>ابدأ رحلتك المهنية</h3>
      <p>سجل كمتدرب، أضف مجال تخصصك ومهاراتك، وتابع فرص التدريب المتاحة.</p>
      <a href="/mutadarrib/auth/register_literature.php" class="spec-btn">تسجيل متدرب آداب</a>
    </div>

    <div class="spec-card">
      <div class="spec-icon">🏛️</div>
      <span class="spec-badge">التسجيل كمزود تدريب</span>
      <h3>هل لديك فرصة تدريبية؟</h3>
      <p>سجل كمؤسسة تدريبية أو مركز ثقافي أو إعلامي، وانشر فرص التدريب بسهولة.</p>
      <a href="/mutadarrib/auth/register_literature.php" class="spec-btn outline">تسجيل مزود آداب</a>
    </div>

    <div class="spec-card">
      <div class="spec-icon">📋</div>
      <span class="spec-badge">فرص تدريب الآداب</span>
      <h3>استعرض فرص التدريب</h3>
      <p>تصفح فرص التدريب في الكتابة، الترجمة، الإعلام، العلاقات العامة والمجالات الثقافية.</p>
      <a href="/mutadarrib/specializations/literature/internships_list.php" class="spec-btn">عرض الفرص</a>
    </div>

  </div>
</section>

<?php include("../../includes/footer.php"); ?>

</body>
</html>