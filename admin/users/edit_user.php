<?php
require_once __DIR__ . '/../../includes/theme_init.php';

session_start();
require_once("../../config/db.php");

// حماية الوصول: المدير فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: users.php");
    exit;
}

// جلب بيانات المستخدم
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("❌ المستخدم غير موجود.");
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name   = trim($_POST['full_name']);
    $role        = trim($_POST['role']);
    $password    = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;

    try {
        $sql = "UPDATE users SET full_name=?, role=?";
        $params = [$full_name, $role];

        if ($password) {
            $sql .= ", password=?";
            $params[] = $password;
        }

        $sql .= " WHERE user_id=?";
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $message = "<p style='color:green;'>✔ تم تعديل البيانات بنجاح</p>";

        // إعادة تحميل البيانات
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $message = "<p style='color:red;'>❌ خطأ: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تعديل المستخدم</title>
<link rel="stylesheet" href="/mutadarrib/assets/css/admin.css">
</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<div class="container">
  <h2>تعديل بيانات المستخدم</h2>

  <?= $message ?>

  <form method="POST">

    <label>الاسم الكامل:</label>
    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>

    <label>الدور:</label>
    <select name="role">
      <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>مدير</option>
      <option value="lawyer" <?= $user['role']=='lawyer'?'selected':'' ?>>محامي</option>
      <option value="trainee" <?= $user['role']=='trainee'?'selected':'' ?>>متدرب</option>
      <option value="syndicate_admin" <?= $user['role']=='syndicate_admin'?'selected':'' ?>>مدير نقابة</option>
    </select>

    <label>كلمة المرور (اتركها فارغة إذا لا ترغب بتغييرها):</label>
    <input type="password" name="password">

    <button type="submit">💾 حفظ التعديلات</button>

  </form>

  <p><a href="users.php">⬅ الرجوع للقائمة</a></p>

</div>
</body>
</html>
