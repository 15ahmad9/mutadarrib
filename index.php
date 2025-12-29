<?php
require_once __DIR__ . '/includes/theme_init.php';
 session_start(); ?>
<?php include("includes/header.php"); ?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>منصة متدرب | تدريب مهني لجميع التخصصات</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/lawyers.css">
</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<!-- ========= HERO ========= -->
<section class="hero hero--fullscreen">
  <div class="hero-content">
    <h1>مرحبًا بك في منصة <span style="color:#ffd700;">متدرب</span></h1>
    <p>منصة للتدريب المهني تربط المتدربين بجهات التدريب المعتمدة في مختلف التخصصات.</p>
    <button class="hero-cta" onclick="window.location.href='/mutadarrib/specializations/lawyers/index.php'">ابدأ رحلتك المهنية</button>
  </div>

  <!-- Animated separator to create a smooth transition to the next section -->
  <div class="hero-sep" aria-hidden="true">
    <svg viewBox="0 0 1440 120" preserveAspectRatio="none" focusable="false">
      <path d="M0,72 C240,116 480,28 720,72 C960,116 1200,28 1440,72 L1440,120 L0,120 Z" fill="rgba(255,255,255,0.55)"></path>
      <path d="M0,84 C240,126 480,46 720,84 C960,126 1200,46 1440,84 L1440,120 L0,120 Z" fill="#ffffff"></path>
    </svg>
  </div>
</section>

<!-- ========= ABOUT ========= -->

<section class="about about-slider reveal" id="about" data-reveal="up">
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
<section class="specializations reveal" data-reveal="up">
  <h2>التخصصات المتاحة</h2>

  <div class="cards" data-stagger>
      
    <a class="card card-link reveal-item" href="specializations/lawyers/index.php">
        <h3>⚖️ المحاماة</h3>
        <p>تدريب مهني تحت إشراف محامين مزاولين معتمدين.</p>
        <span class="btn-card">دخول القسم</span>
    </a>

    <a class="card card-link reveal-item" href="/mutadarrib/soon.php">
        <h3>🛠️ الهندسة</h3>
        <p>تدريب ميداني في المكاتب والمشاريع الهندسية.</p>
        <span class="btn-card">دخول القسم</span>
    </a>

    <a class="card card-link reveal-item" href="/mutadarrib/soon.php">
        <h3>💊 الصيدلة</h3>
        <p>تدريب عملي في الصيدليات والمصانع الطبية.</p>
        <span class="btn-card">دخول القسم</span>
    </a>

    <a class="card card-link reveal-item" href="/mutadarrib/soon.php">
        <h3>🩺 التمريض</h3>
        <p>تدريب مهني في المستشفيات والمراكز الطبية المعتمدة.</p>
        <span class="btn-card">دخول القسم</span>
    </a>

    <a class="card card-link reveal-item" href="/mutadarrib/soon.php">
        <h3>💻 تكنولوجيا المعلومات</h3>
        <p>تدريب برمجة، شبكات، دعم فني وتطوير أنظمة.</p>
        <span class="btn-card">دخول القسم</span>
    </a>

  </div>
</section>

<!-- ========= SERVICES ========= -->
<section id="services" class="services reveal" data-reveal="up">
  <h2>خدماتنا</h2>

  <div class="cards" data-stagger>

      <div class="card reveal-item">
          <h3>ربط مباشر</h3>
          <p>نوصل المتدربين مباشرة بجهات التدريب المعتمدة.</p>
      </div>

      <div class="card reveal-item">
          <h3>إشراف وتقييم</h3>
          <p>متابعة الأداء وتقييم التدريب رقمياً.</p>
      </div>

      <div class="card reveal-item">
          <h3>شهادات خبرة</h3>
          <p>استخراج شهادة تدريب رسمية بعد إتمام البرنامج.</p>
      </div>

      <div class="card reveal-item">
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

  const slides   = Array.from(carousel.querySelectorAll(".about-slide"));
  const dots     = Array.from(carousel.querySelectorAll(".dot"));
  const btnPrev  = carousel.querySelector(".about-nav.prev");
  const btnNext  = carousel.querySelector(".about-nav.next");
  const viewport = carousel.querySelector(".about-viewport");

  const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  let index = 0;
  const total = slides.length;

  const autoplayEnabled = carousel.dataset.autoplay === "1" && !prefersReducedMotion;
  const intervalMs = Math.max(2500, parseInt(carousel.dataset.interval || "7000", 10));
  let timer = null;

  function updateDots() {
    dots.forEach((d, i) => d.classList.toggle("active", i === index));
  }

  function updateAria() {
    slides.forEach((s, i) => {
      s.setAttribute("aria-hidden", i === index ? "false" : "true");
      if (i === index) s.removeAttribute("tabindex");
      else s.setAttribute("tabindex", "-1");
    });
  }

  function fitViewport(i) {
    if (!viewport) return;
    const h = slides[i]?.offsetHeight || 0;
    if (h) viewport.style.height = h + "px";
  }

  function cleanClasses(el) {
    el.classList.remove(
      "is-entering",
      "is-leaving",
      "enter-next",
      "enter-prev",
      "leave-next",
      "leave-prev"
    );
  }

  function show(i, dir) {
    const nextIndex = (i + total) % total;
    if (nextIndex === index) return;

    const current = slides[index];
    const incoming = slides[nextIndex];

    // Ensure incoming is visible for animation
    cleanClasses(incoming);
    incoming.classList.add("is-entering", dir === "prev" ? "enter-prev" : "enter-next");

    // Trigger reflow so the animation reliably starts
    void incoming.offsetWidth;

    // Outgoing animation
    cleanClasses(current);
    current.classList.add("is-leaving", dir === "prev" ? "leave-prev" : "leave-next");
    current.classList.remove("is-active");

    // Incoming becomes active
    incoming.classList.add("is-active");

    // Update state
    index = nextIndex;
    updateDots();
    updateAria();

    // Smoothly adapt height
    requestAnimationFrame(() => fitViewport(index));

    // Cleanup after animations
    current.addEventListener(
      "animationend",
      () => {
        cleanClasses(current);
      },
      { once: true }
    );

    incoming.addEventListener(
      "animationend",
      () => {
        cleanClasses(incoming);
      },
      { once: true }
    );
  }

  function next() { show(index + 1, "next"); }
  function prev() { show(index - 1, "prev"); }

  function start() {
    if (!autoplayEnabled) return;
    stop();
    timer = setInterval(next, intervalMs);
  }

  function stop() {
    if (timer) clearInterval(timer);
    timer = null;
  }

  // Init (make first slide active)
  slides.forEach((s, i) => {
    cleanClasses(s);
    s.classList.toggle("is-active", i === 0);
  });
  updateDots();
  updateAria();
  fitViewport(0);

  // Buttons
  if (btnNext) btnNext.addEventListener("click", () => { next(); start(); });
  if (btnPrev) btnPrev.addEventListener("click", () => { prev(); start(); });

  // Dots
  dots.forEach((dot) => {
    dot.addEventListener("click", function () {
      const i = parseInt(dot.dataset.slide, 10);
      if (isNaN(i)) return;
      const dir = i < index ? "prev" : "next";
      show(i, dir);
      start();
    });
  });

  // Pause on hover / focus
  carousel.addEventListener("mouseenter", stop);
  carousel.addEventListener("mouseleave", start);
  carousel.addEventListener("focusin", stop);
  carousel.addEventListener("focusout", start);

  // Pause when tab not visible
  document.addEventListener("visibilitychange", function () {
    if (document.hidden) stop();
    else start();
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

  // Autoplay
  start();
});
</script>

<script src="assets/js/reveal-home.js"></script>

</html>
