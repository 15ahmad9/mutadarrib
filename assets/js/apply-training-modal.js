(function () {
  const HINTS = [
    'لا يمكنك', 'عذراً', 'عذرًا', 'خطأ', 'مرفوض', 'تم رفض', 'غير متاح', 'لا يوجد', 'تحذير',
    'تم', 'نجاح', 'تم إرسال', 'تم تقديم', 'تمت', 'مقبول', 'قيد الانتظار',
    'pending', 'accepted', 'rejected',
    '⚠', '❌', '✅'
  ];

  const ERR_HINTS = ['لا يمكنك', 'عذراً', 'عذرًا', 'خطأ', 'مرفوض', 'تم رفض', 'غير متاح', '❌'];
  const OK_HINTS = ['نجاح', 'تم إرسال', 'تم تقديم', 'تمت', '✅'];

  function isApplyLink(href) {
    if (!href) return false;
    return href.includes('apply_training.php') && href.includes('training_id=');
  }

  function stripHtml(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html || '';
    return (tmp.textContent || tmp.innerText || '').replace(/\s+\n/g, '\n').trim();
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function linesFrom(text) {
    return (text || '')
      .split(/\n+/)
      .map((l) => l.trim())
      .filter(Boolean);
  }

  function isLikelyMessagePage(text, html) {
    const t = (text || '').trim();
    if (!t) return false;

    // وجود أي hint قوي
    if (HINTS.some((h) => t.includes(h))) return true;

    // كثير من صفحات apply_training تكون قصيرة وفيها كلمات معروفة
    if (t.length < 1400 && (t.includes('حالة') || t.includes('طلب') || t.includes('عنوان') || t.includes('مكتب'))) {
      return true;
    }

    // حاول نكتشف وجود alert-like markup
    try {
      const doc = new DOMParser().parseFromString(html || '', 'text/html');
      const alert = doc.querySelector('.alert, .error, .warning, .msg, .message');
      if (alert && (alert.textContent || '').trim().length > 0) return true;
    } catch (_) {}

    return false;
  }

  function detectType(text) {
    const t = (text || '').trim();
    if (ERR_HINTS.some((h) => t.includes(h))) return 'danger';
    if (OK_HINTS.some((h) => t.includes(h))) return 'success';
    if (t.includes('accepted') || t.includes('مقبول')) return 'success';
    if (t.includes('pending') || t.includes('قيد الانتظار')) return 'info';
    return 'info';
  }

  function buildInfo(text) {
    const t = (text || '').trim();
    const type = detectType(t);
    const rawLines = linesFrom(t);

    let title = 'تنبيه';
    let subtitle = '';
    let icon = 'ℹ️';

    if (type == 'danger') {
      title = 'لا يمكن إكمال العملية';
      subtitle = 'راجع تفاصيل الرسالة بالأسفل.';
      icon = '⚠️';
    } else if (type == 'success') {
      title = 'تمت العملية بنجاح';
      subtitle = 'تم تنفيذ طلبك.';
      icon = '✅';
    } else {
      title = 'معلومة';
      subtitle = 'تفاصيل إضافية.';
      icon = 'ℹ️';
    }

    // لو في عبارات محددة نغيّر العنوان
    if (t.includes('لا يمكنك التقديم على تدريب جديد الآن')) {
      title = 'لا يمكن التقديم الآن';
      subtitle = 'لديك طلب تدريب قائم.';
      icon = '⚠️';
    }
    if (t.includes('لقد قمت بالتقديم')) {
      title = 'تم التقديم مسبقًا';
      subtitle = 'طلبك موجود بالفعل.';
      icon = '⚠️';
    }

    // تفاصيل منظمة
    const details = [];

    const officeLine = rawLines.find((l) => l.includes('لديك طلب قائم لدى مكتب'));
    const trainingLine = rawLines.find((l) => l.includes('بعنوان تدريب'));
    const statusLine = rawLines.find((l) => l.includes('حالة الطلب'));

    if (officeLine) {
      details.push({ k: 'المكتب', v: officeLine.split(':').slice(1).join(':').trim() || officeLine });
    }
    if (trainingLine) {
      details.push({ k: 'التدريب', v: trainingLine.split(':').slice(1).join(':').trim() || trainingLine });
    }
    if (statusLine) {
      details.push({ k: 'الحالة', v: statusLine.split(':').slice(1).join(':').trim() || statusLine });
    }

    // fallback: التقط أي سطور فيها ':' (بحد أقصى 6)
    if (details.length == 0) {
      for (const l of rawLines) {
        if (l.includes(':')) {
          const parts = l.split(':');
          const k = parts[0].trim();
          const v = parts.slice(1).join(':').trim();
          if (k && v) details.push({ k, v });
        }
        if (details.length >= 6) break;
      }
    }

    // وصف مختصر
    let desc = rawLines.slice(0, 3).join(' — ');
    if (desc.length > 220) desc = desc.slice(0, 220) + '...';

    return { type, title, subtitle, icon, desc, details, rawLines };
  }

  function ensureModal() {
    let overlay = document.querySelector('.ui-modal-overlay');
    if (overlay) return overlay;

    overlay = document.createElement('div');
    overlay.className = 'ui-modal-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');

    overlay.innerHTML = `
      <div class="ui-modal" dir="rtl">
        <div class="ui-modal__top">
          <button class="ui-modal__close" type="button" aria-label="إغلاق">×</button>
          <div class="ui-modal__titleRow">
            <div class="ui-modal__icon" id="uiModalIcon" aria-hidden="true">⚠️</div>
            <div>
              <div class="ui-modal__title" id="uiModalTitle">...</div>
              <div class="ui-modal__subtitle" id="uiModalSub">...</div>
            </div>
          </div>
        </div>
        <div class="ui-modal__body">
          <div class="ui-modal__loading" id="uiModalLoading"></div>
          <div class="ui-modal__content" id="uiModalContent" style="display:none">
            <div class="ui-modal__subtitle" id="uiModalDesc" style="margin:0 0 10px"></div>
            <ul class="ui-modal__details" id="uiModalDetails"></ul>
          </div>
        </div>
        <div class="ui-modal__footer">
          <button type="button" class="ui-modal__btn ui-modal__btn--ghost" data-ui-close>إغلاق</button>
          <a class="ui-modal__btn ui-modal__btn--primary" href="/mutadarrib/trainee/training_progress.php">مدة التدريب</a>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);

    function close() {
      overlay.classList.remove('is-open');
      document.body.style.overflow = '';

      // لو كنا على apply_training.php مباشرة (fallback)، رجّع المستخدم
      if ((window.location.pathname || '').includes('apply_training.php')) {
        try {
          if (document.referrer) {
            window.location.href = document.referrer;
          } else {
            window.history.back();
          }
        } catch (_) {}
      }
    }

    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) close();
    });
    overlay.querySelector('.ui-modal__close')?.addEventListener('click', close);
    overlay.querySelector('[data-ui-close]')?.addEventListener('click', close);

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && overlay.classList.contains('is-open')) close();
    });

    return overlay;
  }

  function forceModalEnterAnim(overlay) {
    const modal = overlay?.querySelector('.ui-modal');
    if (!modal) return;
    modal.style.animation = 'none';
    void modal.offsetHeight; // reflow
    modal.style.animation = '';
  }

  function openLoading() {
    const overlay = ensureModal();
    overlay.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    forceModalEnterAnim(overlay);

    overlay.querySelector('#uiModalTitle').textContent = 'جاري التحقق...';
    overlay.querySelector('#uiModalSub').textContent = 'ثواني وبنرجع لك بالنتيجة.';
    overlay.querySelector('#uiModalIcon').textContent = '⏳';
    overlay.querySelector('#uiModalLoading').style.display = '';
    overlay.querySelector('#uiModalContent').style.display = 'none';
    return overlay;
  }

  function showMessage(info) {
    const overlay = ensureModal();
    overlay.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    forceModalEnterAnim(overlay);

    overlay.querySelector('#uiModalTitle').textContent = info.title;
    overlay.querySelector('#uiModalSub').textContent = info.subtitle || '';
    overlay.querySelector('#uiModalIcon').textContent = info.icon || 'ℹ️';

    overlay.querySelector('#uiModalLoading').style.display = 'none';
    overlay.querySelector('#uiModalContent').style.display = '';

    const desc = overlay.querySelector('#uiModalDesc');
    desc.textContent = info.desc || '';

    const list = overlay.querySelector('#uiModalDetails');
    list.innerHTML = '';

    const items = (info.details && info.details.length) ? info.details : info.rawLines.slice(0, 6).map((l) => ({ k: '•', v: l }));

    items.forEach((it, idx) => {
      const li = document.createElement('li');
      li.style.animationDelay = (idx * 70) + 'ms';
      li.innerHTML = `<span>${escapeHtml(it.k)}:</span> <strong>${escapeHtml(it.v)}</strong>`;
      list.appendChild(li);
    });

    const modal = overlay.querySelector('.ui-modal');
    modal?.classList.remove('ui-modal__shake');
    if (info.type === 'danger') {
      requestAnimationFrame(() => modal?.classList.add('ui-modal__shake'));
    }
  }

  async function checkAndHandle(href) {
    const overlay = openLoading();
    try {
      const res = await fetch(href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const html = await res.text();
      const text = stripHtml(html);

      if (isLikelyMessagePage(text, html)) {
        showMessage(buildInfo(text));
        return { handled: true };
      }

      // مش رسالة -> سكّر المودال وكمّل تنقّل طبيعي
      overlay.classList.remove('is-open');
      document.body.style.overflow = '';
      return { handled: false };
    } catch (e) {
      overlay.classList.remove('is-open');
      document.body.style.overflow = '';
      return { handled: false, error: true };
    }
  }

  // Intercept clicks
  document.addEventListener('click', async (e) => {
    const a = e.target.closest('a');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!isApplyLink(href)) return;

    // لا نعطّل فتح تبويب جديد
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;

    e.preventDefault();
    const res = await checkAndHandle(href);
    if (!res.handled) window.location.href = href;
  }, true);

  // Fallback: لو المستخدم وصل مباشرة لصفحة apply_training.php (بدون intercept)
  document.addEventListener('DOMContentLoaded', () => {
    try {
      const path = window.location.pathname || '';
      if (!path.includes('apply_training.php')) return;
      const text = (document.body?.innerText || '').trim();
      if (!text) return;
      if (isLikelyMessagePage(text, document.documentElement?.outerHTML || '')) {
        document.body.innerHTML = '';
        showMessage(buildInfo(text));
      }
    } catch (_) {}
  });
})();
