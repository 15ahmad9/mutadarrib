<?php
require_once __DIR__ . "/includes/auth_check.php";

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$provider_user_id = (int)($_SESSION['user_id'] ?? 0);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  // ما نطبع HTML قبل ما نبني الصفحة كاملة، بس هون ما في redirect
  $page_error = "❌ معرّف الفرصة غير صحيح.";
} else {
  $page_error = "";
}

/* =========================
   جلب الفرصة (إذا id صحيح)
========================= */
$internship = null;
if (!$page_error) {
  $stmt = $pdo->prepare("
    SELECT *
    FROM it_internships
    WHERE internship_id = ? AND provider_user_id = ?
    LIMIT 1
  ");
  $stmt->execute([$id, $provider_user_id]);
  $internship = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$internship) {
    $page_error = "❌ لا تملك صلاحية تعديل هذه الفرصة أو أنها غير موجودة.";
  }
}

$message = "";

/* =========================
   حذف
========================= */
if (!$page_error && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  try {
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM it_applications WHERE internship_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM it_internships WHERE internship_id = ? AND provider_user_id = ? LIMIT 1")->execute([$id, $provider_user_id]);
    $pdo->commit();

    header("Location: /mutadarrib/it/dashboard.php");
    exit;
  } catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $message = "<p class='error'>❌ خطأ أثناء الحذف: " . h($e->getMessage()) . "</p>";
  }
}

/* =========================
   تحديث
========================= */
if (!$page_error && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {

  $title           = trim($_POST['title'] ?? '');
  $description     = trim($_POST['description'] ?? '');
  $internship_type = trim($_POST['internship_type'] ?? 'onsite');

  $city            = trim($_POST['city'] ?? '');
  $country         = trim($_POST['country'] ?? 'الأردن');

  $duration_weeks  = trim($_POST['duration_weeks'] ?? '');
  $start_date      = trim($_POST['start_date'] ?? '');
  $end_date        = trim($_POST['end_date'] ?? '');

  $required_skills = trim($_POST['required_skills'] ?? '');
  $seats           = trim($_POST['seats'] ?? '');

  $status          = trim($_POST['status'] ?? $internship['status']);

  $errors = [];
  if ($title === '') $errors[] = "عنوان الفرصة مطلوب.";
  if ($description === '') $errors[] = "وصف الفرصة مطلوب.";

  if (!in_array($internship_type, ['onsite','remote','hybrid'], true)) $errors[] = "نوع التدريب غير صحيح.";

  if ($duration_weeks !== '' && (!ctype_digit($duration_weeks) || (int)$duration_weeks < 1 || (int)$duration_weeks > 520)) {
    $errors[] = "مدة التدريب يجب أن تكون رقم صحيح.";
  }

  if ($seats !== '' && (!ctype_digit($seats) || (int)$seats < 1 || (int)$seats > 10000)) {
    $errors[] = "عدد المقاعد يجب أن يكون رقم صحيح.";
  }

  if ($start_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    $errors[] = "صيغة تاريخ البدء غير صحيحة (YYYY-MM-DD).";
  }

  if ($end_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    $errors[] = "صيغة تاريخ الانتهاء غير صحيحة (YYYY-MM-DD).";
  }

  if (!in_array($status, ['published','closed','draft'], true)) {
    $errors[] = "الحالة غير صحيحة.";
  }

  if ($errors) {
    $message = "<p class='error'>❌ " . implode("<br>", array_map('h', $errors)) . "</p>";
  } else {
    try {
      // إذا الحالة صارت published ولم يكن لها published_at قبل
      $setPublishedAt = ($status === 'published' && empty($internship['published_at'])) ? 1 : 0;

      $stmtUp = $pdo->prepare("
        UPDATE it_internships
        SET
          title = ?,
          description = ?,
          internship_type = ?,
          city = ?,
          country = ?,
          duration_weeks = ?,
          start_date = ?,
          end_date = ?,
          required_skills = ?,
          seats = ?,
          status = ?,
          published_at = IF(?, NOW(), published_at),
          updated_at = NOW()
        WHERE internship_id = ? AND provider_user_id = ?
        LIMIT 1
      ");

      $stmtUp->execute([
        $title,
        $description,
        $internship_type,
        ($city !== '' ? $city : null),
        ($country !== '' ? $country : null),
        ($duration_weeks !== '' ? (int)$duration_weeks : null),
        ($start_date !== '' ? $start_date : null),
        ($end_date !== '' ? $end_date : null),
        ($required_skills !== '' ? $required_skills : null),
        ($seats !== '' ? (int)$seats : null),
        $status,
        $setPublishedAt,
        $id,
        $provider_user_id
      ]);

      // إعادة جلب البيانات بعد التحديث
      $stmt->execute([$id, $provider_user_id]);
      $internship = $stmt->fetch(PDO::FETCH_ASSOC);

      $message = "<p class='success'>✅ تم تحديث الفرصة بنجاح.</p>";

    } catch (Exception $e) {
      $message = "<p class='error'>❌ خطأ: " . h($e->getMessage()) . "</p>";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تعديل فرصة تدريب</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
  <link rel="stylesheet" href="/mutadarrib/assets/css/admin.css">
  <link rel="stylesheet" href="/mutadarrib/assets/css/it.css">
</head>

<body data-theme="<?= h($theme) ?>">
<div class="it-shell" id="itShell">

  <?php include __DIR__ . "/includes/header.php"; ?>

  <div class="it-body">
    <?php include __DIR__ . "/includes/sidebar.php"; ?>

    <main class="it-main">

      <?php if ($page_error): ?>
        <p class="error"><?= h($page_error) ?></p>
      <?php else: ?>

        <div class="it-main-head">
          <div>
            <h1>تعديل فرصة تدريب</h1>
            <p>قم بتحديث تفاصيل الفرصة ثم احفظ التغييرات.</p>
          </div>
          <div style="display:flex; gap:10px; flex-wrap:wrap">
            <a class="btn btn-ghost" href="/mutadarrib/it/dashboard.php">↩️ الرجوع للوحة المزود</a>
            <a class="btn btn-outline" href="/mutadarrib/it/it_provider_applicants.php?internship_id=<?= (int)$internship['internship_id'] ?>">
              عرض المتقدمين
            </a>
          </div>
        </div>

        <?= $message ?>

        <form method="POST" class="auth-form" style="max-width:920px;">
          <input type="hidden" name="action" value="update">

          <div class="auth-grid">

            <div class="auth-field col-12">
              <label for="title">عنوان الفرصة *</label>
              <input type="text" id="title" name="title" required value="<?= h($_POST['title'] ?? $internship['title']) ?>">
            </div>

            <div class="auth-field col-12">
              <label for="description">وصف الفرصة *</label>
              <textarea id="description" name="description" rows="6" required><?= h($_POST['description'] ?? $internship['description']) ?></textarea>
            </div>

            <div class="auth-field col-6">
              <label for="internship_type">نوع التدريب *</label>
              <?php $t = $_POST['internship_type'] ?? $internship['internship_type']; ?>
              <select id="internship_type" name="internship_type" required>
                <option value="onsite" <?= ($t==='onsite'?'selected':'') ?>>حضوري</option>
                <option value="remote" <?= ($t==='remote'?'selected':'') ?>>عن بُعد</option>
                <option value="hybrid" <?= ($t==='hybrid'?'selected':'') ?>>هجين</option>
              </select>
            </div>

            <div class="auth-field col-6">
              <label for="status">الحالة *</label>
              <?php $st = $_POST['status'] ?? $internship['status']; ?>
              <select id="status" name="status" required>
                <option value="published" <?= ($st==='published'?'selected':'') ?>>منشورة</option>
                <option value="draft" <?= ($st==='draft'?'selected':'') ?>>مسودة</option>
                <option value="closed" <?= ($st==='closed'?'selected':'') ?>>مغلقة</option>
              </select>
              <div class="muted" style="margin-top:6px;">نشرها لأول مرة سيحدد تاريخ النشر تلقائياً.</div>
            </div>

            <div class="auth-field col-6">
              <label for="city">المدينة</label>
              <input type="text" id="city" name="city" value="<?= h($_POST['city'] ?? ($internship['city'] ?? '')) ?>">
            </div>

            <div class="auth-field col-6">
              <label for="country">الدولة</label>
              <input type="text" id="country" name="country" value="<?= h($_POST['country'] ?? ($internship['country'] ?? 'الأردن')) ?>">
            </div>

            <div class="auth-field col-4">
              <label for="duration_weeks">المدة (أسابيع)</label>
              <input type="number" id="duration_weeks" name="duration_weeks" min="1" max="520"
                     value="<?= h($_POST['duration_weeks'] ?? ($internship['duration_weeks'] ?? '')) ?>">
            </div>

            <div class="auth-field col-4">
              <label for="start_date">تاريخ البدء</label>
              <input type="date" id="start_date" name="start_date"
                     value="<?= h($_POST['start_date'] ?? ($internship['start_date'] ?? '')) ?>">
            </div>

            <div class="auth-field col-4">
              <label for="end_date">تاريخ الانتهاء</label>
              <input type="date" id="end_date" name="end_date"
                     value="<?= h($_POST['end_date'] ?? ($internship['end_date'] ?? '')) ?>">
            </div>

            <div class="auth-field col-12">
              <label for="required_skills">المهارات المطلوبة</label>
              <textarea id="required_skills" name="required_skills" rows="4"><?= h($_POST['required_skills'] ?? ($internship['required_skills'] ?? '')) ?></textarea>
            </div>

            <div class="auth-field col-4">
              <label for="seats">عدد المقاعد</label>
              <input type="number" id="seats" name="seats" min="1" max="10000"
                     value="<?= h($_POST['seats'] ?? ($internship['seats'] ?? '')) ?>">
            </div>

          </div>

          <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
            <button type="submit" class="auth-submit">حفظ التعديلات</button>

            <a class="btn btn-outline" href="/mutadarrib/it/it_internship_view.php?id=<?= (int)$internship['internship_id'] ?>" target="_blank" rel="noopener">
              معاينة العرض
            </a>
          </div>
        </form>

        <hr style="margin:18px 0; border:0; border-top:1px solid rgba(15,23,42,.10);">

        <form method="POST" onsubmit="return confirm('هل أنت متأكد من حذف الفرصة؟ سيتم حذف كل طلباتها أيضاً.');">
          <input type="hidden" name="action" value="delete">
          <button type="submit" class="btn btn-outline" style="border-color:#b42318;color:#b42318;">
            🗑️ حذف الفرصة
          </button>
        </form>

        <!-- تم نقل fallback helpers إلى assets/css/it.css -->

      <?php endif; ?>

    </main>
  </div>

  <?php include __DIR__ . "/includes/footer.php"; ?>
</div>
</body>
</html>
