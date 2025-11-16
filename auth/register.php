<?php
require_once("../config/db.php");
$message = "";

// عند الضغط على تسجيل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $national_id = trim($_POST['national_id']);
    $password    = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // الحقول
    $first_name        = trim($_POST['first_name']);
    $father_name       = trim($_POST['father_name']);
    $grandfather_name  = trim($_POST['grandfather_name']);
    $family_name       = trim($_POST['family_name']);

    $full_name = "$first_name $father_name $grandfather_name $family_name";

    $has_social_security = $_POST['has_social_security'] ?? 'لا';
    $social_security = ($has_social_security === "نعم") ? trim($_POST['social_security']) : '';

    $home_address = trim($_POST['home_address']);
    $office_address = trim($_POST['office_address']);
    $highschool_certificate = $_POST['highschool_certificate'];
    $university_degree = $_POST['university_degree'];
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    try {

        // تحقق من أن المستخدم لم يُسجل مسبقاً
        $checkUser = $pdo->prepare("SELECT user_id FROM users WHERE national_id = ?");
        $checkUser->execute([$national_id]);
        if ($checkUser->rowCount() > 0) {
            $message = "<p class='error'>⚠ يوجد حساب بهذا الرقم الوطني.</p>";
        } else {

            // جلب بيانات المحامي من جدول النقابة
            $stmt = $pdo->prepare("SELECT * FROM lawyers_syndicate WHERE national_id = ?");
            $stmt->execute([$national_id]);
            $lawyer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lawyer) {
                $message = "<p class='error'>❌ الرقم الوطني غير موجود في جدول النقابة.</p>";
            } else {

                $pdo->beginTransaction();

                // إنشاء المستخدم
                $insertUser = $pdo->prepare("
                    INSERT INTO users (full_name, national_id, phone, email, home_address, password, role)
                    VALUES (?, ?, ?, ?, ?, ?, 'lawyer')
                ");

                $insertUser->execute([
                    $full_name ?: $lawyer['first_name'] . " " . $lawyer['father_name'],
                    $national_id,
                    $phone ?: $lawyer['phone'],
                    $email ?: $lawyer['email'],
                    $home_address ?: $lawyer['residence_address'],
                    $password
                ]);

                $user_id = $pdo->lastInsertId();

                // تسجيل معلومات المحامي كاملة
                $insertLawyer = $pdo->prepare("
                    INSERT INTO lawyers (
                        user_id, syndicate_id, first_name, father_name, grandfather_name, family_name,
                        social_security, home_address, office_address,
                        highschool_certificate, university_degree, phone, email, password, verified
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");

                $insertLawyer->execute([
                    $user_id,
                    $lawyer['syndicate_id'],
                    $first_name ?: $lawyer['first_name'],
                    $father_name ?: $lawyer['father_name'],
                    $grandfather_name ?: $lawyer['grandfather_name'],
                    $family_name ?: $lawyer['family_name'],
                    $social_security ?: $lawyer['social_security'],
                    $home_address ?: $lawyer['residence_address'],
                    $office_address ?: $lawyer['office_address'],
                    $highschool_certificate ?: $lawyer['highschool_certificate'],
                    $university_degree ?: $lawyer['university_degree'],
                    $phone ?: $lawyer['phone'],
                    $email ?: $lawyer['email'],
                    $password
                ]);

                $pdo->commit();

                $message = "<p class='success'>🎉 تم إنشاء حساب المحامي بنجاح!</p>";
            }
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "<p class='error'>خطأ: " . $e->getMessage() . "</p>";
    }
}
?>



<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تسجيل محامي</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
input, select { width:100%; padding:8px; margin-top:5px; border:1px solid #ccc; border-radius:6px; }
button { margin-top:15px; padding:10px; background:#0077b6; color:white; border:none; border-radius:6px; cursor:pointer; }
button:hover { opacity:0.9; }
</style>

<style>
input, select {
    width:100%; padding:8px; margin-top:5px;
    border:1px solid #ccc; border-radius:6px;
}
button {
    margin-top:15px; padding:10px; background:#0077b6;
    color:white; border:none; border-radius:6px; cursor:pointer;
}
button:hover { opacity:.9; }
.container {
    width:450px; margin:40px auto; background:white;
    padding:20px; border-radius:8px; box-shadow:0 0 10px #ccc;
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
<body>

<div class="container">
<h2>تسجيل محامي</h2>
<?= $message ?>

<form method="POST">

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

    <label>هل يوجد ضمان اجتماعي؟</label>
    <select id="has_social_security" name="has_social_security">
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
    <select id="highschool_certificate" name="highschool_certificate">
        <option value="">اختر</option>
        <option value="نعم">نعم</option>
        <option value="لا">لا</option>
    </select>

    <label>الشهادة الجامعية:</label>
    <select id="university_degree" name="university_degree">
        <option value="">اختر</option>
        <option value="بكالوريوس">بكالوريوس</option>
        <option value="ماجستير">ماجستير</option>
        <option value="دكتوراه">دكتوراه</option>
    </select>

    <label>الهاتف:</label>
    <input type="text" name="phone" id="phone">

    <label>البريد الإلكتروني:</label>
    <input type="email" name="email" id="email">

    <label>كلمة المرور:</label>
    <input type="password" name="password" required>

    <button type="submit">إنشاء الحساب</button>
</form>
</div>


<script>
$("#has_social_security").change(function(){
    if($(this).val() === "نعم"){
        $("#social_security").prop("disabled", false);
    } else {
        $("#social_security").prop("disabled", true).val("");
    }
});

// جلب بيانات المحامي تلقائياً
$("#national_id").on("keyup change", function(){

    let national_id = $(this).val().trim();
    if(national_id.length < 5) return;

    $.post("fetch_lawyer.php", { national_id: national_id }, function(res){

        if(!res.found) return;

        $("#first_name").val(res.first_name);
        $("#father_name").val(res.father_name);
        $("#grandfather_name").val(res.grandfather_name);
        $("#family_name").val(res.family_name);

        $("#home_address").val(res.residence_address);
        $("#office_address").val(res.office_address);
        $("#phone").val(res.phone);
        $("#email").val(res.email);
        $("#highschool_certificate").val(res.highschool_certificate);
        $("#university_degree").val(res.university_degree);

        // الضمان
        if(res.social_security){
            $("#has_social_security").val("نعم");
            $("#social_security").val(res.social_security).prop("disabled", false);
        } else {
            $("#has_social_security").val("لا");
            $("#social_security").val("").prop("disabled", true);
        }

    }, "json");
});
</script>

</body>
</html>
