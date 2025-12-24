<?php
session_start();
require_once("config/db.php");

// التحقق من أن المستخدم مسجل دخول
if (!isset($_SESSION['user_id'])) {
    header("Location: ./auth/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// دالة رفع ملف آمنة
function uploadDoc($fieldName, $userId, $prefix, $maxSize = 5242880) { // 5MB
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null]; // لم يرفع شيء
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return [null, "حدث خطأ أثناء رفع الملف."];
    }

    $tmp  = $_FILES[$fieldName]['tmp_name'];
    $name = $_FILES[$fieldName]['name'];
    $size = (int)$_FILES[$fieldName]['size'];

    if ($size <= 0 || $size > $maxSize) {
        return [null, "حجم الملف غير مسموح (الحد الأقصى 5MB)."];
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!in_array($ext, $allowedExt, true)) {
        return [null, "نوع الملف غير مسموح. المسموح: PDF / JPG / PNG."];
    }

    // تحقق MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($tmp);
    $allowedMime = ['application/pdf', 'image/jpeg', 'image/png'];

    if (!in_array($mime, $allowedMime, true)) {
        return [null, "محتوى الملف غير مسموح (MIME غير صحيح)."];
    }

    // إنشاء مسار الحفظ
    $baseDirAbs = __DIR__ . "/uploads/user_" . $userId . "/docs";
    if (!is_dir($baseDirAbs)) {
        mkdir($baseDirAbs, 0755, true);
    }

    $safeName = $prefix . "_" . date("Ymd_His") . "_" . bin2hex(random_bytes(6)) . "." . $ext;
    $destAbs  = $baseDirAbs . "/" . $safeName;

    if (!move_uploaded_file($tmp, $destAbs)) {
        return [null, "فشل حفظ الملف على السيرفر."];
    }

    // مسار نسبي للتخزين في DB
    $destRel = "uploads/user_" . $userId . "/docs/" . $safeName;

    return [$destRel, null];
}

// جلب بيانات المستخدم
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("المستخدم غير موجود.");
}

// جلب بيانات الدور (متدرب/مزاول)
$roleRow = null;
$role = $user['role'];

if ($role === 'lawyer') {
    $stmt2 = $pdo->prepare("SELECT * FROM lawyers WHERE user_id = ? LIMIT 1");
    $stmt2->execute([$user_id]);
    $roleRow = $stmt2->fetch(PDO::FETCH_ASSOC);
} elseif ($role === 'trainee') {
    $stmt2 = $pdo->prepare("SELECT * FROM trainees WHERE user_id = ? LIMIT 1");
    $stmt2->execute([$user_id]);
    $roleRow = $stmt2->fetch(PDO::FETCH_ASSOC);
}

$message = "";

// معالجة رفع الملفات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!in_array($role, ['trainee', 'lawyer'], true)) {
        $message = "<p style='color:red;'>رفع الوثائق غير متاح لهذا النوع من الحساب.</p>";
    } else {
        try {
            if (!$roleRow) {
                throw new Exception("لا يوجد سجل مرتبط بهذا المستخدم في جدول الدور.");
            }

            // رفع الملفات (مسموح رفع واحد أو الاثنين)
            [$noConvPath, $err1] = uploadDoc('no_conviction_doc', $user_id, 'no_conviction');
            if ($err1) throw new Exception($err1);

            [$goodPath, $err2] = uploadDoc('good_conduct_doc', $user_id, 'good_conduct');
            if ($err2) throw new Exception($err2);

            // إذا لم يرفع أي ملف
            if ($noConvPath === null && $goodPath === null) {
                throw new Exception("لم يتم اختيار أي ملف للرفع.");
            }

            $pdo->beginTransaction();

            // تحديث الجدول المناسب
            if ($role === 'lawyer') {
                $lawyerId = (int)$roleRow['lawyer_id'];

                // نبقي القديم إذا لم يتم رفع جديد
                $newNoConv = $noConvPath ?? $roleRow['no_conviction_doc'];
                $newGood   = $goodPath   ?? $roleRow['good_conduct_doc'];

                $up = $pdo->prepare("
                    UPDATE lawyers
                    SET no_conviction_doc = ?, good_conduct_doc = ?, updated_at = NOW()
                    WHERE lawyer_id = ?
                ");
                $up->execute([$newNoConv, $newGood, $lawyerId]);

            } else { // trainee
                $traineeId = (int)$roleRow['trainee_id'];

                $newNoConv = $noConvPath ?? $roleRow['no_conviction_doc'];
                $newGood   = $goodPath   ?? $roleRow['good_conduct_doc'];

                $up = $pdo->prepare("
                    UPDATE trainees
                    SET no_conviction_doc = ?, good_conduct_doc = ?, updated_at = NOW()
                    WHERE trainee_id = ?
                ");
                $up->execute([$newNoConv, $newGood, $traineeId]);
            }

            // تحديث حالة اكتمال الملف في users إذا أصبحت الوثائق مكتملة
            $finalNo = $noConvPath ?? ($roleRow['no_conviction_doc'] ?? null);
            $finalGood = $goodPath ?? ($roleRow['good_conduct_doc'] ?? null);

            if (!empty($finalNo) && !empty($finalGood)) {
                $upUser = $pdo->prepare("
                    UPDATE users
                    SET profile_completed = 1,
                        profile_completed_at = COALESCE(profile_completed_at, NOW())
                    WHERE user_id = ?
                ");
                $upUser->execute([$user_id]);
            }

            $pdo->commit();
            $message = "<p style='color:green;'>تم رفع/تحديث الوثائق بنجاح.</p>";

            // إعادة جلب البيانات بعد التحديث
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($role === 'lawyer') {
                $stmt2->execute([$user_id]);
                $roleRow = $stmt2->fetch(PDO::FETCH_ASSOC);
            } elseif ($role === 'trainee') {
                $stmt2->execute([$user_id]);
                $roleRow = $stmt2->fetch(PDO::FETCH_ASSOC);
            }

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "<p style='color:red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

$role_ar = ($role === 'lawyer') ? 'محامي مزاول' : (($role === 'trainee') ? 'متدرب' : 'مدير');

// حالات الوثائق للعرض
$hasNoConv = ($roleRow && !empty($roleRow['no_conviction_doc']));
$hasGood   = ($roleRow && !empty($roleRow['good_conduct_doc']));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>الملف الشخصي | <?= htmlspecialchars($user['full_name']) ?></title>
  <link rel="stylesheet" href="assets/css/style.css"></head>
<body>

<?php include("includes/header.php"); ?>

<div class="container profile-page">
  <h2>الملف الشخصي</h2>

  <div class="profile-card">
    <?= $message ?>

    <?php if ((int)($user['profile_completed'] ?? 0) === 0 && in_array($role, ['trainee','lawyer'], true)): ?>
      <p class="alert alert-error">
        حسابك غير مكتمل. يرجى رفع وثائق عدم المحكومية وحسن السيرة والسلوك لإكمال الملف.
      </p>
    <?php endif; ?>

    <p><strong>الاسم الكامل:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
    <p><strong>الرقم الوطني:</strong> <?= htmlspecialchars($user['national_id']) ?></p>
    <p><strong>رقم الهاتف:</strong> <?= htmlspecialchars($user['phone']) ?></p>
    <p><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <p><strong>العنوان:</strong> <?= htmlspecialchars($user['address']) ?></p>
    <p><strong>نوع الحساب:</strong> <?= $role_ar ?></p>

    <a href="edit_profile.php" class="edit-btn">تعديل الملف الشخصي</a>

    <?php if ($role === 'lawyer' && $roleRow): ?>
      <hr>
      <h3>معلومات المزاول</h3>
      <p><strong>رقم السجل:</strong> <?= htmlspecialchars($roleRow['syndicate_id']) ?></p>
      <p><strong>عنوان المكتب:</strong> <?= htmlspecialchars($roleRow['office_address']) ?></p>
      <p><strong>الحالة:</strong> <?= ((int)$roleRow['verified'] === 1) ? 'موثّق' : 'قيد التحقق' ?></p>
    <?php endif; ?>

    <?php if (in_array($role, ['trainee','lawyer'], true)): ?>
      <div class="doc-card">
        <h3>وثائق الملف الشخصي</h3>

        <div class="doc-row">
          <div class="doc-col">
            <p>
              <strong>عدم المحكومية:</strong>
              <?= $hasNoConv ? '<span class="badge ok">مرفوع</span>' : '<span class="badge no">غير مرفوع</span>' ?>
            </p>
          </div>
          <div class="doc-col">
            <p>
              <strong>حسن السيرة والسلوك:</strong>
              <?= $hasGood ? '<span class="badge ok">مرفوع</span>' : '<span class="badge no">غير مرفوع</span>' ?>
            </p>
          </div>
        </div>

        <p class="note">الصيغ المسموحة: PDF/JPG/PNG — الحد الأقصى 5MB لكل ملف.</p>

        <form class="doc-form" method="POST" enctype="multipart/form-data">
          <label>رفع/تحديث وثيقة عدم المحكومية</label>
          <input type="file" name="no_conviction_doc" accept=".pdf,.jpg,.jpeg,.png">

          <label style="margin-top:10px;">رفع/تحديث وثيقة حسن السيرة والسلوك</label>
          <input type="file" name="good_conduct_doc" accept=".pdf,.jpg,.jpeg,.png">

          <button type="submit">حفظ الوثائق</button>
        </form>

        <?php if ($hasNoConv || $hasGood): ?>
          <div class="doc-actions">
            <?php if ($hasNoConv): ?>
              <a target="_blank" href="/mutadarrib/my_doc_download.php?doc=no_conviction&disp=inline">عرض عدم المحكومية</a>
              <a class="dl" href="/mutadarrib/my_doc_download.php?doc=no_conviction&disp=attachment">تنزيل</a>
            <?php endif; ?>

            <?php if ($hasGood): ?>
              <a target="_blank" href="/mutadarrib/my_doc_download.php?doc=good_conduct&disp=inline">عرض حسن السيرة</a>
              <a class="dl" href="/mutadarrib/my_doc_download.php?doc=good_conduct&disp=attachment">تنزيل</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <a href="./auth/logout.php" class="logout-btn">تسجيل الخروج</a>
  </div>
</div>

<?php include("includes/footer.php"); ?>

</body>
</html>
