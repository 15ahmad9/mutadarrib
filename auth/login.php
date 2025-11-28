<?php
session_start();
require_once("../config/db.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $national_id = trim($_POST["national_id"]);
    $password    = trim($_POST["password"]);

    /*----------------------------------------
        1️⃣ تحقق: هل هو Admin ؟
    -----------------------------------------*/
    $stmt = $pdo->prepare("SELECT * FROM users WHERE national_id = ? AND role = 'admin'");
    $stmt->execute([$national_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {

        $_SESSION["user_id"] = $admin['user_id'];
        $_SESSION["role"]     = "admin";
        $_SESSION["full_name"] = $admin['full_name'];

        header("Location: ../admin/dashboard.php");
        exit;
    }

    /*----------------------------------------
        2️⃣ تحقق: هل هو Lawyer ؟
    -----------------------------------------*/
    $stmt = $pdo->prepare("SELECT * FROM lawyers WHERE national_id = ?");
    $stmt->execute([$national_id]);
    $lawyer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lawyer && password_verify($password, $lawyer['password'])) {

        // تحقق من وجوده في جدول النقابة
        $chk = $pdo->prepare("SELECT * FROM lawyers_syndicate WHERE national_id = ?");
        $chk->execute([$national_id]);
        $inSyndicate = $chk->fetch(PDO::FETCH_ASSOC);

        if (!$inSyndicate) {
            $message = "<p class='error'>❌ غير مسموح — أنت غير مسجل في نقابة المحامين.</p>";
        } else {

            $_SESSION["user_id"]   = $lawyer['lawyer_id'];
            $_SESSION["role"]      = "lawyer";
            $_SESSION["full_name"] = $lawyer['full_name'];

                            header("Location: ../index.php");
            exit;
        }
    }

    /*----------------------------------------
        3️⃣ تحقق: هل هو Trainee ؟
    -----------------------------------------*/
    $stmt = $pdo->prepare("SELECT * FROM trainees WHERE national_id = ?");
    $stmt->execute([$national_id]);
    $trainee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($trainee) {

        // جلب بيانات المستخدم لمعرفة كلمة السر
        $u = $pdo->prepare("SELECT * FROM users WHERE national_id = ? AND role = 'trainee'");
        $u->execute([$national_id]);
        $userAcc = $u->fetch(PDO::FETCH_ASSOC);

        if ($userAcc && password_verify($password, $userAcc['password'])) {

            $_SESSION["user_id"] = $trainee['trainee_id'];
            $_SESSION["role"]    = "trainee";
            $_SESSION["full_name"] = $trainee['full_name'];

                            header("Location: ../index.php");
            exit;
        }
    }

    // إذا لم ينجح أي تسجيل دخول
    $message = "<p class='error'>❌ الرقم الوطني أو كلمة المرور غير صحيحة.</p>";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تسجيل الدخول</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
    <h2>تسجيل الدخول</h2>
    <?= $message ?>
    <form method="POST">
        <label>الرقم الوطني:</label>
        <input type="text" name="national_id" required>

        <label>كلمة المرور:</label>
        <input type="password" name="password" required>

        <button type="submit">تسجيل الدخول</button>
    </form>

    <p>ليس لديك حساب؟ <a href="choose_specialization.php">إنشاء حساب</a></p>
</div>
</body>
</html>
