<?php
session_start();
require_once("../config/db.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $national_id = trim($_POST["national_id"]);
    $password = trim($_POST["password"]);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE national_id = ?");
    $stmt->execute([$national_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // تسجيل الدخول ناجح ✅
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        // توجيه حسب نوع المستخدم
        if ($user['role'] === 'admin') {
            header("Location: ../admin/dashboard.php");
        } else {
            header("Location: ../index.php");
        }
        exit;

    } else {
        $message = "<p class='error'>❌ الرقم الوطني أو كلمة المرور غير صحيحة.</p>";
    }
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
