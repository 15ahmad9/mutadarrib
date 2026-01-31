<?php
require_once __DIR__ . "/includes/auth_check.php";

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$provider_user_id = (int)($_SESSION['user_id'] ?? 0);

// ✅ دعم اسمين للباراميتر: internship_id أو id
$internship_id = (int)($_GET['internship_id'] ?? ($_GET['id'] ?? 0));

/* =========================================================
   1) لو ما وصل internship_id: اعرض قائمة فرص المزود لاختيار واحدة
========================================================= */
$items = [];
if ($internship_id <= 0) {
  $stmtPick = $pdo->prepare("
    SELECT internship_id, title, status, created_at
    FROM it_internships
    WHERE provider_user_id = ?
    ORDER BY internship_id DESC
    LIMIT 50
  ");
  $stmtPick->execute([$provider_user_id]);
  $items = $stmtPick->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================================================
   2) وصل internship_id: كمل طبيعي
========================================================= */
$internship = null;
$message = "";
$applications = [];

if ($internship_id > 0) {

  // تأكد أن الفرصة تخص هذا المزود
  $stmtI = $pdo->prepare("
    SELECT internship_id, title, status
    FROM it_internships
    WHERE internship_id = ? AND provider_user_id = ?
    LIMIT 1
  ");
  $stmtI->execute([$internship_id, $provider_user_id]);
  $internship = $stmtI->fetch(PDO::FETCH_ASSOC);

  // إذا ما عنده صلاحية
  if (!$internship) {
    $internship = false; // flag
  } else {

    // تغيير حالة طلب
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'], $_POST['new_status'])) {
      $application_id = (int)$_POST['application_id'];
      $new_status = trim($_POST['new_status']);

      if (!in_array($new_status, ['submitted','under_review','accepted','rejected'], true)) {
        $message = "<p class='error'>❌ حالة غير صحيحة.</p>";
      } else {

        // تحقق أن الطلب يخص فرصة هذا المزود
        $stmtChk = $pdo->prepare("
          SELECT a.application_id
          FROM it_applications a
          JOIN it_internships i ON i.internship_id = a.internship_id
          WHERE a.application_id = ? AND i.provider_user_id = ?
          LIMIT 1
        ");
        $stmtChk->execute([$application_id, $provider_user_id]);
        $ok = $stmtChk->fetchColumn();

        if (!$ok) {
          $message = "<p class='error'>❌ لا تملك صلاحية تعديل هذا الطلب.</p>";
        } else {
          $stmtUp = $pdo->prepare("
            UPDATE it_applications
            SET status = ?, reviewed_at = NOW(), trainee_seen = 0
            WHERE application_id = ? LIMIT 1
          ");
          $stmtUp->execute([$new_status, $application_id]);
          $message = "<p class='success'>✅ تم تحديث حالة الطلب.</p>";
        }
      }
    }

    // جلب الطلبات
    $stmtA = $pdo->prepare("
      SELECT
        a.application_id,
        a.trainee_user_id,
        a.cover_letter,
        a.cv_file_path,
        a.status,
        a.applied_at,
        u.full_name,
        u.email,
        u.phone
      FROM it_applications a
      JOIN users u ON u.user_id = a.trainee_user_id
      WHERE a.internship_id = ?
      ORDER BY a.application_id DESC
    ");
    $stmtA->execute([$internship_id]);
    $applications = $stmtA->fetchAll(PDO::FETCH_ASSOC);
  }
}

$statusAr = [
  'submitted'    => 'مُرسل',
  'under_review' => 'قيد المراجعة',
  'accepted'     => 'مقبول',
  'rejected'     => 'مرفوض',
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>طلبات المتقدمين</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
  <link rel="stylesheet" href="/mutadarrib/assets/css/admin.css">
</head>

<body data-theme="<?= h($theme) ?>">
<div class="it-shell" id="itShell">

  <?php include __DIR__ . "/includes/header.php"; ?>

  <div class="it-body">
    <?php include __DIR__ . "/includes/sidebar.php"; ?>

    <main class="it-main">

      <?php if ($internship_id <= 0): ?>

        <div class="it-main-head">
          <div>
            <h1>المتقدمون</h1>
            <p class="muted">اختر فرصة تدريب لعرض المتقدمين عليها.</p>
          </div>
        </div>

        <?php if (!$items): ?>
          <div class="empty-state">
            لا يوجد لديك فرص تدريب بعد.
            <div style="margin-top:10px;">
              <a class="btn btn-outline" href="/mutadarrib/it/it_internship_create.php">+ إضافة فرصة تدريب</a>
            </div>
          </div>
        <?php else: ?>
          <div class="table-wrap" style="margin-top:12px;">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>الفرصة</th>
                  <th>الحالة</th>
                  <th>تاريخ الإضافة</th>
                  <th>عرض المتقدمين</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $it): ?>
                  <tr>
                    <td>
                      <strong><?= h($it['title']) ?></strong>
                      <span class="muted">#<?= (int)$it['internship_id'] ?></span>
                    </td>
                    <td><?= h($it['status']) ?></td>
                    <td class="muted"><?= h($it['created_at'] ?? '-') ?></td>
                    <td>
                      <a class="btn btn-outline btn-sm"
                        href="/mutadarrib/it/it_provider_applicants.php?internship_id=<?= (int)$it['internship_id'] ?>">
                        فتح
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

      <?php else: ?>

        <?php if ($internship === false): ?>
          <p class="error">❌ لا تملك صلاحية على هذه الفرصة أو غير موجودة.</p>

        <?php else: ?>

          <div class="it-main-head">
            <div>
              <h1>المتقدمون</h1>
              <p>الفرصة: <strong><?= h($internship['title']) ?></strong> — الحالة: <?= h($internship['status']) ?></p>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
              <a class="btn btn-ghost" href="/mutadarrib/it/provider_internships.php">↩️ الرجوع لإدارة الفرص</a>
            </div>
          </div>

          <?= $message ?>

          <?php if (!$applications): ?>
            <div class="empty-state">لا يوجد طلبات حتى الآن.</div>
          <?php else: ?>

            <div class="table-wrap" style="margin-top:12px;">
              <table class="dash-table">
                <thead>
                  <tr>
                    <th>المتدرب</th>
                    <th>التواصل</th>
                    <th>الرسالة</th>
                    <th>CV</th>
                    <th>الحالة</th>
                    <th>إجراء</th>
                  </tr>
                </thead>

                <tbody>
                  <?php foreach ($applications as $a): ?>
                    <tr>
                      <td>
                        <strong><?= h($a['full_name']) ?></strong>
                        <div class="muted">تاريخ التقديم: <?= h($a['applied_at']) ?></div>
                      </td>

                      <td>
                        <div><?= h($a['email'] ?? '-') ?></div>
                        <div class="muted"><?= h($a['phone'] ?? '-') ?></div>
                      </td>

                      <td style="max-width:360px;">
                        <?= $a['cover_letter'] ? nl2br(h($a['cover_letter'])) : '<span class="muted">—</span>' ?>
                      </td>

                      <td>
                        <?php if (!empty($a['cv_file_path'])): ?>
                          <a class="btn btn-outline btn-sm" target="_blank" rel="noopener"
                            href="/mutadarrib/<?= h($a['cv_file_path']) ?>">
                            عرض الملف
                          </a>
                        <?php else: ?>
                          <span class="muted">لا يوجد</span>
                        <?php endif; ?>
                      </td>

                      <td>
                        <span class="badge badge-blue"><?= h($statusAr[$a['status']] ?? $a['status']) ?></span>
                      </td>

                      <td class="actions-cell">
                        <form method="POST" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                          <input type="hidden" name="application_id" value="<?= (int)$a['application_id'] ?>">
                          <select name="new_status" class="select-sm">
                            <?php foreach (['submitted','under_review','accepted','rejected'] as $st): ?>
                              <option value="<?= $st ?>" <?= ($a['status']===$st?'selected':'') ?>>
                                <?= h($statusAr[$st] ?? $st) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                          <button class="btn btn-ghost btn-sm" type="submit">تحديث</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

          <?php endif; ?>

        <?php endif; ?>
      <?php endif; ?>

      <style>
        .table-wrap{overflow:auto;border-radius:14px;border:1px solid rgba(15,23,42,.08)}
        .dash-table{width:100%;border-collapse:collapse;background:#fff}
        .dash-table th,.dash-table td{padding:12px;border-bottom:1px solid rgba(15,23,42,.08);text-align:right;vertical-align:top}
        .dash-table th{background:#f7f8ff;color:#1b2a7a;font-weight:900}
        .muted{color:#8890b4;font-size:12px;margin-top:6px}
        .actions-cell{white-space:nowrap}
        .badge{display:inline-block;padding:6px 10px;border-radius:999px;font-weight:900;font-size:12px}
        .badge-blue{background:#eef1ff;color:#1b2a7a}
        .select-sm{padding:8px 10px;border-radius:12px;border:1px solid rgba(15,23,42,.14)}
        .btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 16px;border-radius:14px;text-decoration:none;font-weight:800;border:0;cursor:pointer}
        .btn-outline{background:#fff;color:#4154d0;border:2px solid #4154d0}
        .btn-ghost{background:#f4f6ff;color:#1b2a7a}
        .btn-sm{padding:8px 12px;border-radius:12px;font-size:13px}
        .empty-state{padding:18px;background:#fff;border:1px dashed rgba(15,23,42,.18);border-radius:16px;color:#5b5f85}
      </style>

    </main>
  </div>

  <?php include __DIR__ . "/includes/footer.php"; ?>
</div>
</body>
</html>
