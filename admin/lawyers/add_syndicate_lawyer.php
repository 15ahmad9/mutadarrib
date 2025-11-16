<?php
session_start();
require_once("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

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
    $password_hashed = password_hash($password, PASSWORD_BCRYPT);

    try {
        // التأكد من عدم وجود الرقم الوطني مسبقًا
        $check = $pdo->prepare("SELECT * FROM lawyers_syndicate WHERE national_id = ?");
        $check->execute([$national_id]);
        if ($check->rowCount() > 0) {
            $message = "<p style='color:red;'>المحامي موجود مسبقًا!</p>";
        } else {
            $pdo->beginTransaction();

            // إضافة في جدول النقابة
            $stmt1 = $pdo->prepare("
                INSERT INTO lawyers_syndicate 
                (full_name, first_name, father_name, grandfather_name, family_name, national_id, phone, email, office_address,
                 highschool_certificate, university_degree, no_conviction_doc, good_conduct_doc, social_security, social_security_number, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt1->execute([
                $full_name, $first_name, $father_name, $grandfather_name, $family_name,
                $national_id, $phone, $email, $office_address,
                $highschool, $university, $no_conviction, $good_conduct, $social_security, $social_number
            ]);
            $syndicate_id = $pdo->lastInsertId();

            // إضافة المستخدم
            $stmt2 = $pdo->prepare("
                INSERT INTO users
                (full_name, first_name, father_name, grandfather_name, family_name, national_id, phone, email, home_address, office_address,
                 highschool_certificate, university_degree, no_conviction_doc, good_conduct_doc, social_security, social_security_number, password, role)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'lawyer')
            ");
            $stmt2->execute([
                $full_name, $first_name, $father_name, $grandfather_name, $family_name,
                $national_id, $phone, $email, $home_address, $office_address,
                $highschool, $university, $no_conviction, $good_conduct, $social_security, $social_number, $password_hashed
            ]);
            $user_id = $pdo->lastInsertId();

            // إضافة المحامي في جدول lawyers
            $stmt3 = $pdo->prepare("
                INSERT INTO lawyers
                (full_name, first_name, father_name, grandfather_name, family_name, national_id, phone, email, home_address, office_address,
                 highschool_certificate, university_degree, no_conviction_doc, good_conduct_doc, social_security, social_security_number,
                 password, syndicate_id, user_id, verified, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt3->execute([
                $full_name, $first_name, $father_name, $grandfather_name, $family_name,
                $national_id, $phone, $email, $home_address, $office_address,
                $highschool, $university, $no_conviction, $good_conduct, $social_security, $social_number,
                $password_hashed, $syndicate_id, $user_id
            ]);

            $pdo->commit();
            $message = "<p style='color:green;'>تمت إضافة المحامي بنجاح!</p>";
        }

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
<title>إضافة محامي جديد</title>
<link rel="stylesheet" href="../../assets/css/admin.css">
<style>
form {
  width: 60%;
  margin: 40px auto;
  background: #fff;
  padding: 20px;
  border-radius: 10px;
}
input[type="text"], input[type="email"], input[type="password"], button {
  display: block;
  width: 100%;
  padding: 10px;
  margin-top: 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
}
button {
  background: #0077b6;
  color: white;
  border: none;
  cursor: pointer;
  font-weight: bold;
}
button:hover { opacity: 0.9; }
</style>
</head>
<body>

<h2 style="text-align:center;">إضافة محامي جديد إلى النظام</h2>
<div style="text-align:center;"><?= $message ?></div>

<form method="POST">
  <label>الاسم الأول:</label>
  <input type="text" name="first_name" required>

  <label>اسم الأب:</label>
  <input type="text" name="father_name" required>

  <label>اسم الجد:</label>
  <input type="text" name="grandfather_name" required>

  <label>اسم العائلة:</label>
  <input type="text" name="family_name" required>

  <label>الرقم الوطني:</label>
  <input type="text" name="national_id" required>

  <label>رقم الهاتف:</label>
  <input type="text" name="phone">

  <label>البريد الإلكتروني:</label>
  <input type="email" name="email" required>

  <label>عنوان السكن:</label>
  <input type="text" name="home_address">

  <label>عنوان المكتب:</label>
  <input type="text" name="office_address">

  <label>شهادة ثانوية:</label>
  <select name="highschool_certificate">
      <option value="نعم">نعم</option>
      <option value="لا" selected>لا</option>
  </select>

  <label>شهادة جامعية:</label>
  <select name="university_degree">
      <option value="">---</option>
      <option value="بكالوريوس">بكالوريوس</option>
      <option value="ماجستير">ماجستير</option>
      <option value="دكتوراه">دكتوراه</option>
  </select>

  <label>عدم محكومية (رابط الصورة):</label>
  <input type="text" name="no_conviction_doc">

  <label>حسن السيرة والسلوك (رابط الصورة):</label>
  <input type="text" name="good_conduct_doc">

  <label>الضمان الاجتماعي:</label>
  <select name="social_security" id="social_security" onchange="toggleSocialNumber()">
      <option value="لا" selected>لا</option>
      <option value="نعم">نعم</option>
  </select>

  <label>رقم الضمان الاجتماعي:</label>
  <input type="text" name="social_security_number" id="social_number" disabled>

  <label>كلمة المرور:</label>
  <input type="password" name="password" required>

  <button type="submit">حفظ البيانات</button>
</form>

<script>
function toggleSocialNumber() {
    const ss = document.getElementById('social_security');
    const num = document.getElementById('social_number');
    num.disabled = (ss.value !== 'نعم');
}
</script>


<a href="syndicate_lawyers.php" class="back-link">العودة إلى قائمة المحامين</a>

</body>
</html>