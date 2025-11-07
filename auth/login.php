<?php
require_once("../config/db.php");
session_start();
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        header("Location: ../index.php");
        exit;
    } else {
        $message = "<p class='error'>❌ البريد الإلكتروني أو كلمة المرور غير صحيحة.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تسجيل الدخول | متدرب</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
  <h2>تسجيل الدخول</h2>
  <?= $message ?>
  <form method="POST">
    <label>البريد الإلكتروني:</label>
    <input type="email" name="email" required>
    <label>كلمة المرور:</label>
    <input type="password" name="password" required>
    <button type="submit">دخول</button>
  </form>
  <p style="text-align:center;">ليس لديك حساب؟ <a href="register.php">إنشاء حساب</a></p>
</div>
</body>
</html>
