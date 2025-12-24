<?php 
require_once("../config/db.php");
include("../includes/header.php");

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

    // full_name تلقائي
    $full_name = trim("$first_name $father_name $grandfather_name $family_name");

    $has_social_security = $_POST['has_social_security'] ?? 'لا';
    $social_security = ($has_social_security === "نعم") ? trim($_POST['social_security']) : '';

    $home_address = trim($_POST['home_address']);
    $office_address = trim($_POST['office_address']);
    $highschool_certificate = $_POST['highschool_certificate'] ?? 'لا';
    $university_degree = $_POST['university_degree'] ?? '';
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

try {

    // تحقق من عدم وجود حساب مسبق
    $checkUser = $pdo->prepare("SELECT user_id FROM users WHERE national_id = ?");
    $checkUser->execute([$national_id]);
    if ($checkUser->rowCount() > 0) {
        $message = "<p class='error'>⚠ يوجد حساب بهذا الرقم الوطني.</p>";
    } else {

        $pdo->beginTransaction();

        // ==============================
        // ===== تسجيل المتدرب trainee ===
        // ==============================
if ($_POST['role'] === 'trainee') {

    // التحقق من وجوده في جدول النقابة
    $stmt = $pdo->prepare("
        SELECT * 
        FROM lawyers_syndicate 
        WHERE national_id = ?
    ");
    $stmt->execute([$national_id]);
    $traineeRef = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$traineeRef) {
        $pdo->rollBack();
        $message = "<p class='error'>❌ الرقم الوطني غير موجود بسجلات الجهة المعتمدة للتدريب</p>";
    } else {

// إنشاء المستخدم (متدرب)
$insertUser = $pdo->prepare("
    INSERT INTO users 
    (full_name, national_id, phone, email, address, password, role)
    VALUES (?, ?, ?, ?, ?, ?, 'trainee')
");

$insertUser->execute([
    $full_name,
    $national_id,
    $phone,
    $email,
    $home_address,   // هذا يذهب إلى address
    $password
]);

$user_id = $pdo->lastInsertId();

        // إنشاء سجل المتدرب
        $insertTrainee = $pdo->prepare("
            INSERT INTO trainees (
                user_id,
                full_name,
                first_name,
                father_name,
                grandfather_name,
                family_name,
                national_id,
                phone,
                email,
                home_address,
                highschool_certificate,
                university_degree,
                social_security,
                social_security_number
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $insertTrainee->execute([
            $user_id,
            $full_name ?: $traineeRef['full_name'],
            $first_name ?: $traineeRef['first_name'],
            $father_name ?: $traineeRef['father_name'],
            $grandfather_name ?: $traineeRef['grandfather_name'],
            $family_name ?: $traineeRef['family_name'],
            $national_id,
            $phone ?: $traineeRef['phone'],
            $email ?: $traineeRef['email'],
            $home_address ?: $traineeRef['home_address'],
            $highschool_certificate,
            $university_degree,
            $has_social_security,
            $social_security
        ]);

        $pdo->commit();
        $message = "<p class='success'>✅ تم إنشاء حساب المتدرب بنجاح!</p>";
    }
}

        // =============================
        // ===== تسجيل المحامي lawyer ===
        // =============================
        else {

            $stmt = $pdo->prepare("SELECT * FROM lawyers_syndicate WHERE national_id = ?");
            $stmt->execute([$national_id]);
            $lawyer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lawyer) {
                $pdo->rollBack();
                $message = "<p class='error'>❌ الرقم الوطني غير موجود في جدول النقابة.</p>";
            } else {

// إنشاء المستخدم (محامي)
$insertUser = $pdo->prepare("
    INSERT INTO users 
    (full_name, national_id, phone, email, address, password, role)
    VALUES (?, ?, ?, ?, ?, ?, 'lawyer')
");

$insertUser->execute([
    $full_name ?: trim($lawyer['first_name']." ".$lawyer['father_name']),
    $national_id,
    $phone ?: $lawyer['phone'],
    $email ?: $lawyer['email'],
    $home_address ?: ($lawyer['home_address'] ?? null), // العنوان
    $password
]);

$user_id = $pdo->lastInsertId();

                // إنشاء سجل المحامي
                $insertLawyer = $pdo->prepare("
                    INSERT INTO lawyers (
                        user_id,
                        syndicate_id,
                        full_name,
                        first_name,
                        father_name,
                        grandfather_name,
                        family_name,
                        national_id,
                        social_security,
                        home_address,
                        office_address,
                        highschool_certificate,
                        university_degree,
                        phone,
                        email,
                        password,
                        verified
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");

                $insertLawyer->execute([
                    $user_id,
                    $lawyer['syndicate_id'],
                    $full_name,
                    $first_name ?: $lawyer['first_name'],
                    $father_name ?: $lawyer['father_name'],
                    $grandfather_name ?: $lawyer['grandfather_name'],
                    $family_name ?: $lawyer['family_name'],
                    $national_id,
                    $social_security ?: $lawyer['social_security'],
                    $home_address ?: $lawyer['home_address'],
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
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $message = "<p class='error'>❌ خطأ: {$e->getMessage()}</p>";
}
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تسجيل محامي</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script></head>
<body>

<div class="form-card">
<h2>إنشاء حساب</h2>
<?= $message ?>

<form method="POST">

<label>نوع الحساب:</label>
<select name="role" id="role" required>
    <option value="">اختر</option>
    <option value="trainee">متدرب</option>
    <option value="lawyer">محامي مزاول</option>
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

    <label>الاسم الكامل:</label>
    <input type="text" name="full_name" id="full_name" readonly class="readonly">

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
function updateFullName() {
    const first = $("#first_name").val().trim();
    const father = $("#father_name").val().trim();
    const grand = $("#grandfather_name").val().trim();
    const family = $("#family_name").val().trim();
    $("#full_name").val([first, father, grand, family].filter(Boolean).join(' '));
}

$("#first_name, #father_name, #grandfather_name, #family_name").on('input', updateFullName);

$("#has_social_security").change(function(){
    if($(this).val() === "نعم"){
        $("#social_security").prop("disabled", false);
    } else {
        $("#social_security").prop("disabled", true).val("");
    }
});

// جلب بيانات المحامي تلقائياً من جدول النقابة
$("#national_id").on("keyup change", function(){
    let national_id = $(this).val().trim();
    if(national_id.length < 5) return;

    $.post("fetch_lawyer.php", { national_id: national_id }, function(res){
        if(!res.found) return;

        $("#first_name").val(res.first_name);
        $("#father_name").val(res.father_name);
        $("#grandfather_name").val(res.grandfather_name);
        $("#family_name").val(res.family_name);
        updateFullName();

        $("#home_address").val(res.residence_address);
        $("#office_address").val(res.office_address);
        $("#phone").val(res.phone);
        $("#email").val(res.email);
        $("#highschool_certificate").val(res.highschool_certificate);
        $("#university_degree").val(res.university_degree);

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
<?php include("../includes/footer.php"); ?>