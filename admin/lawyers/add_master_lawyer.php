<?php
session_start();
require_once("../../config/db.php");

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lawyer_name   = trim($_POST['lawyer_name']);
    $national_id   = trim($_POST['national_id']);
    $office_address = trim($_POST['office_address']);
    $phone         = trim($_POST['phone']);
    $email         = trim($_POST['email']);
    $address       = trim($_POST['address']);
    $password  = trim($_POST['password']);
    $password_hashed = password_hash($password, PASSWORD_BCRYPT);

    try {
        // التأكد من عدم وجود الرقم الوطني مسبقًا في أي من الجداول
        $check = $pdo->prepare("SELECT * FROM lawyers_master WHERE national_id = ?");
        $check->execute([$national_id]);

        if ($check->rowCount() > 0) {
            $message = "<p style='color:red;'>⚠️ يوجد محامي بهذا الرقم الوطني مسبقًا في سجل النقابة!</p>";
        } else {
            // بدء معاملة
            $pdo->beginTransaction();

            // 1️⃣ إدخال المحامي في جدول النقابة (lawyers_master)
            $stmt1 = $pdo->prepare("
                INSERT INTO lawyers_master (lawyer_name, national_id, office_address, phone, email, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt1->execute([$lawyer_name, $national_id, $office_address, $phone, $email]);
            $master_id = $pdo->lastInsertId();

            // 2️⃣ إدخاله في جدول المستخدمين (users)
            $stmt2 = $pdo->prepare("
                INSERT INTO users (full_name, national_id, phone, email, address, password, role)
                VALUES (?, ?, ?, ?, ?, ?, 'lawyer')
            ");
            $stmt2->execute([$lawyer_name, $national_id, $phone, $email, $office_address, $password]);
            $user_id = $pdo->lastInsertId();

            // 3️⃣ إدخاله في جدول المحامين المزاولين (lawyers)
            $stmt3 = $pdo->prepare("
                INSERT INTO lawyers (user_id, master_id, office_address, verified, created_at)
                VALUES (?, ?, ?, 1, NOW())
            ");
            $stmt3->execute([$user_id, $master_id, $office_address]);

            // تأكيد العملية
            $pdo->commit();

            $message = "<p style='color:green;'>✅ تم إضافة المحامي بنجاح إلى جميع الجداول!</p>";
        }
    } catch (Exception $e) {
        // إلغاء العملية إذا حدث خطأ
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "<p style='color:red;'>❌ حدث خطأ أثناء الإضافة: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>➕ إضافة محامي جديد</title>
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

<h2 style="text-align:center;">➕ إضافة محامي جديد إلى النظام</h2>
<div style="text-align:center;"><?= $message ?></div>

<form method="POST">
  <label>الاسم الكامل:</label>
  <input type="text" name="lawyer_name" required>

  <label>الرقم الوطني:</label>
  <input type="text" name="national_id" required>

  <label>عنوان المكتب:</label>
  <input type="text" name="office_address">

  <label>رقم الهاتف:</label>
  <input type="text" name="phone">

  <label>البريد الإلكتروني:</label>
  <input type="email" name="email" required>

  <label>عنوان السكن:</label>
  <input type="text" name="address">

  <label>كلمة المرور (للدخول للنظام):</label>
  <input type="password" name="password" required>

  <button type="submit">💾 حفظ البيانات</button>
</form>

<a href="master_lawyers.php" class="back-link">⬅️ العودة إلى قائمة المحامين</a>

</body>
</html>