<?php
require_once __DIR__ . '/../includes/theme_init.php';

session_start();
require_once("../config/db.php");

// أي مستخدم مسجل دخول يمكنه فتح التقويم (كل الأدوار)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>التقويم | المهام والتذكير</title>

  <!-- FullCalendar (CDN) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

  <link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="calendar-page" data-theme="<?= htmlspecialchars($theme) ?>">

<?php include("../includes/header.php"); ?>

<div class="page-wrap calendar-wrap">
  <div class="topbar">
    <h2>التقويم (المهام والتذكير)</h2>
    <div class="hint">اضغط على يوم/اسحب فترة لإضافة مهمة. اضغط على مهمة للتعديل أو الحذف.</div>
  </div>

  <div id="calendar"></div>
</div>

<!-- Modal -->
<div class="modal-backdrop" id="modalBackdrop">
  <div class="modal">
    <h3 id="modalTitle">مهمة جديدة</h3>

    <input type="hidden" id="event_id" value="">

    <label>العنوان</label>
    <input type="text" id="title" maxlength="200" placeholder="مثال: مراجعة ملف التدريب" />

    <label>الوصف</label>
    <textarea id="description" placeholder="تفاصيل المهمة..."></textarea>

    <div class="row">
      <div>
        <label>نوع الإدخال</label>
        <select id="type">
          <option value="task">مهمة</option>
          <option value="event">حدث</option>
        </select>
      </div>
      <div>
        <label>تذكير قبل الموعد</label>
        <select id="reminder_minutes">
          <option value="">بدون</option>
          <!-- <option value="1">قبل 1 دقائق</option> -->
          <!-- <option value="10">قبل 10 دقائق</option> -->
          <option value="30">قبل 30 دقيقة</option>
          <option value="60">قبل ساعة</option>
          <option value="570">قبل 12 ساعة</option>
          <option value="1440">قبل يوم</option>
        </select>
        <div class="hint">التذكير داخل التطبيق (يمكن لاحقًا إضافة بريد/رسائل).</div>
      </div>
    </div>

    <label>
      <input type="checkbox" id="all_day" />
      طوال اليوم
    </label>

    <div class="row">
      <div>
        <label>بداية</label>
        <input type="datetime-local" id="start_at" />
      </div>
      <div>
        <label>نهاية</label>
        <input type="datetime-local" id="end_at" />
      </div>
    </div>

    <div class="actions">
      <button class="btn btn-primary" id="saveBtn">حفظ</button>
      <button class="btn btn-danger" id="deleteBtn" style="display:none;">حذف</button>
      <button class="btn btn-muted" id="closeBtn">إغلاق</button>
    </div>
  </div>
</div>

<script>
  const modalBackdrop = document.getElementById('modalBackdrop');
  const modalTitle = document.getElementById('modalTitle');

  const elEventId = document.getElementById('event_id');
  const elTitle = document.getElementById('title');
  const elDescription = document.getElementById('description');
  const elType = document.getElementById('type');
  const elReminder = document.getElementById('reminder_minutes');
  const elAllDay = document.getElementById('all_day');
  const elStart = document.getElementById('start_at');
  const elEnd = document.getElementById('end_at');

  const btnSave = document.getElementById('saveBtn');
  const btnDelete = document.getElementById('deleteBtn');
  const btnClose = document.getElementById('closeBtn');

  function openModal(mode) {
  // show with animation
  modalBackdrop.style.display = 'flex';
  // force reflow then add class
  requestAnimationFrame(() => {
    modalBackdrop.classList.add('open');
  });

  if (mode === 'edit') {
    modalTitle.textContent = 'تعديل مهمة';
    btnDelete.style.display = 'inline-block';
  } else {
    modalTitle.textContent = 'مهمة جديدة';
    btnDelete.style.display = 'none';
  }
}

function closeModal() {
  // hide with animation
  modalBackdrop.classList.remove('open');
  // wait for transition end
  setTimeout(() => {
    if (!modalBackdrop.classList.contains('open')) {
      modalBackdrop.style.display = 'none';
    }
  }, 220);
}

  function toLocalInputValue(dateObj) {
    // YYYY-MM-DDTHH:mm
    const pad = n => (n < 10 ? '0' + n : n);
    const y = dateObj.getFullYear();
    const m = pad(dateObj.getMonth() + 1);
    const d = pad(dateObj.getDate());
    const hh = pad(dateObj.getHours());
    const mm = pad(dateObj.getMinutes());
    return `${y}-${m}-${d}T${hh}:${mm}`;
  }

  function clearForm() {
    elEventId.value = '';
    elTitle.value = '';
    elDescription.value = '';
    elType.value = 'task';
    elReminder.value = '';
    elAllDay.checked = false;
    elStart.value = '';
    elEnd.value = '';
  }

  function setAllDayUI(allDay) {
    elAllDay.checked = !!allDay;
    // إذا طوال اليوم: نخلي المدخل datetime-local، لكن المستخدم غالبًا يضع وقت 00:00
    // بإمكانك لاحقًا تحويله لـ date فقط إن رغبت.
  }

  async function api(url, method = 'GET', body = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(url, opts);
    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Request failed');
    }
    return data;
  }

  document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      locale: 'ar',
      direction: 'rtl',
      selectable: true,
      editable: false,
      height: 'auto',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
      },


// Animations & polish
loading: function(isLoading) {
  calendarEl.classList.toggle('is-loading', !!isLoading);
},

datesSet: function() {
  // view transition (month/week/day)
  const harness = calendarEl.querySelector('.fc-view-harness');
  if (!harness) return;
  harness.classList.remove('fc-animate-in');
  // reflow then animate in
  void harness.offsetWidth;
  harness.classList.add('fc-animate-in');
},

eventDidMount: function(info) {
  // animate events popping in
  info.el.classList.add('fc-event-pop');
  // small hover hint
  info.el.setAttribute('title', info.event.title || '');
},

      events: async function(fetchInfo, successCallback, failureCallback) {
        try {
          const params = new URLSearchParams({
            start: fetchInfo.startStr,
            end: fetchInfo.endStr
          });
          const res = await fetch(`api/events.php?${params.toString()}`);
          const data = await res.json();
          if (!data.success) throw new Error(data.message || 'Load failed');
          successCallback(data.events);
        } catch (e) {
          failureCallback(e);
        }
      },

      select: function(info) {
        clearForm();
        setAllDayUI(info.allDay);

        // FullCalendar يعطينا start/end. نضبطها
        const start = new Date(info.start);
        const end = info.end ? new Date(info.end) : null;

        elStart.value = toLocalInputValue(start);
        if (end) elEnd.value = toLocalInputValue(end);

        openModal('create');
      },

      eventClick: async function(info) {
        try {
          clearForm();
          const id = info.event.id;

          const data = await api(`api/get.php?id=${encodeURIComponent(id)}`, 'GET');
          const ev = data.event;

          elEventId.value = ev.event_id;
          elTitle.value = ev.title || '';
          elDescription.value = ev.description || '';
          elType.value = ev.type || 'task';
          elReminder.value = (ev.reminder_minutes ?? '') + '';
          setAllDayUI(ev.all_day == 1);

          // datetime-local values
          elStart.value = (ev.start_at_local || '');
          elEnd.value = (ev.end_at_local || '');

          openModal('edit');
        } catch (e) {
          alert('تعذر تحميل بيانات المهمة: ' + e.message);
        }
      }
    });

    calendar.render();

    btnClose.addEventListener('click', closeModal);

    btnSave.addEventListener('click', async function() {
      try {
        const payload = {
          event_id: elEventId.value ? parseInt(elEventId.value) : null,
          title: elTitle.value.trim(),
          description: elDescription.value.trim(),
          type: elType.value,
          reminder_minutes: elReminder.value ? parseInt(elReminder.value) : null,
          all_day: elAllDay.checked ? 1 : 0,
          start_at: elStart.value,
          end_at: elEnd.value || null
        };

        if (!payload.title) {
          alert('العنوان مطلوب.');
          return;
        }
        if (!payload.start_at) {
          alert('تاريخ البداية مطلوب.');
          return;
        }

        await api('api/save.php', 'POST', payload);

        closeModal();
        calendar.refetchEvents();
      } catch (e) {
        alert('تعذر الحفظ: ' + e.message);
      }
    });

    btnDelete.addEventListener('click', async function() {
      if (!elEventId.value) return;
      if (!confirm('هل أنت متأكد من حذف هذه المهمة؟')) return;

      try {
        await api('api/delete.php', 'POST', { event_id: parseInt(elEventId.value) });
        closeModal();
        calendar.refetchEvents();
      } catch (e) {
        alert('تعذر الحذف: ' + e.message);
      }
    });
  });
</script>

</body>
</html>
