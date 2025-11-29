<?php session_start(); ?>
<?php include("includes/header.php"); ?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>منصة متدرب | تدريب مهني لجميع التخصصات</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/lawyers.css">
</head>
<body>

<!-- ========= HERO ========= -->
<section class="hero">
  <h1>مرحبًا بك في منصة <span style="color:#ffd700;">متدرب</span></h1>
  <p>منصة للتدريب المهني تربط الخريجين بجهات التدريب المعتمدة في مختلف التخصصات.</p>
  <button onclick="window.location.href='choose_specialization.php'">ابدأ رحلتك المهنية</button>
</section>

<!-- ========= ABOUT ========= -->
<section class="about">
  <h2>من نحن</h2>
  <p>
    منصة متدرب هي مشروع يهدف لتهيئة الخريجين لسوق العمل عن طريق برامج تدريب ميداني
    مع جهات معتمدة في مختلف القطاعات المهنية.
  </p>
</section>

<!-- ========= SPECIALIZATIONS ========= -->
<section class="specializations">
  <h2>التخصصات المتاحة</h2>

  <div class="cards">
      
    <div class="card">
        <h3>⚖️ المحاماة</h3>
        <p>تدريب مهني تحت إشراف محامين مزاولين معتمدين.</p>
        <a href="specializations/lawyers/index.php" class="btn-card">دخول القسم</a>
    </div>

    <div class="card">
        <h3>🛠️ الهندسة</h3>
        <p>تدريب ميداني في المكاتب والمشاريع الهندسية.</p>
        <a href="engineering/index.php" class="btn-card">دخول القسم</a>
    </div>

    <div class="card">
        <h3>💊 الصيدلة</h3>
        <p>تدريب عملي في الصيدليات والمصانع الطبية.</p>
        <a href="pharmacy/index.php" class="btn-card">دخول القسم</a>
    </div>

    <div class="card">
        <h3>🩺 التمريض</h3>
        <p>تدريب مهني في المستشفيات والمراكز الطبية المعتمدة.</p>
        <a href="nursing/index.php" class="btn-card">دخول القسم</a>
    </div>

    <div class="card">
        <h3>💻 تكنولوجيا المعلومات</h3>
        <p>تدريب برمجة، شبكات، دعم فني وتطوير أنظمة.</p>
        <a href="it/index.php" class="btn-card">دخول القسم</a>
    </div>

  </div>
</section>

<!-- ========= SERVICES ========= -->
<section class="services">
  <h2>خدماتنا</h2>

  <div class="cards">

      <div class="card">
          <h3>ربط مباشر</h3>
          <p>نوصل المتدربين مباشرة بجهات التدريب المعتمدة.</p>
      </div>

      <div class="card">
          <h3>إشراف وتقييم</h3>
          <p>متابعة الأداء وتقييم التدريب رقمياً.</p>
      </div>

      <div class="card">
          <h3>شهادات خبرة</h3>
          <p>استخراج شهادة تدريب رسمية بعد إتمام البرنامج.</p>
      </div>

      <div class="card">
          <h3>فرص توظيف</h3>
          <p>مساعدة الخريجين للحصول على فرص عمل.</p>
      </div>

  </div>
</section>

<?php include("includes/footer.php"); ?>

</body>
</html>
