<?php
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

<style>
.container {
  width: 60%; margin: 40px auto; background: #fff; padding: 25px; 
  border-radius: 10px; box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}
label { display: block; margin-top: 10px; }
input, select {
  width: 100%; padding: 8px; border-radius: 6px;
  border: 1px solid #ccc; margin-top: 5px;
}
button {
  margin-top: 15px; padding: 10px;
  background: #0077b6; color: white;
  border: none; border-radius: 6px; cursor: pointer;
}
button:hover { opacity: 0.8; }
</style>

</head>
<body>

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
    </select>

    <label>كلمة المرور (اتركها فارغة إذا لا ترغب بتغييرها):</label>
    <input type="password" name="password">

    <button type="submit">💾 حفظ التعديلات</button>

  </form>

  <p><a href="users.php">⬅ الرجوع للقائمة</a></p>

</div>
</body>
</html>
