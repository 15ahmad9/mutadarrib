<?php
require_once("../config/db.php");
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name   = trim($_POST['full_name']);
    $national_id = trim($_POST['national_id']);
    $phone       = trim($_POST['phone']);
    $email       = trim($_POST['email']);
    $address     = trim($_POST['address']);
    $password    = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role        = $_POST['role'];

    try {

        // 🧠 تحقق أولاً من وجود حساب مسبقًا
        $checkUser = $pdo->prepare("SELECT user_id FROM users WHERE national_id = ?");
        $checkUser->execute([$national_id]);

        if ($checkUser->rowCount() > 0) {
            $message = "<p class='error'>⚠️ يوجد حساب بالفعل مرتبط بهذا الرقم الوطني. يرجى تسجيل الدخول.</p>";
        } else {

            // 🧑‍⚖️ تسجيل محامي مزاول
            if ($role === 'lawyer') {

                $stmt = $pdo->prepare("SELECT master_id FROM lawyers_master WHERE national_id = ?");
                $stmt->execute([$national_id]);
                $lawyerMaster = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$lawyerMaster) {
                    $message = "<p class='error'>❌ لا يمكن إنشاء الحساب، الرقم الوطني غير موجود في سجل المزاولين.</p>";
                } else {
                    $pdo->beginTransaction();

                    $insertUser = $pdo->prepare("
                        INSERT INTO users (full_name, national_id, phone, email, address, password, role)
                        VALUES (?, ?, ?, ?, ?, ?, 'lawyer')
                    ");
                    $insertUser->execute([$full_name, $national_id, $phone, $email, $address, $password]);
                    $user_id = $pdo->lastInsertId();

                    $insertLawyer = $pdo->prepare("
                        INSERT INTO lawyers (user_id, master_id, office_address, password, verified)
                        VALUES (?, ?, ?, ?, 1)
                    ");
                    $insertLawyer->execute([$user_id, $lawyerMaster['master_id'], $address, $password]);

                    $pdo->commit();
                    $message = "<p class='success'>✅ تم التسجيل بنجاح كمزاول معتمد! يمكنك الآن تسجيل الدخول.</p>";
                }

            // 🎓 تسجيل طالب
            } elseif ($role === 'student') {

                $stmt = $pdo->prepare("
                    INSERT INTO users (full_name, national_id, phone, email, address, password, role)
                    VALUES (?, ?, ?, ?, ?, ?, 'student')
                ");
                $stmt->execute([$full_name, $national_id, $phone, $email, $address, $password]);
                $message = "<p class='success'>✅ تم التسجيل بنجاح كطالب!</p>";

            // 🧑‍💼 تسجيل مدير النظام
            } elseif ($role === 'admin') {

                $stmt = $pdo->prepare("
                    INSERT INTO users (full_name, national_id, phone, email, address, password, role)
                    VALUES (?, ?, ?, ?, ?, ?, 'admin')
                ");
                $stmt->execute([$full_name, $national_id, $phone, $email, $address, $password]);
                $message = "<p class='success'>✅ تم إنشاء حساب المدير بنجاح!</p>";

            }
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "<p class='error'>حدث خطأ أثناء التسجيل: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تسجيل حساب | متدرب</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
  <h2>إنشاء حساب جديد</h2>
  <?= $message ?>
  <form method="POST">
    <label>الاسم الكامل:</label>
    <input type="text" name="full_name" required>

    <label>الرقم الوطني:</label>
    <input type="text" name="national_id" required>

    <label>رقم الهاتف:</label>
    <input type="text" name="phone" required>

    <label>البريد الإلكتروني:</label>
    <input type="email" name="email" required>

    <label>العنوان:</label>
    <input type="text" name="address" required>

    <label>كلمة المرور:</label>
    <input type="password" name="password" required>

    <label>نوع الحساب:</label>
    <select name="role" required>
      <option value="student">طالب</option>
      <option value="lawyer">مزاول</option>
    </select>

    <button type="submit">تسجيل</button>
  </form>
  <p style="text-align:center;">هل لديك حساب؟ <a href="login.php">تسجيل الدخول</a></p>
</div>
</body>
</html>
