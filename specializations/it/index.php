<?php
require_once __DIR__ . '/../../includes/theme_init.php';
  include("../../includes/header.php");

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>قسم تكنولوجيا المعلومات</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body data-theme="<?= htmlspecialchars($theme) ?>">

<main class="landing-shell">
  <section class="landing-hero">
    <h1 class="landing-title">قسم تكنولوجيا المعلومات 💻</h1>
    <p class="landing-subtitle">
      بوابتك إلى فرص التدريب التقني المعتمدة وربط المتدربين بالشركات ومزوّدي التدريب بسهولة.
    </p>
  </section>

  <section class="landing-cards">
    <!-- Card 1: IT Trainee -->
    <div class="landing-card">
      <div class="landing-card-top">
        <span class="landing-pill">التسجيل كمتدرب IT</span>
        <span class="landing-icon">🎓</span>
      </div>

      <h3 class="landing-card-title">ابدأ مسيرتك التقنية</h3>
      <p class="landing-card-text">
        سجّل كمتدرب IT، أضف مهاراتك وروابطك، وتقدّم لفرص التدريب المتاحة وتابع حالة طلباتك.
      </p>

      <a class="landing-btn landing-btn-primary" href="../../auth/choose_specialization.php">
        تسجيل متدرب IT
      </a>
    </div>

    <!-- Card 2: IT Provider -->
    <div class="landing-card">
      <div class="landing-card-top">
        <span class="landing-pill">التسجيل كمزوّد IT</span>
        <span class="landing-icon">🏢</span>
      </div>

      <h3 class="landing-card-title">هل ترغب باستقبال متدربين؟</h3>
      <p class="landing-card-text">
        سجّل كشركة/مزوّد تدريب، أنشئ ملف شركتك، وانشر فرص تدريب جديدة واستقبل الطلبات بسهولة.
      </p>

      <a class="landing-btn landing-btn-outline" href="../../auth/register_it.php">
        تسجيل مزوّد IT
      </a>
    </div>

    <!-- Card 3: Browse internships -->
    <div class="landing-card">
      <div class="landing-card-top">
        <span class="landing-pill">فرص التدريب التقنية</span>
        <span class="landing-icon">📋</span>
      </div>

      <h3 class="landing-card-title">استعرض فرص التدريب</h3>
      <p class="landing-card-text">
        تصفّح فرص التدريب المتاحة حسب المجال والمدينة ونوع التدريب (عن بُعد/حضوري) وتفاصيل كل فرصة.
      </p>

      <a class="landing-btn landing-btn-primary" href="../it/it_internships_list.php">
        عرض الفرص
      </a>
    </div>
  </section>
</main>

<style>
/* إذا style.css عندك فيه ستايل مشابه لصفحة المحامين، احذف هذا الـ style بالكامل.
   هذا فقط fallback ليطلع شكل قريب من الصورة. */

.landing-shell{
  padding: 40px 16px 70px;
  min-height: 70vh;
}
.landing-hero{
  text-align:center;
  margin: -20px auto 5px;
  max-width: 980px;
}
.landing-title{
  font-size: 34px;
  margin: 0 0 10px;
  color: #0b0f5c;
}
.landing-subtitle{
  font-size: 18px;
  margin: 0;
  color: #5b5f85;
}
.landing-cards{
  max-width: 1150px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 22px;
}
@media(max-width: 980px){
  .landing-cards{ grid-template-columns: 1fr; }
  .landing-title{ font-size: 34px; }
}

.landing-card{
  background: #fff;
  border-radius: 18px;
  padding: 16px;
  box-shadow: 0 12px 30px rgba(0,0,0,.08);
  border: 1px solid rgba(15, 23, 42, .06);
  position: relative;
  overflow: hidden;
}
.landing-card:before{
  content:"";
  position:absolute;
  left:-80px; top:-80px;
  width: 220px; height: 220px;
  border-radius: 50%;
  background: rgba(78, 90, 229, .10);
}
.landing-card-top{
  display:flex;
  justify-content: space-between;
  align-items:center;
  margin-bottom: 12px;
  position: relative;
  z-index: 1;
}
.landing-pill{
  display:inline-block;
  padding: 8px 14px;
  border-radius: 999px;
  background: #f4f6ff;
  color:#1b2a7a;
  font-weight: 700;
  font-size: 14px;
}
.landing-icon{
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:#eef1ff;
  font-size: 18px;
  position: relative;
  z-index: 1;
}
.landing-card-title{
  margin: 10px 0 10px;
  font-size: 18px;
  color:#0b0f5c;
  position: relative;
  z-index: 1;
}
.landing-card-text{
  margin: 0 0 18px;
  color:#5b5f85;
  line-height: 1.8;
  position: relative;
  z-index: 1;
}

.landing-btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding: 12px 18px;
  border-radius: 14px;
  text-decoration:none;
  font-weight: 800;
  position: relative;
  z-index: 1;
}
.landing-btn-primary{
  background:#4154d0;
  color:#fff;
}
.landing-btn-outline{
  background:#fff;
  color:#4154d0;
  border: 2px solid #4154d0;
}
</style>

<?php include("../../includes/footer.php"); ?>
</body>
</html>
