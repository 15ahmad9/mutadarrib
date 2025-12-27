<?php
require_once __DIR__ . '/../includes/theme_init.php';

session_start();

$code = $_SESSION['membership_public_code'] ?? null;
$nat  = $_SESSION['membership_national_id'] ?? null;

// اعرضه مرة واحدة ثم امسحه
unset($_SESSION['membership_public_code'], $_SESSION['membership_national_id']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تم إرسال طلب الانتساب</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
  <style>
    .wrap{max-width:800px;margin:25px auto;background:#fff;padding:18px;border-radius:12px}
    .code{font-size:22px;font-weight:bold;letter-spacing:2px;direction:ltr;text-align:center;background:#f6f6f6;padding:10px;border-radius:10px}
    .btn{display:inline-block;margin-top:12px;padding:10px 14px;border-radius:10px;background:#0077b6;color:#fff;text-decoration:none}
    .muted{color:#666}
  </style>
</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<?php include(__DIR__ . "/../includes/header.php"); ?>

<div class="wrap">
  <h2>تم إرسال طلب الانتساب بنجاح</h2>

  <p class="muted">
    يرجى الاحتفاظ بالكود التالي لاستخدامه في تتبّع الطلب مع الرقم الوطني.
  </p>

  <?php if ($code): ?>
    <div class="code"><?= htmlspecialchars($code) ?></div>
    <p class="muted" style="margin-top:10px;">
      <?php if ($nat): ?>
        الرقم الوطني الذي أُرسل به الطلب: <strong><?= htmlspecialchars($nat) ?></strong>
      <?php endif; ?>
    </p>
  <?php else: ?>
    <p style="color:#b00020">
      لم يتم العثور على كود التتبّع في الجلسة. إذا أعدت فتح الصفحة لاحقاً لن يظهر الكود.
      يمكنك تقديم الطلب مرة أخرى أو طلب الكود من النقابة حسب السياسة التي ستعتمدها.
    </p>
  <?php endif; ?>

  <a class="btn" href="/mutadarrib/membership/track_request.php">تتبّع طلب الانتساب</a>
</div>

</body>
</html>
