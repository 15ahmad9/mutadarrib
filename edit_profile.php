<?php
session_start();
require_once("config/db.php");

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: ./auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// جلب بيانات المستخدم
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("حدث خطأ: المستخدم غير موجود.");
}

$message = "";

// عند إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    // التحقق من صحة المدخلات
    if (empty($phone) || empty($email)) {
        $message = "❌ يجب تعبئة جميع الحقول المطلوبة.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET phone = ?, email = ?, address = ? WHERE user_id = ?");
            $stmt->execute([$phone, $email, $address, $user_id]);
            $message = "✅ تم تحديث المعلومات بنجاح.";
            // تحديث البيانات في الجلسة في حال كنت تستخدمها في الهيدر
            $_SESSION['email'] = $email;
        } catch (PDOException $e) {
            $message = "⚠️ حدث خطأ أثناء التحديث: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تعديل الملف الشخصي | <?= htmlspecialchars($user['full_name']) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include("includes/header.php"); ?>

<div class="container edit-profile-page">
  <h2>✏️ تعديل الملف الشخصي</h2>

  <?php if ($message): ?>
    <p class="alert"><?= $message ?></p>
  <?php endif; ?>

  <form action="" method="POST" class="edit-form">
    <label for="phone">رقم الهاتف:</label>
    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>

    <label for="email">البريد الإلكتروني:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

    <label for="address">عنوان السكن:</label>
    <input type="text" id="address" name="address" value="<?= htmlspecialchars($user['address']) ?>">

    <button type="submit" class="btn">حفظ التغييرات</button>
    <a href="profile.php" class="btn-cancel">العودة للملف الشخصي</a>
  </form>
</div>

<?php include("includes/footer.php"); ?>

</body>
</html>
