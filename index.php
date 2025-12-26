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
  <p>منصة للتدريب المهني تربط المتدربين بجهات التدريب المعتمدة في مختلف التخصصات.</p>
  <button onclick="window.location.href='/mutadarrib/specializations/lawyers/index.php'">ابدأ رحلتك المهنية</button>
</section>

<!-- ========= ABOUT ========= -->

<section class="about about-slider" id="about">
  <h2>عن المنصة</h2>

  <div class="about-carousel" data-autoplay="1" data-interval="7000" aria-label="شرائح تعريفية عن منصة متدرب">
    <!-- أزرار التنقل -->
    <button class="about-nav prev" type="button" aria-label="الشريحة السابقة">›</button>
    <button class="about-nav next" type="button" aria-label="الشريحة التالية">‹</button>

    <!-- نافذة العرض -->
    <div class="about-viewport">
      <div class="about-track">
        <article class="about-slide">
          <h3>من نحن</h3>
          <p>
            منصة “متدرب” هي منصة رقمية للتدريب المهني تهدف إلى ربط الخريجين بجهات تدريب معتمدة في مختلف التخصصات،
            ضمن مسار واضح يبدأ بإنشاء الحساب وإكمال الملف الشخصي، ثم التقديم على فرص التدريب والمتابعة حتى إتمام البرنامج.
          </p>
          <p>
            نركز على تسهيل رحلة المتدرب عبر إدارة الطلبات إلكترونيًا، وتوفير إشعارات وتنبيهات بالمواعيد،
            وتتبع مدة التدريب والتقدم بشكل منظم. كما تدعم المنصة الجهات المشرفة عبر لوحات تحكم لإدارة البرامج والطلبات
            والتحقق من الوثائق، بما يعزز الشفافية ويرفع جودة التدريب ويقرب الخريجين من سوق العمل.
          </p>
        </article>

        <article class="about-slide">
          <h3>لماذا متدرب؟</h3>
          <ul class="about-list">
            <li>ربط بجهات معتمدة.</li>
            <li>متابعة وتقييم رقمي.</li>
            <li>تنبيهات بالمواعيد عبر التقويم.</li>
            <li>توثيق الجهات/المزاولين.</li>
            <li>إدارة طلبات التدريب والامتحان من لوحة النقابة/الإدارة.</li>
          </ul>
        </article>

        <article class="about-slide">
          <h3>رؤيتنا</h3>
          <p>
            نسعى لأن نكون المنصة الرائدة في مجال التدريب المهني، من خلال توفير تجربة متكاملة للمتدربين
            والجهات التدريبية، وتعزيز جودة التدريب وربط الخريجين بسوق العمل بفعالية.
          </p>
        </article>
      </div>
    </div>

    <!-- النقاط -->
    <div class="about-dots" role="tablist" aria-label="التنقل بين الشرائح">
      <button type="button" class="dot" data-slide="0" aria-label="الشريحة 1"></button>
      <button type="button" class="dot" data-slide="1" aria-label="الشريحة 2"></button>
      <button type="button" class="dot" data-slide="2" aria-label="الشريحة 3"></button>
    </div>
  </div>
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
<section id="services" class="services">
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

<script>
document.addEventListener("DOMContentLoaded", function () {
  const carousel = document.querySelector(".about-carousel");
  if (!carousel) return;

  const track  = carousel.querySelector(".about-track");
  const slides = Array.from(carousel.querySelectorAll(".about-slide"));
  const dots   = Array.from(carousel.querySelectorAll(".dot"));
  const btnPrev = carousel.querySelector(".about-nav.prev");
  const btnNext = carousel.querySelector(".about-nav.next");

  const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  let index = 0;
  const total = slides.length;

  const autoplayEnabled = carousel.dataset.autoplay === "1" && !prefersReducedMotion;
  const intervalMs = Math.max(2500, parseInt(carousel.dataset.interval || "7000", 10));
  let timer = null;

  function updateUI() {
    track.style.transform = `translateX(-${index * 100}%)`;

    dots.forEach((d, i) => d.classList.toggle("active", i === index));

    slides.forEach((s, i) => {
      s.setAttribute("aria-hidden", i === index ? "false" : "true");
      if (i === index) {
        s.removeAttribute("tabindex");
      } else {
        s.setAttribute("tabindex", "-1");
      }
    });
  }

  function goTo(newIndex) {
    index = (newIndex + total) % total;
    updateUI();
  }

  function next() { goTo(index + 1); }
  function prev() { goTo(index - 1); }

  function start() {
    if (!autoplayEnabled) return;
    stop();
    timer = setInterval(next, intervalMs);
  }

  function stop() {
    if (timer) clearInterval(timer);
    timer = null;
  }

  // Buttons
  if (btnNext) btnNext.addEventListener("click", function () { next(); start(); });
  if (btnPrev) btnPrev.addEventListener("click", function () { prev(); start(); });

  // Dots
  dots.forEach((dot) => {
    dot.addEventListener("click", function () {
      const i = parseInt(dot.dataset.slide, 10);
      if (!isNaN(i)) { goTo(i); start(); }
    });
  });

  // Pause on hover / focus
  carousel.addEventListener("mouseenter", stop);
  carousel.addEventListener("mouseleave", start);
  carousel.addEventListener("focusin", stop);
  carousel.addEventListener("focusout", start);

  // Pause when tab not visible
  document.addEventListener("visibilitychange", function () {
    if (document.hidden) stop(); else start();
  });

  // Keyboard navigation (مراعاة RTL)
  carousel.setAttribute("tabindex", "0");
  carousel.addEventListener("keydown", function (e) {
    const isRTL = (document.documentElement.getAttribute("dir") || "").toLowerCase() === "rtl";

    if (e.key === "ArrowLeft") {
      e.preventDefault();
      isRTL ? next() : prev();
      start();
    } else if (e.key === "ArrowRight") {
      e.preventDefault();
      isRTL ? prev() : next();
      start();
    }
  });

  // Init
  updateUI();
  start();
});
</script>


</html>
