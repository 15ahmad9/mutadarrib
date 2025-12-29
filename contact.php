<?php
require_once __DIR__ . '/includes/theme_init.php';

session_start();
require_once __DIR__ . "/config/db.php";

$errors = [];
$success = "";

// افتراضات مبدئية (تعبئة تلقائية إذا كان المستخدم مسجلاً)
$prefillName  = $_SESSION['full_name'] ?? '';
$prefillEmail = '';
$prefillPhone = '';

if (isset($_SESSION['user_id'])) {
  $stmtU = $pdo->prepare("SELECT email, full_name, phone FROM users WHERE user_id = ? LIMIT 1");
  $stmtU->execute([(int)$_SESSION['user_id']]);
  $u = $stmtU->fetch(PDO::FETCH_ASSOC);
  if ($u) {
    $prefillName  = $u['full_name'] ?? $prefillName;
    $prefillEmail = $u['email'] ?? '';
    $prefillPhone = $u['phone'] ?? '';
  }
}

// دالة بسيطة لتنظيف الهاتف
function normalizePhone($phone) {
  $phone = trim($phone);
  // السماح بالأرقام و + والمسافات والشرطات
  $phone = preg_replace('/[^\d\+\-\s]/u', '', $phone);
  // إزالة تكرار المسافات
  $phone = preg_replace('/\s+/', ' ', $phone);
  return $phone;
}

// معالجة الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name    = trim($_POST['name'] ?? '');
  $email   = trim($_POST['email'] ?? '');
  $phone   = normalizePhone($_POST['phone'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $message = trim($_POST['message'] ?? '');

  if ($name === '' || mb_strlen($name) < 3) $errors[] = "الاسم مطلوب (3 أحرف على الأقل).";
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "البريد الإلكتروني غير صحيح.";

  // الهاتف: اجعله "مطلوب" إذا رغبت
  // حاليًا: اختياري، لكن إن كُتب يجب أن يكون بطول منطقي
  if ($phone !== '') {
    if (mb_strlen($phone) < 8 || mb_strlen($phone) > 20) {
      $errors[] = "رقم الهاتف غير صحيح (يجب أن يكون بين 8 و20 خانة تقريبًا).";
    }
  }

  if ($subject === '' || mb_strlen($subject) < 3) $errors[] = "الموضوع مطلوب (3 أحرف على الأقل).";
  if ($message === '' || mb_strlen($message) < 10) $errors[] = "الرسالة مطلوبة (10 أحرف على الأقل).";

  if (!$errors) {
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    $stmt = $pdo->prepare("
      INSERT INTO contact_messages (user_id, name, email, phone, subject, message)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $name, $email, ($phone !== '' ? $phone : null), $subject, $message]);

    $success = "تم إرسال رسالتك بنجاح. سيتم الرد عليك في أقرب وقت.";

    // تفريغ الحقول بعد الإرسال
    $name = $email = $phone = $subject = $message = '';
  }
} else {
  // القيم الافتراضية عند فتح الصفحة
  $name    = $prefillName;
  $email   = $prefillEmail;
  $phone   = $prefillPhone;
  $subject = '';
  $message = '';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تواصل معنا</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<?php include(__DIR__ . "/includes/header.php"); ?>

<main class="auth-shell contact-shell">
  <div class="auth-card auth-card--wide contact-card">
    <div class="auth-head">
      <h1 class="auth-title">تواصل معنا</h1>
      <p class="auth-subtitle">اكتب لنا رسالتك وسنعود إليك بأقرب وقت.</p>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <ul class="contact-list">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" class="auth-form">
      <div class="auth-grid contact-grid">
        <div class="auth-field col-6">
          <label for="c-name">الاسم</label>
          <input id="c-name" type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
        </div>

        <div class="auth-field col-6">
          <label for="c-email">البريد الإلكتروني</label>
          <input id="c-email" type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <div class="auth-field col-6">
          <label for="c-phone">رقم الهاتف <span class="muted">(اختياري)</span></label>
          <input id="c-phone" type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" placeholder="مثال: 079xxxxxxx أو +9627xxxxxxxx">
          <div class="small-note">يساعدنا رقم الهاتف على التواصل معك بسرعة عند الحاجة.</div>
        </div>

        <div class="auth-field col-6">
          <label for="c-subject">الموضوع</label>
          <input id="c-subject" type="text" name="subject" value="<?= htmlspecialchars($subject) ?>" required>
        </div>

        <div class="auth-field col-12">
          <label for="c-message">الرسالة</label>
          <textarea id="c-message" name="message" class="contact-textarea" required><?= htmlspecialchars($message) ?></textarea>
          <div class="small-note">يرجى كتابة تفاصيل واضحة لتسهيل الرد.</div>
        </div>
      </div>

      <button type="submit" class="btn-card auth-submit contact-submit">إرسال</button>

      <div class="contact-hint">
        * سيتم حفظ رسالتك في النظام ومراجعتها من الإدارة.
      </div>
    </form>
  </div>
</main>

<?php include(__DIR__ . "/includes/footer.php"); ?>

</body>
</html>
