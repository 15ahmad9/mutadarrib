<?php
session_start();
require_once("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: students_master.php"); exit; }

// جلب بيانات الطالب + المستخدم
$stmt = $pdo->prepare("
    SELECT s.*, u.*
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    WHERE s.student_id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) die("لا يوجد طالب بهذا الرقم.");

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name       = trim($_POST['first_name']);
    $father_name      = trim($_POST['father_name']);
    $grandfather_name = trim($_POST['grandfather_name']);
    $family_name      = trim($_POST['family_name']);
    $full_name        = "$first_name $father_name $grandfather_name $family_name";

    $national_id      = $_POST['national_id'];
    $phone            = $_POST['phone'];
    $email            = $_POST['email'];
    $home_address          = $_POST['home_address'];
    $degree           = $_POST['university_degree'];
    $highschool       = $_POST['highschool_certificate'];
    $social           = $_POST['social_security'];
    $social_no        = $_POST['social_security_number'] ?? null;
    $password         = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;

    try {
        $pdo->beginTransaction();

        // تحديث المستخدم
        $sql = "UPDATE users SET 
            full_name=?, first_name=?, father_name=?, grandfather_name=?, family_name=?,
            national_id=?, phone=?, email=?, home_address=?";
        $params = [$full_name, $first_name, $father_name, $grandfather_name, $family_name,
                   $national_id, $phone, $email, $home_address];

        if ($password) {
            $sql .= ", password=?";
            $params[] = $password;
        }

        $sql .= " WHERE user_id=?";
        $params[] = $data['user_id'];

        $stmtU = $pdo->prepare($sql);
        $stmtU->execute($params);

        // تحديث الطالب
        $stmtS = $pdo->prepare("
            UPDATE students SET 
            highschool_certificate=?, university_degree=?, social_security=?, social_security_number=?
            WHERE student_id=?
        ");
        $stmtS->execute([$highschool, $degree, $social, $social_no, $id]);

        $pdo->commit();
        $message = "<p style='color:green;'>تم تعديل البيانات بنجاح!</p>";

        // تحديث البيانات المعروضة
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "<p style='color:red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>


<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تعديل بيانات الطالب</title>
<style>
.container { width:60%; margin:40px auto; background:#fff; padding:25px; border-radius:10px; }
input, select { width:100%; padding:8px; border-radius:6px; border:1px solid #ccc; margin-top:5px; }
button { margin-top:15px; padding:10px; background:#0077b6; color:white; border:none; border-radius:6px; }
</style>
</head>
<body>

<div class="container">

<h2>تعديل بيانات الطالب</h2>
<?= $message ?>

<form method="POST">

<label>الاسم الأول:</label>
<input type="text" name="first_name" value="<?= htmlspecialchars($data['first_name']) ?>" required>

<label>اسم الأب:</label>
<input type="text" name="father_name" value="<?= htmlspecialchars($data['father_name']) ?>" required>

<label>اسم الجد:</label>
<input type="text" name="grandfather_name" value="<?= htmlspecialchars($data['grandfather_name']) ?>" required>

<label>اسم العائلة:</label>
<input type="text" name="family_name" value="<?= htmlspecialchars($data['family_name']) ?>" required>

<label>الرقم الوطني:</label>
<input type="text" name="national_id" value="<?= htmlspecialchars($data['national_id']) ?>">

<label>الهاتف:</label>
<input type="text" name="phone" value="<?= htmlspecialchars($data['phone']) ?>">

<label>البريد الإلكتروني:</label>
<input type="email" name="email" value="<?= htmlspecialchars($data['email']) ?>">

<label>العنوان:</label>
<input type="text" name="home_address" value="<?= htmlspecialchars($data['home_address']) ?>">

<label>كلمة المرور (اختياري):</label>
<input type="password" name="password">

<hr>

<label>شهادة الثانوية:</label>
<select name="highschool_certificate">
  <option value="نعم" <?= $data['highschool_certificate']=="نعم"?"selected":"" ?>>نعم</option>
  <option value="لا" <?= $data['highschool_certificate']=="لا"?"selected":"" ?>>لا</option>
</select>

<label>الدرجة الجامعية:</label>
<select name="university_degree">
  <option value="بكالوريوس" <?= $data['university_degree']=="بكالوريوس"?"selected":"" ?>>بكالوريوس</option>
  <option value="ماجستير" <?= $data['university_degree']=="ماجستير"?"selected":"" ?>>ماجستير</option>
  <option value="دكتوراه" <?= $data['university_degree']=="دكتوراه"?"selected":"" ?>>دكتوراه</option>
</select>

<label>الضمان الاجتماعي:</label>
<select name="social_security" id="social_select" onchange="toggleField()">
  <option value="لا" <?= $data['social_security']=="لا"?"selected":"" ?>>لا</option>
  <option value="نعم" <?= $data['social_security']=="نعم"?"selected":"" ?>>نعم</option>
</select>

<label>رقم الضمان:</label>
<input type="text" name="social_security_number" id="social_input" value="<?= htmlspecialchars($data['social_security_number']) ?>">

<script>
function toggleField() {
    const select = document.getElementById('social_select');
    const input = document.getElementById('social_input');
    input.disabled = (select.value === "لا");
}
toggleField();
</script>

<button type="submit">حفظ التعديلات</button>

</form>

</div>
</body>
</html>
