<?php
include __DIR__ . "/includes/it_provider_layout.php";
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$provider_user_id = (int)($_SESSION['user_id'] ?? 0);

$internship_id = (int)($_GET['internship_id'] ?? 0);
if ($internship_id <= 0) {
  echo "<p class='error'>❌ internship_id غير صحيح.</p>";
  include __DIR__ . "/includes/it_provider_layout_footer.php";
  exit;
}

// تأكد أن الفرصة تخص هذا المزود
$stmtI = $pdo->prepare("
  SELECT internship_id, title, status
  FROM it_internships
  WHERE internship_id = ? AND provider_user_id = ?
  LIMIT 1
");
$stmtI->execute([$internship_id, $provider_user_id]);
$internship = $stmtI->fetch(PDO::FETCH_ASSOC);

if (!$internship) {
  echo "<p class='error'>❌ لا تملك صلاحية على هذه الفرصة.</p>";
  include __DIR__ . "/includes/it_provider_layout_footer.php";
  exit;
}

$message = "";

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
      $stmtUp = $pdo->prepare("UPDATE it_applications SET status = ? WHERE application_id = ? LIMIT 1");
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

$statusAr = [
  'submitted'    => 'مُرسل',
  'under_review' => 'قيد المراجعة',
  'accepted'     => 'مقبول',
  'rejected'     => 'مرفوض',
];
?>

<div class="it-main-head">
  <div>
    <h1>المتقدمون</h1>
    <p>الفرصة: <strong><?= h($internship['title']) ?></strong> — الحالة: <?= h($internship['status']) ?></p>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a class="btn btn-ghost" href="/mutadarrib/it/dashboard.php">↩️ الرجوع للوحة المزود</a>
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

<style>
/* نفس ستايل لوحة المزود (fallback) */
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

<?php include __DIR__ . "/includes/it_provider_layout_footer.php"; ?>
