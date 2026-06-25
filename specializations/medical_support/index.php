<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>قسم الدعم الطبي | متدرب</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
  <link rel="stylesheet" href="/mutadarrib/assets/css/specialization.css">
</head>
<body>

<?php include("../../includes/header.php"); ?>

<section class="spec-page">
  <div class="spec-header">
    <h1>🩺 قسم الدعم الطبي</h1>
    <p>منصة لربط المتدربين بالمراكز الطبية، المختبرات، العيادات، ومؤسسات الرعاية الصحية.</p>
  </div>

  <div class="spec-cards">

    <div class="spec-card">
      <div class="spec-icon">🎓</div>
      <span class="spec-badge">التسجيل كمتدرب طبي</span>
      <h3>ابدأ مسيرتك الصحية</h3>
      <p>سجل كمتدرب في مجال الدعم الطبي، أضف مؤهلاتك، وتابع فرص التدريب المتاحة.</p>
      <a href="/mutadarrib/auth/register_medical_support.php" class="spec-btn">تسجيل متدرب طبي</a>
    </div>

    <div class="spec-card">
      <div class="spec-icon">🏥</div>
      <span class="spec-badge">التسجيل كمزود تدريب</span>
      <h3>هل تستقبل متدربين؟</h3>
      <p>سجل كمركز طبي أو مختبر أو عيادة، وانشر فرص التدريب للمتدربين.</p>
      <a href="/mutadarrib/auth/register_medical_support.php" class="spec-btn outline">تسجيل مزود طبي</a>
    </div>

    <div class="spec-card">
      <div class="spec-icon">📋</div>
      <span class="spec-badge">فرص الدعم الطبي</span>
      <h3>استعرض فرص التدريب</h3>
      <p>تصفح فرص التدريب في المختبرات، العيادات، التمريض المساند، والرعاية الصحية.</p>
      <a href="/mutadarrib/specializations/medical_support/internships_list.php" class="spec-btn">عرض الفرص</a>
    </div>

  </div>
</section>

<?php include("../../includes/footer.php"); ?>

</body>
</html>