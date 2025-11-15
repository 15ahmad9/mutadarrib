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
    $full_name = trim($_POST['full_name']);
    $phone     = trim($_POST['phone']);
    $email     = trim($_POST['email']);
    $address   = trim($_POST['address']);
    $password  = trim($_POST['password']); // كلمة المرور الجديدة

    // التحقق من صحة المدخلات الأساسية
    if (empty($full_name) || empty($phone) || empty($email)) {
        $message = "يجب تعبئة جميع الحقول المطلوبة.";
    } else {
        try {
            $params = [$full_name, $phone, $email, $address];
            $sql = "UPDATE users SET full_name = ?, phone = ?, email = ?, address = ?";

            // إذا أدخل المستخدم كلمة مرور جديدة → تشفيرها وتحديثها
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $sql .= ", password = ?";
                $params[] = $hashed_password;
            }

            $sql .= " WHERE user_id = ?";
            $params[] = $user_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $message = "✅ تم تحديث المعلومات بنجاح.";

            // إعادة تحميل البيانات بعد التحديث
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // تحديث الجلسة إذا كنت تستخدمها لعرض البريد أو الاسم في الهيدر
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];

        } catch (PDOException $e) {
            $message = "حدث خطأ أثناء التحديث: " . htmlspecialchars($e->getMessage());
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
  <style>
    .edit-profile-page { max-width: 600px; margin: 40px auto; background:#fff; padding:25px; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
    .edit-form label { display:block; margin-top:10px; }
    .edit-form input { width:100%; padding:8px; border:1px solid #ccc; border-radius:6px; margin-top:5px; }
    .btn { margin-top:15px; padding:10px 15px; background:#0077b6; color:#fff; border:none; border-radius:6px; cursor:pointer; }
    .btn:hover { opacity:0.9; }
    .btn-cancel { margin-left:10px; text-decoration:none; background:#6c757d; color:#fff; padding:10px 15px; border-radius:6px; }
    .alert { margin-top:10px; color: green; }
  </style>
</head>
<body>

<?php include("includes/header.php"); ?>

<div class="container edit-profile-page">
  <h2>تعديل الملف الشخصي</h2>

  <?php if ($message): ?>
    <p class="alert"><?= $message ?></p>
  <?php endif; ?>

  <form action="" method="POST" class="edit-form">
    <label for="full_name">الاسم الكامل:</label>
    <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>

    <label for="phone">رقم الهاتف:</label>
    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>

    <label for="email">البريد الإلكتروني:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

    <label for="address">عنوان السكن:</label>
    <input type="text" id="address" name="address" value="<?= htmlspecialchars($user['address']) ?>">

    <label for="password">كلمة المرور الجديدة (اتركها فارغة إذا لا تريد التغيير):</label>
    <input type="password" id="password" name="password">

    <button type="submit" class="btn">حفظ التغييرات</button>
    <a href="profile.php" class="btn-cancel">العودة للملف الشخصي</a>
  </form>
</div>

<?php include("includes/footer.php"); ?>
</body>
</html>
