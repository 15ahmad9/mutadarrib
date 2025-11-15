<?php
require_once("../config/db.php");
$message = "";

// تسجيل البيانات عند الضغط على زر التسجيل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role        = $_POST['role'];
    $national_id = trim($_POST['national_id']);
    $password    = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // حقول المستخدم
    $full_name   = trim($_POST['first_name'].' '.$_POST['father_name'].' '.$_POST['grandfather_name'].' '.$_POST['family_name']);
    $first_name  = trim($_POST['first_name']);
    $father_name = trim($_POST['father_name']);
    $grandfather_name = trim($_POST['grandfather_name']);
    $family_name   = trim($_POST['family_name']);
    $social_security = trim($_POST['social_security']);
    $home_address = trim($_POST['home_address']);
    $office_address = trim($_POST['office_address']);
    $highschool_certificate = $_POST['highschool_certificate'] ?? '';
    $university_degree  = $_POST['university_degree'] ?? '';
    $phone      = trim($_POST['phone']);
    $email      = trim($_POST['email']);
$has_social_security = $_POST['has_social_security'] ?? 'لا';
$social_security = ($has_social_security === "نعم") ? trim($_POST['social_security']) : '';

    try {
        // تحقق من وجود المستخدم مسبقاً
        $checkUser = $pdo->prepare("SELECT user_id FROM users WHERE national_id = ?");
        $checkUser->execute([$national_id]);

        if ($checkUser->rowCount() > 0) {
            $message = "<p class='error'>يوجد حساب بالفعل مرتبط بهذا الرقم الوطني.</p>";
        } else {
            if ($role === 'lawyer') {
                // جلب بيانات المحامي من جدول lawyers_master
                $stmt = $pdo->prepare("SELECT * FROM lawyers_master WHERE national_id = ?");
                $stmt->execute([$national_id]);
                $lawyerMaster = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$lawyerMaster) {
                    $message = "<p class='error'>الرقم الوطني غير موجود في سجل المزاولين.</p>";
                } else {
                    $pdo->beginTransaction();

                    // إنشاء المستخدم
                    $insertUser = $pdo->prepare("
                        INSERT INTO users (full_name, national_id, phone, email, home_address, password, role)
                        VALUES (?, ?, ?, ?, ?, ?, 'lawyer')
                    ");
                    $insertUser->execute([
                        $full_name ?: $lawyerMaster['lawyer_name'], 
                        $national_id,
                        $phone ?: $lawyerMaster['phone'] ?? '',
                        $email ?: $lawyerMaster['email'] ?? '',
                        $home_address ?: $lawyerMaster['home_address'] ?? '',
                        $password
                    ]);
                    $user_id = $pdo->lastInsertId();

                    // إضافة كل الحقول إلى جدول lawyers
                    $insertLawyer = $pdo->prepare("
                        INSERT INTO lawyers (
                            user_id, master_id, first_name, father_name, grandfather_name, family_name,
                            social_security, home_address, office_address,
                            highschool_certificate, university_degree, phone, email, password, verified
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    $insertLawyer->execute([
                        $user_id,
                        $lawyerMaster['master_id'],
                        $first_name ?: $lawyerMaster['first_name'] ?? '',
                        $father_name ?: $lawyerMaster['father_name'] ?? '',
                        $grandfather_name ?: $lawyerMaster['grandfather_name'] ?? '',
                        $family_name ?: $lawyerMaster['family_name'] ?? '',
                        $social_security ?: $lawyerMaster['social_security'] ?? '',
                        $home_address ?: $lawyerMaster['home_address'] ?? '',
                        $office_address ?: $lawyerMaster['office_address'] ?? '',
                        $highschool_certificate ?: $lawyerMaster['highschool_certificate'] ?? '',
                        $university_degree ?: $lawyerMaster['university_degree'] ?? '',
                        $phone ?: $lawyerMaster['phone'] ?? '',
                        $email ?: $lawyerMaster['email'] ?? '',
                        $password
                    ]);

                    $pdo->commit();
                    $message = "<p class='success'>تم التسجيل كمحامي معتمد وتم حفظ جميع المعلومات!</p>";
                }
                
            } elseif ($role === 'student') {
                $stmt = $pdo->prepare("
                    INSERT INTO users (full_name, national_id, phone, email, home_address, password, role)
                    VALUES (?, ?, ?, ?, ?, ?, 'student')
                ");
                $stmt->execute([$full_name, $national_id, $phone, $email, $home_address, $password]);
                $message = "<p class='success'>تم التسجيل كطالب!</p>";

            } elseif ($role === 'admin') {
                $stmt = $pdo->prepare("
                    INSERT INTO users (full_name, national_id, phone, email, home_address, password, role)
                    VALUES (?, ?, ?, ?, ?, ?, 'admin')
                ");
                $stmt->execute([$full_name, $national_id, $phone, $email, $home_address, $password]);
                $message = "<p class='success'>تم إنشاء حساب المدير بنجاح!</p>";
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $message = "<p class='error'>حدث خطأ أثناء التسجيل: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>


<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تسجيل حساب</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
input, select { width:100%; padding:8px; margin-top:5px; border:1px solid #ccc; border-radius:6px; }
button { margin-top:15px; padding:10px; background:#0077b6; color:white; border:none; border-radius:6px; cursor:pointer; }
button:hover { opacity:0.9; }
</style>
</head>
<body>
<div class="container">
<h2>إنشاء حساب جديد</h2>
<?= $message ?>
<form method="POST" id="registerForm">
    <label>نوع الحساب:</label>
    <select name="role" id="role" required>
      <option value="student">طالب</option>
      <option value="lawyer">مزاول</option>
      <option value="admin">مدير النظام</option>
    </select>

    <label>الرقم الوطني:</label>
    <input type="text" name="national_id" id="national_id" required>

    <label>الاسم الأول:</label>
    <input type="text" name="first_name" id="first_name">

    <label>اسم الأب:</label>
    <input type="text" name="father_name" id="father_name">

    <label>اسم الجد:</label>
    <input type="text" name="grandfather_name" id="grandfather_name">

    <label>اسم العائلة:</label>
    <input type="text" name="family_name" id="family_name">

    <label>ضمان اجتماعي:</label>
<select name="has_social_security" id="has_social_security">
    <option value="">اختر</option>
    <option value="نعم">نعم</option>
    <option value="لا">لا</option>
</select>

<label>رقم الضمان الاجتماعي:</label>
<input type="text" name="social_security" id="social_security" disabled>

    <label>عنوان السكن:</label>
    <input type="text" name="home_address" id="home_address">

    <label>عنوان المكتب:</label>
    <input type="text" name="office_address" id="office_address">

    <label>شهادة ثانوية:</label>
    <select name="highschool_certificate" id="highschool_certificate">
      <option value="">اختر</option>
      <option value="نعم">نعم</option>
      <option value="لا">لا</option>
    </select>

    <label>شهادة جامعية:</label>
    <select name="university_degree" id="university_degree">
      <option value="">اختر</option>
      <option value="بكالوريوس">بكالوريوس</option>
      <option value="ماجستير">ماجستير</option>
      <option value="دكتوراه">دكتوراه</option>
    </select>

    <label>البريد الإلكتروني:</label>
    <input type="email" name="email" id="email">

    <label>رقم الهاتف:</label>
    <input type="text" name="phone" id="phone">

    <label>كلمة المرور:</label>
    <input type="password" name="password" required>

    <button type="submit">تسجيل</button>
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    $('#role, #national_id').on('change keyup', function(){
        if($('#role').val() === 'lawyer'){
            let national_id = $('#national_id').val().trim();
            if(national_id.length > 0){
                $.ajax({
                    url: 'fetch_lawyer.php',
                    type: 'POST',
                    data: { national_id: national_id },
                    dataType: 'json',
                    success: function(res){
                        if(res.found){
                            $('#first_name').val(res.first_name);
                            $('#father_name').val(res.father_name);
                            $('#grandfather_name').val(res.grandfather_name);
                            $('#family_name').val(res.family_name);
                            $('#social_security').val(res.social_security);
                            $('#home_address').val(res.home_address);
                            $('#office_address').val(res.office_address);
                            $('#highschool_certificate').val(res.highschool_certificate);
                            $('#university_degree').val(res.university_degree);
                            $('#phone').val(res.phone);
                            $('#email').val(res.email);
                        }
                    }
                });
            }
        }
    });
});
</script>

<script>
$(document).ready(function(){

    // التحكم بخانة الضمان الاجتماعي
    $('#has_social_security').change(function() {
        if ($(this).val() === "نعم") {
            $('#social_security').prop('disabled', false);
        } else {
            $('#social_security').prop('disabled', true).val('');
        }
    });

    // جلب بيانات المحامي تلقائياً عند إدخال الرقم الوطني
    $('#role, #national_id').on('change keyup', function(){
        
        if ($('#role').val() !== 'lawyer') return;

        let national_id = $('#national_id').val().trim();
        if (national_id.length === 0) return;

        $.ajax({
            url: 'fetch_lawyer.php',
            type: 'POST',
            data: { national_id: national_id },
            dataType: 'json',
            success: function(res){

                if (!res.found) return;

                $('#first_name').val(res.first_name);
                $('#father_name').val(res.father_name);
                $('#grandfather_name').val(res.grandfather_name);
                $('#family_name').val(res.family_name);

                $('#home_address').val(res.home_address);
                $('#office_address').val(res.office_address);
                $('#highschool_certificate').val(res.highschool_certificate);
                $('#university_degree').val(res.university_degree);
                $('#phone').val(res.phone);
                $('#email').val(res.email);

                // الضمان الاجتماعي
                if (res.social_security && res.social_security !== "") {
                    $('#has_social_security').val('نعم');
                    $('#social_security').val(res.social_security).prop('disabled', false);
                } else {
                    $('#has_social_security').val('لا');
                    $('#social_security').val('').prop('disabled', true);
                }
            }
        });
    });

});

</script>

</body>
</html>
