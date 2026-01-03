<footer class="site-footer">
  <p>© <?= date('Y') ?> متدرب | جميع الحقوق محفوظة.</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const KEY = 'showNameParts';
  const buttons = document.querySelectorAll('.js-toggle-name-parts');
  if (!buttons.length) return;

  // Restore user preference
  const isOn = localStorage.getItem(KEY) === '1';
  if (isOn) document.body.classList.add('show-name-parts');

  function syncButtons() {
    const on = document.body.classList.contains('show-name-parts');
    buttons.forEach(btn => {
      btn.textContent = on ? 'إخفاء الاسم الرباعي' : 'إظهار الاسم الرباعي';
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  }

  syncButtons();

  buttons.forEach(btn => {
    btn.addEventListener('click', function () {
      document.body.classList.toggle('show-name-parts');
      localStorage.setItem(KEY, document.body.classList.contains('show-name-parts') ? '1' : '0');
      syncButtons();
    });
  });
});
</script>

