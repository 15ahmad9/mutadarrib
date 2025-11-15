<?php
session_start();
require_once("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name       = trim($_POST['first_name']);
    $father_name      = trim($_POST['father_name']);
    $grandfather_name = trim($_POST['grandfather_name']);
    $family_name      = trim($_POST['family_name']);
    $full_name        = "$first_name $father_name $grandfather_name $family_name";

    $national_id      = trim($_POST['national_id']);
    $phone            = trim($_POST['phone']);
    $email            = trim($_POST['email']);
    $home_address     = trim($_POST['home_address']);
    $password_hashed  = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $degree           = $_POST['university_degree'];
    $highschool       = $_POST['highschool_certificate'];
    $social           = $_POST['social_security'];
    $social_no        = !empty($_POST['social_security_number']) ? $_POST['social_security_number'] : null;

    if ($social === "نعم" && empty($social_no)) {
        $message = "<p style='color:red;'>يجب إدخال رقم الضمان الاجتماعي.</p>";
    } else {
        try {
            $pdo->beginTransaction();

            // إضافة المستخدم
            $stmt = $pdo->prepare("
                INSERT INTO users 
                (full_name, first_name, father_name, grandfather_name, family_name, national_id, phone, email, home_address, password, role)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'student')
            ");
            $stmt->execute([$full_name, $first_name, $father_name, $grandfather_name, $family_name, $national_id, $phone, $email, $home_address, $password_hashed]);
            $user_id = $pdo->lastInsertId();

            // إضافة الطالب
            $stmt2 = $pdo->prepare("
                INSERT INTO students 
                (user_id, highschool_certificate, university_degree, social_security, social_security_number)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt2->execute([$user_id, $highschool, $degree, $social, $social_no]);

            $pdo->commit();
            $message = "<p style='color:green;'>تم إضافة الطالب بنجاح!</p>";

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
<title>إضافة طالب جديد</title>
<style>
.container { width:60%; margin:40px auto; background:#fff; padding:25px; border-radius:10px; }
input, select { width:100%; padding:8px; margin-top:5px; border:1px solid #ccc; border-radius:6px; }
button { margin-top:15px; padding:10px; background:#0077b6; color:white; border:none; border-radius:6px; }
</style>
</head>
<body>

<div class="container">
<h2>➕ إضافة طالب جديد</h2>
<?= $message ?>

<form method="POST">

<h3>بيانات الطالب</h3>

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

<label>الهاتف:</label>
<input type="text" name="phone">

<label>البريد الإلكتروني:</label>
<input type="email" name="email">

<label>عنوان السكن:</label>
<input type="text" name="home_address">

<label>كلمة المرور:</label>
<input type="password" name="password" required>

<hr>

<label>شهادة الثانوية:</label>
<select name="highschool_certificate">
  <option value="نعم">نعم</option>
  <option value="لا">لا</option>
</select>

<label>الدرجة الجامعية:</label>
<select name="university_degree">
  <option value="بكالوريوس">بكالوريوس</option>
  <option value="ماجستير">ماجستير</option>
  <option value="دكتوراه">دكتوراه</option>
</select>

<label>الضمان الاجتماعي:</label>
<select name="social_security" id="social_select" onchange="toggleField()">
  <option value="لا">لا</option>
  <option value="نعم">نعم</option>
</select>

<label>رقم الضمان:</label>
<input type="text" name="social_security_number" id="social_input" disabled>

<script>
function toggleField() {
    const select = document.getElementById('social_select');
    const input = document.getElementById('social_input');
    input.disabled = (select.value === "لا");
}
</script>

<button type="submit">حفظ</button>

</form>
</div>

</body>
</html>
