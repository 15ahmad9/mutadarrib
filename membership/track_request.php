<?php
require_once __DIR__ . '/../includes/theme_init.php';

session_start();
require_once __DIR__ . "/../config/db.php";

// Rate limit بسيط لمنع التخمين
$now = time();
$_SESSION['track_attempts'] = $_SESSION['track_attempts'] ?? [];
// احذف المحاولات الأقدم من 5 دقائق
$_SESSION['track_attempts'] = array_filter($_SESSION['track_attempts'], fn($t) => ($now - $t) < 300);

if (count($_SESSION['track_attempts']) >= 10) {
  die("تم حظر المحاولات مؤقتًا لكثرة الاستعلامات. حاول لاحقًا.");
}

$statusRow = null;
$error = "";
$submitted = false;

function cleanNat($s) {
  $s = trim($s);
  // الرقم الوطني عادة أرقام فقط
  if (!preg_match('/^\d{8,20}$/', $s)) return null;
  return $s;
}

function cleanCode($s) {
  $s = strtoupper(trim($s));
  // كودنا كان HEX بطول 10 (يمكنك توسيعه إذا غيرت الطول)
  if (!preg_match('/^[A-Z0-9]{6,12}$/', $s)) return null;
  return $s;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $submitted = true;
  $_SESSION['track_attempts'][] = $now;

  $national_id = cleanNat($_POST['national_id'] ?? "");
  $public_code = cleanCode($_POST['public_code'] ?? "");

  if (!$national_id || !$public_code) {
    $error = "الرجاء إدخال رقم وطني صحيح وكود صحيح.";
  } else {
    $stmt = $pdo->prepare("
      SELECT
        request_id,
        role,
        status,
        created_at,
        reviewed_at,
        approved_syndicate_id
      FROM membership_requests
      WHERE national_id = ?
        AND public_code = ?
      ORDER BY created_at DESC
      LIMIT 1
    ");
    $stmt->execute([$national_id, $public_code]);
    $statusRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$statusRow) {
      $error = "لم يتم العثور على طلب مطابق. تأكد من الرقم الوطني والكود.";
    }
  }
}

function statusLabel($status) {
  return match($status) {
    'pending'  => 'قيد المراجعة',
    'approved' => 'تمت الموافقة',
    'rejected' => 'تم الرفض',
    default    => $status
  };
}

function applicantLabel($t) {
  return $t === 'trainee' ? 'متدرب' : ($t === 'lawyer' ? 'مزاول' : $t);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تتبّع طلب الانتساب</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">

</head>
<body class="track-page" data-theme="<?= htmlspecialchars($theme) ?>">

<?php include(__DIR__ . "/../includes/header.php"); ?>

<main class="auth-shell">
  <div class="auth-card">
    <div class="auth-head">
      <h1 class="auth-title">تتبّع طلب الانتساب</h1>
      <p class="auth-subtitle">أدخل الرقم الوطني وكود الطلب لعرض الحالة.</p>
    </div>

    <form class="auth-form" method="POST" autocomplete="off" novalidate>
      <div class="auth-grid">
        <div class="auth-field col-6">
          <label for="national_id">الرقم الوطني</label>
          <input id="national_id" type="text" inputmode="numeric" name="national_id" placeholder="مثال: 1234567890" required>
        </div>

        <div class="auth-field col-6">
          <label for="public_code">كود الطلب</label>
          <input id="public_code" type="text" name="public_code" placeholder="مثال: A1B2C3D4E5" required>
        </div>
      </div>

      <button class="auth-submit" type="submit">بحث</button>
    </form>

    <?php if ($error): ?>
      <div class="err track-alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($statusRow): ?>
      <?php
        $st = $statusRow['status'];
        $badgeClass = $st === 'approved' ? 'b-approved' : ($st === 'rejected' ? 'b-rejected' : 'b-pending');
      ?>
      <div class="track-result">
        <div class="track-meta">
          <div class="item">
            <span class="k">حالة الطلب</span>
            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(statusLabel($st)) ?></span>
          </div>
          <div class="item">
            <span class="k">نوع مقدم الطلب</span>
            <span class="v"><?= htmlspecialchars(applicantLabel($statusRow['role'])) ?></span>
          </div>
          <div class="item">
            <span class="k">تاريخ الإرسال</span>
            <span class="v"><?= htmlspecialchars($statusRow['created_at'] ?? '-') ?></span>
          </div>
          <div class="item">
            <span class="k">تاريخ المراجعة</span>
            <span class="v"><?= htmlspecialchars($statusRow['reviewed_at'] ?? '-') ?></span>
          </div>
        </div>

        <?php if ($st === 'approved'): ?>
          <p class="track-note track-note--ok">
            تمت الموافقة على طلبك.
            <?php if (!empty($statusRow['approved_syndicate_id'])): ?>
              رقم سجل النقابة: <strong><?= htmlspecialchars($statusRow['approved_syndicate_id']) ?></strong>
            <?php endif; ?>
          </p>
        <?php elseif ($st === 'rejected'): ?>
          <p class="track-note track-note--bad">
            تم رفض الطلب. يمكنك التواصل مع النقابة لمعرفة السبب أو تقديم طلب جديد وفق السياسة المعتمدة.
          </p>
        <?php else: ?>
          <p class="track-note">
            الطلب قيد المراجعة لدى النقابة. يرجى إعادة التحقق لاحقًا.
          </p>
        <?php endif; ?>
      </div>
    <?php elseif ($submitted && !$error): ?>
      <div class="err track-alert">لا توجد بيانات لعرضها.</div>
    <?php endif; ?>

  </div>
</main>

<?php include(__DIR__ . "/../includes/footer.php"); ?>

</body>
</html>
