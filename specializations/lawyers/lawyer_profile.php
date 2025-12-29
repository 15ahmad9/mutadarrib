<?php
require_once __DIR__ . '/../../includes/theme_init.php';

session_start();
require_once("../../config/db.php");

$lawyerId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
  SELECT *
  FROM lawyers
  WHERE lawyer_id = ?
    AND verified = 1
  LIMIT 1
");
$stmt->execute([$lawyerId]);
$lawyer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lawyer) {
  die("❌ هذا المكتب غير موجود أو غير مفعّل.");
}

/* =========================
   تحديد trainee_id إن كان المستخدم متدرب
========================= */
$traineeId = null;
if (isset($_SESSION['user_id'], $_SESSION['role']) && $_SESSION['role'] === 'trainee') {
  $stmtT = $pdo->prepare("SELECT trainee_id FROM trainees WHERE user_id = ? LIMIT 1");
  $stmtT->execute([(int) $_SESSION['user_id']]);
  $rowT = $stmtT->fetch(PDO::FETCH_ASSOC);
  if ($rowT)
    $traineeId = (int) $rowT['trainee_id'];
}

/* =========================
   فحص: هل لدى المتدرب طلب قائم (pending/accepted)؟
========================= */
$activeApp = null;
if ($traineeId !== null) {
  $stmtActive = $pdo->prepare("
    SELECT 
      a.application_id,
      a.status,
      a.training_id,
      t.title AS training_title,
      t.lawyer_id,
      l.full_name AS lawyer_name
    FROM training_applications a
    JOIN trainings t ON t.training_id = a.training_id
    JOIN lawyers l ON l.lawyer_id = t.lawyer_id
    WHERE a.trainee_id = ?
      AND a.status IN ('pending','accepted')
    ORDER BY a.applied_at DESC
    LIMIT 1
  ");
  $stmtActive->execute([$traineeId]);
  $activeApp = $stmtActive->fetch(PDO::FETCH_ASSOC);
}

/* =========================
   جلب جميع تدريبات المحامي + المقاعد المشغولة + حالة هذا المتدرب على كل تدريب
========================= */
$traineeParam = $traineeId ?? 0; // لتفادي NULL بالمقارنة

$stmtTr = $pdo->prepare("
  SELECT
    t.*,
    COALESCE(SUM(CASE WHEN a.status IN ('pending','accepted') THEN 1 ELSE 0 END), 0) AS occupied,
    MAX(CASE WHEN a.trainee_id = :trainee_id THEN a.status ELSE NULL END) AS my_status
  FROM trainings t
  LEFT JOIN training_applications a ON a.training_id = t.training_id
  WHERE t.lawyer_id = :lawyer_id
  GROUP BY t.training_id
  ORDER BY t.created_at DESC
");
$stmtTr->execute([
  ':trainee_id' => $traineeParam,
  ':lawyer_id' => $lawyerId,
]);
$trainings = $stmtTr->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($lawyer['full_name']) ?> | مكتب محاماة</title>

  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="../../assets/css/lawyers.css">

</head>

<body data-theme="<?= htmlspecialchars($theme) ?>">

  <?php include("../../includes/header.php"); ?>

  <div class="container profile-container">
    <div class="profile-card">

      <div class="avatar large">⚖️</div>

      <h2><?= htmlspecialchars($lawyer['full_name']) ?></h2>

      <p><strong>رقم السجل:</strong> <?= htmlspecialchars($lawyer['syndicate_id']) ?></p>
      <p><strong>الهاتف:</strong> <?= htmlspecialchars($lawyer['phone'] ?? '-') ?></p>
      <p><strong>البريد:</strong> <?= htmlspecialchars($lawyer['email'] ?? '-') ?></p>
      <p><strong>عنوان المكتب:</strong> <?= htmlspecialchars($lawyer['office_address'] ?? '-') ?></p>

      <hr>

      <h3>التدريبات المنشأة لدى المكتب</h3>

      <?php if ($activeApp && isset($_SESSION['role']) && $_SESSION['role'] === 'trainee'): ?>
        <div class="alert">
          لا يمكنك التقديم على تدريب جديد الآن لأن لديك طلب تدريب قائم.<br>
          المكتب: <strong><?= htmlspecialchars($activeApp['lawyer_name']) ?></strong><br>
          التدريب: <strong><?= htmlspecialchars($activeApp['training_title']) ?></strong><br>
          الحالة: <strong><?= htmlspecialchars($activeApp['status']) ?></strong>
        </div>
      <?php endif; ?>

      <div class="trainings-wrap">
        <?php if (empty($trainings)): ?>
          <p>لا يوجد تدريبات منشأة لهذا المكتب حالياً.</p>
        <?php else: ?>

          <?php foreach ($trainings as $t): ?>
            <?php
            // تحويل حالة الطلب إلى عربي + CSS class
            $statusMap = [
              'pending' => ['text' => 'قيد المراجعة', 'class' => 'b-pending'],
              'accepted' => ['text' => 'مقبول', 'class' => 'b-accepted'],
              'rejected' => ['text' => 'مرفوض', 'class' => 'b-rejected'],
              'completed' => ['text' => 'مكتمل', 'class' => 'b-completed'],
            ];

            $myStatus = $t['my_status'] ?? null;
            $myStatusBadgeHtml = '';

            if (!empty($myStatus) && isset($statusMap[$myStatus])) {
              $myStatusBadgeHtml = '<span class="badge ' . $statusMap[$myStatus]['class'] . '">' . $statusMap[$myStatus]['text'] . '</span>';
            } elseif (!empty($myStatus)) {
              // fallback إذا ظهرت قيمة غير متوقعة
              $myStatusBadgeHtml = '<span class="badge b-closed">' . htmlspecialchars($myStatus) . '</span>';
            }
            ?>

            <?php
            $seats = (int) $t['seats'];
            $occupied = (int) $t['occupied'];
            $left = max(0, $seats - $occupied);

            $isOpen = ($t['status'] === 'open');
            $myStatus = $t['my_status']; // pending/accepted/rejected/completed/null
        
            $badge = $isOpen
              ? '<span class="badge b-open">مفتوح</span>'
              : '<span class="badge b-closed">مغلق</span>';

            // السماح بالتقديم فقط إذا:
            // - المتدرب مسجل
            // - لا يوجد له طلب قائم (pending/accepted)
            // - لم يقدم مسبقاً على نفس التدريب
            // - التدريب مفتوح وفيه مقاعد
            $canApply = (
              isset($_SESSION['user_id'], $_SESSION['role']) &&
              $_SESSION['role'] === 'trainee' &&
              $traineeId !== null &&
              empty($activeApp) &&
              empty($myStatus) &&
              $isOpen &&
              $left > 0
            );
            ?>

            <div class="training-card">
              <div class="training-head">
                <div class="training-title"><?= htmlspecialchars($t['title']) ?></div>
                <div><?= $badge ?></div>
              </div>

              <div class="meta">
                <?php if (!empty($t['description'])): ?>
                  <div><strong>الوصف:</strong> <?= nl2br(htmlspecialchars($t['description'])) ?></div>
                <?php endif; ?>

                <div><strong>المدة (بالأشهر):</strong> <?= htmlspecialchars($t['duration_months'] ?? '-') ?></div>
                <div><strong>الموقع:</strong> <?= htmlspecialchars($t['location'] ?? '-') ?></div>
                <div><strong>تاريخ البداية:</strong> <?= htmlspecialchars($t['start_date'] ?? '-') ?></div>
                <div><strong>تاريخ النهاية:</strong> <?= htmlspecialchars($t['end_date'] ?? '-') ?></div>

                <div class="meta">
                  <span class="badge b-seats">المقاعد: <?= $seats ?></span>
                  <span class="badge b-seats">المشغولة: <?= $occupied ?></span>
                  <span class="badge b-seats">المتبقي: <?= $left ?></span>
                </div>


                <div class="actions">
                  <?php if (!isset($_SESSION['user_id'])): ?>
                    <span class="status-note">يجب تسجيل الدخول كمتدرب للتقديم.</span>
                    <a href="../../auth/login.php" class="btn-apply">تسجيل الدخول</a>

                  <?php elseif ($_SESSION['role'] !== 'trainee'): ?>
                    <span class="status-note">التقديم متاح فقط لحسابات المتدربين.</span>

                  <?php else: ?>

                    <?php if (!empty($myStatusBadgeHtml)): ?>
                      <span class="status-note">حالة طلبك: <?= $myStatusBadgeHtml ?></span>
                      <a class="btn-disabled" href="javascript:void(0)">تم التقديم</a>

                    <?php elseif (!$isOpen): ?>
                      <span class="status-note">هذا التدريب مغلق حالياً.</span>
                      <a class="btn-disabled" href="javascript:void(0)">غير متاح</a>

                    <?php elseif ($left <= 0): ?>
                      <span class="status-note">لا توجد مقاعد متاحة حالياً لهذا التدريب.</span>
                      <a class="btn-disabled" href="javascript:void(0)">مكتمل المقاعد</a>

                    <?php else: ?>
                      <a class="btn-apply" href="apply_training.php?training_id=<?= (int) $t['training_id'] ?>">
                        التقديم على هذا التدريب
                      </a>
                    <?php endif; ?>

                  <?php endif; ?>
                </div>

              </div>
            </div>
          <?php endforeach; ?>

        <?php endif; ?>


      </div>
    </div>

  </div>

<?php include("../../includes/footer.php"); ?>

</body>

</html>