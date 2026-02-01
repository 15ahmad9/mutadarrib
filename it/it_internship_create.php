<?php
require_once __DIR__ . '/../includes/theme_init.php';
require_once __DIR__ . "/includes/auth_check.php";

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$provider_user_id = (int)($_SESSION['user_id'] ?? 0);
$message = "";

// ===== POST handling قبل أي output =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $title          = trim($_POST['title'] ?? '');
  $description    = trim($_POST['description'] ?? '');
  $internship_type= trim($_POST['internship_type'] ?? 'onsite');

  $city           = trim($_POST['city'] ?? '');
  $country        = trim($_POST['country'] ?? 'الأردن');

  $duration_weeks = trim($_POST['duration_weeks'] ?? '');
  $start_date     = trim($_POST['start_date'] ?? '');
  $end_date       = trim($_POST['end_date'] ?? '');

  $required_skills= trim($_POST['required_skills'] ?? '');
  $seats          = trim($_POST['seats'] ?? '');

  $status         = trim($_POST['status'] ?? 'published');

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

  if ($start_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) $errors[] = "صيغة تاريخ البدء غير صحيحة.";
  if ($end_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) $errors[] = "صيغة تاريخ الانتهاء غير صحيحة.";

  if (!in_array($status, ['published','closed','draft'], true)) $errors[] = "الحالة غير صحيحة.";

  if ($errors) {
    $message = "<p class='error'>❌ " . implode("<br>", array_map('h', $errors)) . "</p>";
  } else {
    try {
      $stmt = $pdo->prepare("
        INSERT INTO it_internships
          (provider_user_id, title, description, internship_type, city, country,
           duration_weeks, start_date, end_date, required_skills, seats, status, published_at, created_at, updated_at)
        VALUES
          (?, ?, ?, ?, ?, ?,
           ?, ?, ?, ?, ?, ?, IF(?='published', NOW(), NULL), NOW(), NOW())
      ");

      $stmt->execute([
        $provider_user_id,
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
        $status
      ]);

      header("Location: /mutadarrib/it/dashboard.php?created=1");
      exit;

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
  <title>إضافة فرصة تدريب</title>
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

      <div class="it-main-head">
        <div>
          <h1>إضافة فرصة تدريب IT</h1>
          <p>قم بإدخال بيانات الفرصة ثم نشرها ليستطيع المتدربون التقديم.</p>
        </div>
      </div>

      <?= $message ?>

      <form method="POST" class="auth-form" style="max-width:900px;">
        <div class="auth-grid">

          <div class="auth-field col-12">
            <label for="title">عنوان الفرصة *</label>
            <input type="text" id="title" name="title" required value="<?= h($_POST['title'] ?? '') ?>">
          </div>

          <div class="auth-field col-12">
            <label for="description">وصف الفرصة *</label>
            <textarea id="description" name="description" rows="5" required><?= h($_POST['description'] ?? '') ?></textarea>
          </div>

          <div class="auth-field col-6">
            <label for="internship_type">نوع التدريب *</label>
            <?php $t = $_POST['internship_type'] ?? 'onsite'; ?>
            <select id="internship_type" name="internship_type" required>
              <option value="onsite" <?= ($t==='onsite'?'selected':'') ?>>حضوري</option>
              <option value="remote" <?= ($t==='remote'?'selected':'') ?>>عن بُعد</option>
              <option value="hybrid" <?= ($t==='hybrid'?'selected':'') ?>>هجين</option>
            </select>
          </div>

          <div class="auth-field col-6">
            <label for="status">الحالة *</label>
            <?php $st = $_POST['status'] ?? 'published'; ?>
            <select id="status" name="status" required>
              <option value="published" <?= ($st==='published'?'selected':'') ?>>منشورة</option>
              <option value="draft" <?= ($st==='draft'?'selected':'') ?>>مسودة</option>
              <option value="closed" <?= ($st==='closed'?'selected':'') ?>>مغلقة</option>
            </select>
          </div>

          <div class="auth-field col-6">
            <label for="city">المدينة</label>
            <input type="text" id="city" name="city" value="<?= h($_POST['city'] ?? '') ?>">
          </div>

          <div class="auth-field col-6">
            <label for="country">الدولة</label>
            <input type="text" id="country" name="country" value="<?= h($_POST['country'] ?? 'الأردن') ?>">
          </div>

          <div class="auth-field col-4">
            <label for="duration_weeks">المدة (أسابيع)</label>
            <input type="number" id="duration_weeks" name="duration_weeks" min="1" max="520"
                  value="<?= h($_POST['duration_weeks'] ?? '') ?>">
          </div>

          <div class="auth-field col-4">
            <label for="start_date">تاريخ البدء</label>
            <input type="date" id="start_date" name="start_date" value="<?= h($_POST['start_date'] ?? '') ?>">
          </div>

          <div class="auth-field col-4">
            <label for="end_date">تاريخ الانتهاء</label>
            <input type="date" id="end_date" name="end_date" value="<?= h($_POST['end_date'] ?? '') ?>">
          </div>

          <div class="auth-field col-12">
            <label for="required_skills">المهارات المطلوبة</label>
            <textarea id="required_skills" name="required_skills" rows="4"><?= h($_POST['required_skills'] ?? '') ?></textarea>
          </div>

          <div class="auth-field col-4">
            <label for="seats">عدد المقاعد</label>
            <input type="number" id="seats" name="seats" min="1" max="10000"
                  value="<?= h($_POST['seats'] ?? '') ?>">
          </div>

        </div>

        <button type="submit" class="auth-submit">حفظ الفرصة</button>
      </form>

    </main>
  </div>

  <?php include __DIR__ . "/includes/footer.php"; ?>
</div>
</body>
</html>
