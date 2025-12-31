<?php
require_once __DIR__ . '/../../includes/theme_init.php';
require_once("../../config/db.php");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>مكاتب المحامين | منصة متدرب</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="../../assets/css/lawyers.css">
</head>

<body data-theme="<?= htmlspecialchars($theme) ?>">
<?php include("../../includes/header.php"); ?>

<main class="lawyers-page">
  <header class="page-hero">
    <h1 class="page-title">مكاتب المحامين المعتمدين</h1>
    <p class="page-subtitle">اختر مكتب التدريب المناسب لك وابدأ رحلتك المهنية</p>
  </header>

  <!-- ================= Filters ================== -->
  <section class="filters-card" aria-label="خيارات البحث والتصفية">
    <div class="filters-row">
      <div class="input-icon">
        <span class="icon" aria-hidden="true">
          <!-- search -->
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
            <path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2"/>
            <path d="M16.5 16.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </span>
        <input id="lawyerSearch" type="text" placeholder="ابحث باسم المحامي، المدينة، أو البريد..." autocomplete="off">
      </div>

      <div class="select-wrap">
        <span class="icon" aria-hidden="true">
          <!-- filter -->
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
            <path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </span>
        <select id="statusFilter" aria-label="تصفية حسب حالة القبول">
          <option value="all">الكل</option>
          <option value="open">يقبل متدربين</option>
          <option value="closed">مغلق</option>
        </select>
      </div>
    </div>

    <div class="filters-hint" id="resultHint" aria-live="polite"></div>
  </section>

  <!-- ================= Lawyers Grid ================= -->
  <section class="lawyers-grid" id="lawyersGrid" aria-label="قائمة مكاتب المحامين">
    <?php
      $q = $pdo->query("
        SELECT 
          l.*, 
          u.full_name 
        FROM lawyers l
        LEFT JOIN users u ON u.user_id = l.user_id
        WHERE l.verified = 1
      ");

      while ($lawyer = $q->fetch(PDO::FETCH_ASSOC)):
        $isOpen = (int)($lawyer['verified'] ?? 0) === 1;
        $status = $isOpen ? 'يقبل متدربين' : 'مغلق';
        $statusKey = $isOpen ? 'open' : 'closed';

        $fullName = trim((string)($lawyer['full_name'] ?? ''));
        $office   = trim((string)($lawyer['office_address'] ?? ''));
        $phone    = trim((string)($lawyer['phone'] ?? ''));
        $email    = trim((string)($lawyer['email'] ?? ''));

        // Used for client-side searching (front-end only).
        $searchBlob = $fullName . ' ' . $office . ' ' . $phone . ' ' . $email;
        $profileHref = 'lawyer_profile.php?id=' . urlencode((string)$lawyer['lawyer_id']);
    ?>
      <article
        class="lawyer-card <?= $isOpen ? '' : 'is-disabled' ?>"
        data-status="<?= htmlspecialchars($statusKey) ?>"
        data-search="<?= htmlspecialchars($searchBlob) ?>"
        <?= $isOpen ? 'data-href="' . htmlspecialchars($profileHref) . '"' : '' ?>
      >
        <div class="card-top">
          <div class="avatar" aria-hidden="true">
            <!-- scale -->
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none">
              <path d="M12 3v18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M4 7h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M7 7 5 12a4 4 0 0 0 8 0L11 7" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
              <path d="M17 7 15 12a4 4 0 0 0 8 0L21 7" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
              <path d="M8 21h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </div>

          <div class="title-stack">
            <h3 class="lawyer-name"><?= htmlspecialchars($fullName) ?></h3>
            <span class="pill <?= $statusKey === 'open' ? 'pill-open' : 'pill-closed' ?>">
              <?= htmlspecialchars($status) ?>
            </span>
          </div>
        </div>

        <div class="meta">
          <div class="meta-row">
            <span class="meta-icon" aria-hidden="true">
              <!-- map pin -->
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                <path d="M12 22s7-4.5 7-12a7 7 0 1 0-14 0c0 7.5 7 12 7 12Z" stroke="currentColor" stroke-width="2"/>
                <path d="M12 10.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="2"/>
              </svg>
            </span>
            <span class="meta-label">المكتب:</span>
            <span class="meta-value"><?= htmlspecialchars($office ?: '—') ?></span>
          </div>

          <div class="meta-row">
            <span class="meta-icon" aria-hidden="true">
              <!-- phone -->
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                <path d="M22 16.9v2a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 3.1 4.2 2 2 0 0 1 5.1 2h2a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.6a2 2 0 0 1-.5 2.1L8.4 9.2a16 16 0 0 0 6.4 6.4l.8-.8a2 2 0 0 1 2.1-.5c.8.3 1.7.5 2.6.6a2 2 0 0 1 1.7 2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
              </svg>
            </span>
            <span class="meta-label">الهاتف:</span>
            <span class="meta-value ltr"><?= htmlspecialchars($phone ?: '—') ?></span>
          </div>

          <div class="meta-row">
            <span class="meta-icon" aria-hidden="true">
              <!-- mail -->
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                <path d="M4 6h16v12H4V6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
              </svg>
            </span>
            <span class="meta-label">البريد:</span>
            <span class="meta-value ltr"><?= htmlspecialchars($email ?: '—') ?></span>
          </div>
        </div>

        <div class="card-actions">
          <?php if ($isOpen): ?>
            <a href="<?= htmlspecialchars($profileHref) ?>" class="btn btn-sm">عرض تفاصيل المكتب</a>
          <?php else: ?>
            <span class="btn btn-sm secondary" aria-disabled="true">لا يقبل حالياً</span>
          <?php endif; ?>
        </div>
      </article>
    <?php endwhile; ?>
  </section>
</main>

<?php include("../../includes/footer.php"); ?>

<script>
  (function () {
    const searchInput = document.getElementById('lawyerSearch');
    const statusSelect = document.getElementById('statusFilter');
    const grid = document.getElementById('lawyersGrid');
    const hint = document.getElementById('resultHint');

    if (!searchInput || !statusSelect || !grid) return;

    const cards = Array.from(grid.querySelectorAll('.lawyer-card'));

    function normalize(s) {
      return (s || '').toString().trim().toLowerCase();
    }

    function applyFilter() {
      const q = normalize(searchInput.value);
      const st = statusSelect.value;

      let visible = 0;

      cards.forEach(card => {
        const blob = normalize(card.getAttribute('data-search'));
        const status = card.getAttribute('data-status') || 'open';

        const matchesText = !q || blob.includes(q);
        const matchesStatus = (st === 'all') || (status === st);

        const show = matchesText && matchesStatus;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
      });

      if (hint) {
        hint.textContent = visible ? ('عدد النتائج: ' + visible) : 'لا توجد نتائج مطابقة.';
      }
    }

    // Lightweight debounce for typing
    let t = null;
    searchInput.addEventListener('input', () => {
      clearTimeout(t);
      t = setTimeout(applyFilter, 120);
    });
    statusSelect.addEventListener('change', applyFilter);

    // Make card clickable (same as button) – front-end only.
    cards.forEach(card => {
      const href = card.getAttribute('data-href');
      if (!href) return;

      card.addEventListener('click', (e) => {
        const a = e.target.closest('a');
        if (a) return; // respect normal link clicks
        window.location.href = href;
      });
    });

    // Initial count
    applyFilter();
  })();
</script>

</body>
</html>
