<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>قسم الأعمال | متدرب</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
  <link rel="stylesheet" href="/mutadarrib/assets/css/specialization.css">
</head>
<body>

<?php include("../../includes/header.php"); ?>

<section class="spec-page">
  <div class="spec-header">
    <h1>💼 قسم الأعمال</h1>
    <p>بوابتك إلى فرص التدريب الإداري والمالي والتسويقي وربط المتدربين بالشركات ومزودي التدريب بسهولة.</p>
  </div>

  <div class="spec-cards">

    <div class="spec-card">
      <div class="spec-icon">🎓</div>
      <span class="spec-badge">التسجيل كمتدرب أعمال</span>
      <h3>ابدأ مسيرتك المهنية</h3>
      <p>سجل كمتدرب في مجال الأعمال، أضف مهاراتك وخبراتك، وتقدم لفرص التدريب المناسبة.</p>
      <a href="/mutadarrib/auth/register_business.php" class="spec-btn">تسجيل متدرب أعمال</a>
    </div>

    <div class="spec-card">
      <div class="spec-icon">🏢</div>
      <span class="spec-badge">التسجيل كمزود تدريب</span>
      <h3>هل ترغب باستقبال متدربين؟</h3>
      <p>سجل كشركة أو مؤسسة تدريبية، أنشئ فرص تدريب، واستقبل طلبات المتدربين بسهولة.</p>
      <a href="/mutadarrib/auth/register_business.php" class="spec-btn outline">تسجيل مزود أعمال</a>
    </div>

    <div class="spec-card">
      <div class="spec-icon">📋</div>
      <span class="spec-badge">فرص تدريب الأعمال</span>
      <h3>استعرض فرص التدريب</h3>
      <p>تصفح فرص التدريب المتاحة حسب المجال، المدينة، وطبيعة التدريب.</p>
      <a href="/mutadarrib/specializations/business/internships_list.php" class="spec-btn">عرض الفرص</a>
    </div>

  </div>
</section>

<?php include("../../includes/footer.php"); ?>

</body>
</html>