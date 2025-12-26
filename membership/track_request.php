<?php
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
<body>

<?php include(__DIR__ . "/../includes/header.php"); ?>

<div class="wrap">
  <h2>تتبّع طلب الانتساب</h2>

  <form method="POST">
    <label>الرقم الوطني</label>
    <input type="text" name="national_id" placeholder="مثال: 1234567890" required>

    <label>كود الطلب</label>
    <input type="text" name="public_code" placeholder="مثال: A1B2C3D4E5" required>

    <button type="submit">بحث</button>
    <br>
  </form>

  <?php if ($error): ?>
    <div class="err"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($statusRow): ?>
    <?php
      $st = $statusRow['status'];
      $badgeClass = $st === 'approved' ? 'b-approved' : ($st === 'rejected' ? 'b-rejected' : 'b-pending');
    ?>
    <div class="card">
      <div class="row">
        <div>
          <strong>حالة الطلب:</strong>
          <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(statusLabel($st)) ?></span>
        </div>
        <div><strong>نوع مقدم الطلب:</strong> <?= htmlspecialchars(applicantLabel($statusRow['role'])) ?></div>
        <div><strong>تاريخ الإرسال:</strong> <?= htmlspecialchars($statusRow['created_at'] ?? '-') ?></div>
        <div><strong>تاريخ المراجعة:</strong> <?= htmlspecialchars($statusRow['reviewed_at'] ?? '-') ?></div>
      </div>

      <?php if ($st === 'approved'): ?>
        <p style="margin-top:12px;">
          تمت الموافقة على طلبك.
          <?php if (!empty($statusRow['approved_syndicate_id'])): ?>
            رقم سجل النقابة: <strong><?= htmlspecialchars($statusRow['approved_syndicate_id']) ?></strong>
          <?php endif; ?>
        </p>
      <?php elseif ($st === 'rejected'): ?>
        <p style="margin-top:12px;">
          تم رفض الطلب. يمكنك التواصل مع النقابة لمعرفة السبب أو تقديم طلب جديد وفق السياسة المعتمدة.
        </p>
      <?php else: ?>
        <p style="margin-top:12px;">
          الطلب قيد المراجعة لدى النقابة. يرجى إعادة التحقق لاحقًا.
        </p>
      <?php endif; ?>
    </div>
  <?php elseif ($submitted && !$error): ?>
    <div class="err">لا توجد بيانات لعرضها.</div>
  <?php endif; ?>

</div>

</body>
</html>
