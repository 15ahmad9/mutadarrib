<?php
session_start();
require_once("../../config/db.php");
include("../includes/header.php");

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$syndicate_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($syndicate_id <= 0) {
    header("Location: syndicate_lawyers.php");
    exit;
}

/* 1) Fetch syndicate record (authoritative) */
$stmtSyn = $pdo->prepare("SELECT * FROM lawyers_syndicate WHERE syndicate_id = ? LIMIT 1");
$stmtSyn->execute([$syndicate_id]);
$syn = $stmtSyn->fetch(PDO::FETCH_ASSOC);
if (!$syn) die("لم يتم العثور على المحامي في سجل النقابة.");

/* 2) Fetch lawyers record by syndicate_id; fallback to national_id ONLY if unique */
$law = null;
$stmtLaw = $pdo->prepare("SELECT * FROM lawyers WHERE syndicate_id = ? LIMIT 1");
$stmtLaw->execute([$syndicate_id]);
$law = $stmtLaw->fetch(PDO::FETCH_ASSOC);

$ambiguousLawyer = false;
if (!$law && !empty($syn['national_id'])) {
    $stmtLaw2 = $pdo->prepare("SELECT * FROM lawyers WHERE national_id = ? LIMIT 2");
    $stmtLaw2->execute([$syn['national_id']]);
    $tmp = $stmtLaw2->fetchAll(PDO::FETCH_ASSOC);
    if (count($tmp) === 1) $law = $tmp[0];
    if (count($tmp) > 1)  $ambiguousLawyer = true;
}

/* 3) Fetch users if possible (lawyers.php depends on users) */
$usr = null;
if (!empty($law['user_id'])) {
    $stmtUsr = $pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
    $stmtUsr->execute([(int)$law['user_id']]);
    $usr = $stmtUsr->fetch(PDO::FETCH_ASSOC);
}

/* 4) Build view model with fallbacks */
$view = $syn;
$view['lawyer_id'] = $law['lawyer_id'] ?? null;
$view['user_id']   = $usr['user_id'] ?? ($law['user_id'] ?? null);

$view['home_address']   = $law['home_address'] ?? ($usr['address'] ?? '');
$view['office_address'] = $syn['office_address'] ?? ($law['office_address'] ?? '');

$view['phone'] = $syn['phone'] ?: ($usr['phone'] ?? ($law['phone'] ?? ''));
$view['email'] = $syn['email'] ?: ($usr['email'] ?? ($law['email'] ?? ''));

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name       = trim($_POST['first_name'] ?? '');
    $father_name      = trim($_POST['father_name'] ?? '');
    $grandfather_name = trim($_POST['grandfather_name'] ?? '');
    $family_name      = trim($_POST['family_name'] ?? '');
    $full_name        = preg_replace('/\s+/', ' ', trim("$first_name $father_name $grandfather_name $family_name"));

    $national_id      = trim($_POST['national_id'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $home_address     = trim($_POST['home_address'] ?? '');
    $office_address   = trim($_POST['office_address'] ?? '');

    $highschool       = $_POST['highschool_certificate'] ?? 'لا';
    $university       = $_POST['university_degree'] ?? null;
    $no_conviction    = trim($_POST['no_conviction_doc'] ?? '');
    $good_conduct     = trim($_POST['good_conduct_doc'] ?? '');

    $social_security  = $_POST['social_security'] ?? 'لا';
    $social_number    = $_POST['social_security_number'] ?? null;
    if ($social_security !== 'نعم') {
        $social_number = null;
    } else {
        $social_number = trim((string)$social_number);
        if ($social_number === '') $social_number = null;
    }

    $password_plain = trim($_POST['password'] ?? '');
    $password_hash  = ($password_plain !== '') ? password_hash($password_plain, PASSWORD_BCRYPT) : null;

    if ($full_name === '' || $national_id === '') {
        $message = "<p style='color:red;'>الاسم الكامل والرقم الوطني حقول إلزامية.</p>";
    } elseif ($ambiguousLawyer) {
        $message = "<p style='color:red;'>يوجد أكثر من سجل في جدول lawyers بنفس الرقم الوطني. يجب تصحيح البيانات قبل مزامنة الحساب.</p>";
    } else {
        try {
            $pdo->beginTransaction();

            /* A) Update lawyers_syndicate */
            $upSyn = $pdo->prepare("
                UPDATE lawyers_syndicate SET
                    lawyer_name            = ?,
                    full_name              = ?,
                    first_name             = ?,
                    father_name            = ?,
                    grandfather_name       = ?,
                    family_name            = ?,
                    national_id            = ?,
                    phone                  = ?,
                    email                  = ?,
                    office_address         = ?,
                    highschool_certificate = ?,
                    university_degree      = ?,
                    no_conviction_doc      = ?,
                    good_conduct_doc       = ?,
                    social_security        = ?,
                    social_security_number = ?
                WHERE syndicate_id = ?
            ");
            $upSyn->execute([
                $full_name,
                $full_name, $first_name, $father_name, $grandfather_name, $family_name,
                $national_id, $phone, $email, $office_address,
                $highschool, $university,
                ($no_conviction !== '' ? $no_conviction : null),
                ($good_conduct  !== '' ? $good_conduct  : null),
                $social_security, $social_number,
                $syndicate_id
            ]);

            /* B) Update users (so lawyers.php reflects changes) */
            if (!empty($view['user_id'])) {
                $fields = "full_name=?, national_id=?, phone=?, email=?, address=?";
                $params = [$full_name, $national_id, $phone, $email, $home_address];

                if ($password_hash) {
                    $fields .= ", password=?";
                    $params[] = $password_hash;
                }

                $params[] = (int)$view['user_id'];
                $upUsr = $pdo->prepare("UPDATE users SET $fields WHERE user_id=?");
                $upUsr->execute($params);
            }

            /* C) Update lawyers (second table requested) */
            if (!empty($view['lawyer_id'])) {
                $lawFields = "
                    syndicate_id           = ?,
                    office_address         = ?,
                    full_name              = ?,
                    first_name             = ?,
                    father_name            = ?,
                    grandfather_name       = ?,
                    family_name            = ?,
                    national_id            = ?,
                    phone                  = ?,
                    email                  = ?,
                    home_address           = ?,
                    no_conviction_doc      = ?,
                    good_conduct_doc       = ?,
                    social_security        = ?,
                    highschool_certificate = ?,
                    university_degree      = ?,
                    social_security_number = ?
                ";

                $lawParams = [
                    $syndicate_id,
                    ($office_address !== '' ? $office_address : null),
                    $full_name, $first_name, $father_name, $grandfather_name, $family_name,
                    $national_id, $phone, $email, $home_address,
                    ($no_conviction !== '' ? $no_conviction : null),
                    ($good_conduct  !== '' ? $good_conduct  : null),
                    $social_security, $highschool, $university, $social_number
                ];

                // Update password in lawyers too if provided (your login may rely on it)
                if ($password_hash) {
                    $lawFields .= ", password=?";
                    $lawParams[] = $password_hash;
                }

                $lawParams[] = (int)$view['lawyer_id'];

                $upLaw = $pdo->prepare("UPDATE lawyers SET $lawFields WHERE lawyer_id=?");
                $upLaw->execute($lawParams);

            } else {
                // If no lawyers record linked, we still keep syndicate updated.
                // Optionally you can create a lawyers record here if needed.
            }

            $pdo->commit();
            $message = "<p style='color:green;'>تم تحديث بيانات المحامي بنجاح.</p>";

            // Reload
            $stmtSyn->execute([$syndicate_id]);
            $syn = $stmtSyn->fetch(PDO::FETCH_ASSOC);

            $stmtLaw->execute([$syndicate_id]);
            $law = $stmtLaw->fetch(PDO::FETCH_ASSOC);

            $usr = null;
            if (!empty($law['user_id'])) {
                $stmtUsr = $pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
                $stmtUsr->execute([(int)$law['user_id']]);
                $usr = $stmtUsr->fetch(PDO::FETCH_ASSOC);
            }

            $view = $syn;
            $view['lawyer_id'] = $law['lawyer_id'] ?? null;
            $view['user_id']   = $usr['user_id'] ?? ($law['user_id'] ?? null);
            $view['home_address']   = $law['home_address'] ?? ($usr['address'] ?? '');
            $view['office_address'] = $syn['office_address'] ?? ($law['office_address'] ?? '');
            $view['phone'] = $syn['phone'] ?: ($usr['phone'] ?? ($law['phone'] ?? ''));
            $view['email'] = $syn['email'] ?: ($usr['email'] ?? ($law['email'] ?? ''));

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "<p style='color:red;'>خطأ: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تعديل بيانات المحامي</title>
<link rel="stylesheet" href="../../assets/css/admin.css"></head>
<body>

<div class="admin-container">
<?php include("../includes/sidebar.php"); ?>

<div class="container">
  <h2>تعديل بيانات المحامي</h2>
  <?= $message ?>

  <form method="POST">
    <label>الاسم الأول</label>
    <input type="text" name="first_name" value="<?= htmlspecialchars($view['first_name'] ?? '') ?>" required>

    <label>اسم الأب</label>
    <input type="text" name="father_name" value="<?= htmlspecialchars($view['father_name'] ?? '') ?>" required>

    <label>اسم الجد</label>
    <input type="text" name="grandfather_name" value="<?= htmlspecialchars($view['grandfather_name'] ?? '') ?>" required>

    <label>اسم العائلة</label>
    <input type="text" name="family_name" value="<?= htmlspecialchars($view['family_name'] ?? '') ?>" required>

    <label>الرقم الوطني</label>
    <input type="text" name="national_id" value="<?= htmlspecialchars($view['national_id'] ?? '') ?>" required>

    <label>رقم الهاتف</label>
    <input type="text" name="phone" value="<?= htmlspecialchars($view['phone'] ?? '') ?>">

    <label>البريد الإلكتروني</label>
    <input type="email" name="email" value="<?= htmlspecialchars($view['email'] ?? '') ?>">

    <label>عنوان السكن</label>
    <input type="text" name="home_address" value="<?= htmlspecialchars($view['home_address'] ?? '') ?>">

    <label>عنوان المكتب</label>
    <input type="text" name="office_address" value="<?= htmlspecialchars($view['office_address'] ?? '') ?>">

    <label>شهادة ثانوية</label>
    <select name="highschool_certificate">
      <option value="نعم" <?= ($view['highschool_certificate'] ?? 'لا')=='نعم'?'selected':'' ?>>نعم</option>
      <option value="لا"  <?= ($view['highschool_certificate'] ?? 'لا')=='لا'?'selected':'' ?>>لا</option>
    </select>

    <label>الدرجة الجامعية</label>
    <select name="university_degree">
      <option value="">---</option>
      <option value="بكالوريوس" <?= ($view['university_degree'] ?? '')=='بكالوريوس'?'selected':'' ?>>بكالوريوس</option>
      <option value="ماجستير"   <?= ($view['university_degree'] ?? '')=='ماجستير'?'selected':'' ?>>ماجستير</option>
      <option value="دكتوراه"   <?= ($view['university_degree'] ?? '')=='دكتوراه'?'selected':'' ?>>دكتوراه</option>
    </select>

    <label>عدم محكومية (رابط/مسار)</label>
    <input type="text" name="no_conviction_doc" value="<?= htmlspecialchars($view['no_conviction_doc'] ?? '') ?>">

    <label>حسن السيرة والسلوك (رابط/مسار)</label>
    <input type="text" name="good_conduct_doc" value="<?= htmlspecialchars($view['good_conduct_doc'] ?? '') ?>">

    <label>الضمان الاجتماعي</label>
    <select name="social_security" id="social_security" onchange="toggleSocialNumber()">
      <option value="نعم" <?= ($view['social_security'] ?? 'لا')=='نعم'?'selected':'' ?>>نعم</option>
      <option value="لا"  <?= ($view['social_security'] ?? 'لا')=='لا'?'selected':'' ?>>لا</option>
    </select>

    <label>رقم الضمان الاجتماعي</label>
    <input type="text" name="social_security_number" id="social_number"
           value="<?= htmlspecialchars($view['social_security_number'] ?? '') ?>"
           <?= (($view['social_security'] ?? 'لا')!='نعم')?'disabled':'' ?>>

    <label>كلمة المرور (اتركها فارغة إذا لا تريد تغييرها)</label>
    <input type="password" name="password">

    <button type="submit">حفظ التعديلات</button>
  </form>

  <script>
    function toggleSocialNumber() {
      const ss = document.getElementById('social_security');
      const num = document.getElementById('social_number');
      num.disabled = (ss.value !== 'نعم');
      if (num.disabled) num.value = '';
    }
  </script>

  <p><a href="syndicate_lawyers.php">العودة إلى قائمة سجل النقابة</a></p>
</div>

</div>
</body>
</html>
