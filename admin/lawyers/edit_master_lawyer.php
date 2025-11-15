<?php
session_start();
require_once("../../config/db.php");

// حماية الوصول: المدير فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: master_lawyers.php");
    exit;
}

// جلب بيانات المحامي مع بيانات المستخدم المرتبط (إن وجد)
$stmt = $pdo->prepare("
    SELECT lm.*, u.user_id, u.password AS user_password, l.lawyer_id
    FROM lawyers_master lm
    LEFT JOIN lawyers l ON lm.master_id = l.master_id
    LEFT JOIN users u ON l.user_id = u.user_id
    WHERE lm.master_id = ?
");
$stmt->execute([$id]);
$lawyer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lawyer) die("لم يتم العثور على المحامي.");

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name      = trim($_POST['first_name']);
    $father_name     = trim($_POST['father_name']);
    $grandfather_name= trim($_POST['grandfather_name']);
    $family_name     = trim($_POST['family_name']);
    $full_name       = "$first_name $father_name $grandfather_name $family_name";

    $national_id     = trim($_POST['national_id']);
    $phone           = trim($_POST['phone']);
    $email           = trim($_POST['email']);
    $home_address    = trim($_POST['home_address']);
    $office_address  = trim($_POST['office_address']);
    $highschool      = $_POST['highschool_certificate'] ?? 'لا';
    $university      = $_POST['university_degree'] ?? null;
    $no_conviction   = $_POST['no_conviction_doc'] ?? null;
    $good_conduct    = $_POST['good_conduct_doc'] ?? null;
    $social_security = $_POST['social_security'] ?? 'لا';
    $social_number   = $_POST['social_security_number'] ?? null;
    $password        = trim($_POST['password']);

    try {
        $pdo->beginTransaction();

        // تحديث جدول النقابة
        $stmt1 = $pdo->prepare("
            UPDATE lawyers_master SET
            full_name=?, first_name=?, father_name=?, grandfather_name=?, family_name=?,
            national_id=?, phone=?, email=?, office_address=?,
            highschool_certificate=?, university_degree=?, no_conviction_doc=?, good_conduct_doc=?,
            social_security=?, social_security_number=?
            WHERE master_id=?
        ");
        $stmt1->execute([
            $full_name, $first_name, $father_name, $grandfather_name, $family_name,
            $national_id, $phone, $email, $office_address,
            $highschool, $university, $no_conviction, $good_conduct,
            $social_security, $social_number, $id
        ]);

        // تحديث جدول users إذا موجود
        if ($lawyer['user_id']) {
            $fields = "full_name=?, first_name=?, father_name=?, grandfather_name=?, family_name=?,
                       national_id=?, phone=?, email=?, home_address=?, office_address=?,
                       highschool_certificate=?, university_degree=?, no_conviction_doc=?, good_conduct_doc=?,
                       social_security=?, social_security_number=?";
            $params = [
                $full_name, $first_name, $father_name, $grandfather_name, $family_name,
                $national_id, $phone, $email, $home_address, $office_address,
                $highschool, $university, $no_conviction, $good_conduct,
                $social_security, $social_number
            ];

            if (!empty($password)) {
                $fields .= ", password=?";
                $params[] = password_hash($password, PASSWORD_BCRYPT);
            }
            $params[] = $lawyer['user_id'];

            $stmt2 = $pdo->prepare("UPDATE users SET $fields WHERE user_id=?");
            $stmt2->execute($params);

            // تحديث جدول lawyers إذا موجود
            if ($lawyer['lawyer_id']) {
                $stmt3 = $pdo->prepare("
                    UPDATE lawyers SET
                    full_name=?, first_name=?, father_name=?, grandfather_name=?, family_name=?,
                    national_id=?, phone=?, email=?, home_address=?, office_address=?
                    WHERE lawyer_id=?
                ");
                $stmt3->execute([
                    $full_name, $first_name, $father_name, $grandfather_name, $family_name,
                    $national_id, $phone, $email, $home_address, $office_address,
                    $lawyer['lawyer_id']
                ]);
            }
        }

        $pdo->commit();
        $message = "<p style='color:green;'>تم تحديث بيانات المحامي بنجاح!</p>";

        // إعادة تحميل البيانات
        $stmt->execute([$id]);
        $lawyer = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "<p style='color:red;'>خطأ: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>


<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تعديل بيانات المحامي</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.container { width: 60%; margin: 50px auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 3px 6px rgba(0,0,0,0.1);}
label { display: block; margin-top: 10px;}
input[type="text"], input[type="email"], input[type="password"], button {
  width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc; margin-top: 5px;
}
button { margin-top: 15px; padding: 10px; background-color: #0077b6; color: white; border: none; border-radius: 6px; cursor: pointer;}
button:hover { opacity: 0.9; }
</style>
</head>
<body>

<?php include("../includes/header.php"); ?>

<div class="container">
  <h2>تعديل بيانات المحامي</h2>
  <?= $message ?>
  <form method="POST">
  <label>الاسم الأول:</label>
  <input type="text" name="first_name" value="<?= htmlspecialchars($lawyer['first_name']) ?>" required>

  <label>اسم الأب:</label>
  <input type="text" name="father_name" value="<?= htmlspecialchars($lawyer['father_name']) ?>" required>

  <label>اسم الجد:</label>
  <input type="text" name="grandfather_name" value="<?= htmlspecialchars($lawyer['grandfather_name']) ?>" required>

  <label>اسم العائلة:</label>
  <input type="text" name="family_name" value="<?= htmlspecialchars($lawyer['family_name']) ?>" required>

  <label>الرقم الوطني:</label>
  <input type="text" name="national_id" value="<?= htmlspecialchars($lawyer['national_id']) ?>" required>

  <label>رقم الهاتف:</label>
  <input type="text" name="phone" value="<?= htmlspecialchars($lawyer['phone']) ?>">

  <label>البريد الإلكتروني:</label>
  <input type="email" name="email" value="<?= htmlspecialchars($lawyer['email']) ?>">

  <label>عنوان السكن:</label>
  <input type="text" name="home_address" value="<?= htmlspecialchars($lawyer['home_address'] ?? '') ?>">

  <label>عنوان المكتب:</label>
  <input type="text" name="office_address" value="<?= htmlspecialchars($lawyer['office_address']) ?>">

  <label>شهادة ثانوية:</label>
  <select name="highschool_certificate">
      <option value="نعم" <?= $lawyer['highschool_certificate']=='نعم'?'selected':'' ?>>نعم</option>
      <option value="لا" <?= $lawyer['highschool_certificate']=='لا'?'selected':'' ?>>لا</option>
  </select>

  <label>شهادة جامعية:</label>
  <select name="university_degree">
      <option value="">---</option>
      <option value="بكالوريوس" <?= $lawyer['university_degree']=='بكالوريوس'?'selected':'' ?>>بكالوريوس</option>
      <option value="ماجستير" <?= $lawyer['university_degree']=='ماجستير'?'selected':'' ?>>ماجستير</option>
      <option value="دكتوراه" <?= $lawyer['university_degree']=='دكتوراه'?'selected':'' ?>>دكتوراه</option>
  </select>

  <label>عدم محكومية (رابط الصورة):</label>
  <input type="text" name="no_conviction_doc" value="<?= htmlspecialchars($lawyer['no_conviction_doc']) ?>">

  <label>حسن السيرة والسلوك (رابط الصورة):</label>
  <input type="text" name="good_conduct_doc" value="<?= htmlspecialchars($lawyer['good_conduct_doc']) ?>">

  <label>الضمان الاجتماعي:</label>
  <select name="social_security" id="social_security" onchange="toggleSocialNumber()">
      <option value="نعم" <?= $lawyer['social_security']=='نعم'?'selected':'' ?>>نعم</option>
      <option value="لا" <?= $lawyer['social_security']=='لا'?'selected':'' ?>>لا</option>
  </select>

  <label>رقم الضمان الاجتماعي:</label>
  <input type="text" name="social_security_number" id="social_number" value="<?= htmlspecialchars($lawyer['social_security_number']) ?>" <?= $lawyer['social_security']!='نعم'?'disabled':'' ?>>

  <label>كلمة المرور (اتركها فارغة إذا لا تريد تغييرها):</label>
  <input type="password" name="password">

  <button type="submit">💾 حفظ التعديلات</button>
</form>

<script>
function toggleSocialNumber() {
    const ss = document.getElementById('social_security');
    const num = document.getElementById('social_number');
    num.disabled = (ss.value !== 'نعم');
}
</script>


  <p><a href="master_lawyers.php">⬅العودة إلى القائمة</a></p>
</div>
</body>
</html>
