<?php
require_once __DIR__ . '/../../includes/theme_init.php';

session_start();
require_once __DIR__ . "/../../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'syndicate_admin') {
  header("Location: /mutadarrib/auth/login.php");
  exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("رقم طلب غير صالح.");

$stmt = $pdo->prepare("SELECT * FROM membership_requests WHERE request_id=? LIMIT 1");
$stmt->execute([$id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$r) die("الطلب غير موجود.");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>عرض طلب الانتساب</title>
    <link rel="stylesheet" href="/mutadarrib/assets/css/admin.css">
  <link rel="stylesheet" href="/mutadarrib/assets/css/lawyers.css">
  <style>
    .box{background:#fff;border:1px solid #e6e6e6;border-radius:14px;padding:14px;}
    .row{display:flex;gap:14px;flex-wrap:wrap;}
    .row > div{flex:1;min-width:250px;}
    .btn{padding:8px 12px;border-radius:10px;text-decoration:none;color:#fff;display:inline-block;margin:4px 0;}
    .ok{background:#2d6a4f;} .no{background:#d62828;} .view{background:#4c6ef5;}
    label{display:block;margin-top:8px;color:#444;}
    input,textarea{width:100%;padding:8px;border:1px solid #ccc;border-radius:10px;}
  </style>
</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<?php include(__DIR__ . "/../includes/header.php"); ?>
<div class="admin-container">
  <?php include(__DIR__ . "/../includes/sidebar.php"); ?>

  <div class="container">
    <h2>عرض طلب الانتساب</h2>

    <div class="box">
      <div class="row">
        <div>
          <p><strong>الاسم:</strong> <?= htmlspecialchars($r['full_name']) ?></p>
          <p><strong>النوع:</strong> <?= $r['role']==='trainee'?'متدرب':'مزاول' ?></p>
          <p><strong>الرقم الوطني:</strong> <?= htmlspecialchars($r['national_id']) ?></p>
          <p><strong>الهاتف:</strong> <?= htmlspecialchars($r['phone'] ?? '-') ?></p>
          <p><strong>البريد:</strong> <?= htmlspecialchars($r['email'] ?? '-') ?></p>
          <p><strong>عنوان المكتب:</strong> <?= htmlspecialchars($r['office_address'] ?? '-') ?></p>
          <p><strong>ملاحظات:</strong> <?= nl2br(htmlspecialchars($r['notes'] ?? '')) ?></p>
        </div>

        <div>
          <p><strong>الحالة:</strong> <?= htmlspecialchars($r['status']) ?></p>
          <p><strong>تاريخ الإرسال:</strong> <?= htmlspecialchars($r['created_at']) ?></p>
          <p><strong>تاريخ المراجعة:</strong> <?= htmlspecialchars($r['reviewed_at'] ?? '-') ?></p>
          <p><strong>سبب الرفض:</strong> <?= nl2br(htmlspecialchars($r['rejection_reason'] ?? '-')) ?></p>

          <hr>

          <a class="btn view" target="_blank" href="/mutadarrib/syndicate/membership/download.php?id=<?= (int)$r['request_id'] ?>&doc=identity_front">عرض هوية (أمامي)</a><br>
          <a class="btn view" target="_blank" href="/mutadarrib/syndicate/membership/download.php?id=<?= (int)$r['request_id'] ?>&doc=identity_back">عرض هوية (خلفي)</a><br>

          <?php if (!empty($r['no_conviction_doc'])): ?>
            <a class="btn view" target="_blank" href="/mutadarrib/syndicate/membership/download.php?id=<?= (int)$r['request_id'] ?>&doc=no_conviction_doc">عدم محكومية</a><br>
          <?php endif; ?>

          <?php if (!empty($r['good_conduct_doc'])): ?>
            <a class="btn view" target="_blank" href="/mutadarrib/syndicate/membership/download.php?id=<?= (int)$r['request_id'] ?>&doc=good_conduct_doc">حسن سيرة</a><br>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($r['status'] === 'pending'): ?>
        <hr>
        <form method="POST" action="/mutadarrib/syndicate/membership/reject.php">
          <input type="hidden" name="id" value="<?= (int)$r['request_id'] ?>">
          <label>سبب الرفض (اختياري)</label>
          <textarea name="reason" rows="3" placeholder="اكتب سبب الرفض..."></textarea>
          <button class="btn no" type="submit" onclick="return confirm('تأكيد رفض الطلب؟')">رفض</button>
        </form>

        <form method="POST" action="/mutadarrib/syndicate/membership/approve.php" style="margin-top:10px;">
          <input type="hidden" name="id" value="<?= (int)$r['request_id'] ?>">
          <button class="btn ok" type="submit" onclick="return confirm('تأكيد قبول الطلب وتسجيله في جدول النقابة؟')">قبول وتسجيل بالنقابة</button>
        </form>
      <?php endif; ?>

    </div>
  </div>
</div>

</body>
</html>
