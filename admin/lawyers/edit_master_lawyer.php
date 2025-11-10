<?php
session_start();
require_once("../../config/db.php");

// حماية الوصول: المدير فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: master_lawyers.php");
    exit;
}

// جلب بيانات المحامي مع بيانات المستخدم المرتبط (إن وجد)
$stmt = $pdo->prepare("
    SELECT lm.*, u.user_id, u.full_name AS user_full_name, u.national_id AS user_national_id,
           u.phone AS user_phone, u.email AS user_email,
           l.lawyer_id, l.office_address AS lawyer_office
    FROM lawyers_master lm
    LEFT JOIN lawyers l ON lm.master_id = l.master_id
    LEFT JOIN users u ON l.user_id = u.user_id
    WHERE lm.master_id = ?
");
$stmt->execute([$id]);
$lawyer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lawyer) {
    die("❌ لم يتم العثور على المحامي.");
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name      = trim($_POST['full_name']);
    $national_id    = trim($_POST['national_id']);
    $office_address = trim($_POST['office_address']);
    $phone          = trim($_POST['phone']);
    $email          = trim($_POST['email']);
    $password       = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;

    try {
        $pdo->beginTransaction();

        // تحديث جدول النقابة
        $stmt1 = $pdo->prepare("
            UPDATE lawyers_master
            SET lawyer_name = ?, national_id = ?, office_address = ?, phone = ?, email = ?
            WHERE master_id = ?
        ");
        $stmt1->execute([$full_name, $national_id, $office_address, $phone, $email, $id]);

        // إذا كان المحامي مسجلاً في التطبيق، حدث بياناته في جدول users
        if ($lawyer['user_id']) {
            $fields = "full_name = ?, national_id = ?, phone = ?, email = ?, address = ?";
            $params = [$full_name, $national_id, $phone, $email, $office_address];

            if ($password) {
                $fields .= ", password = ?";
                $params[] = $password;
            }

            $params[] = $lawyer['user_id'];

            $stmt2 = $pdo->prepare("UPDATE users SET $fields WHERE user_id = ?");
            $stmt2->execute($params);

            // تحديث جدول lawyers إذا كان موجود
            if ($lawyer['lawyer_id']) {
                $stmt3 = $pdo->prepare("UPDATE lawyers SET office_address = ? WHERE lawyer_id = ?");
                $stmt3->execute([$office_address, $lawyer['lawyer_id']]);
            }
        }

        $pdo->commit();
        $message = "<p style='color:green;'>✅ تم تحديث بيانات المحامي بنجاح!</p>";

        // إعادة تحميل البيانات بعد التحديث
        $stmt->execute([$id]);
        $lawyer = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "<p style='color:red;'>❌ حدث خطأ أثناء التحديث: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تعديل بيانات المحامي</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.container { width: 60%; margin: 50px auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 3px 6px rgba(0,0,0,0.1);}
label { display: block; margin-top: 10px;}
input[type="text"], input[type="email"], input[type="password"], button {
  width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc; margin-top: 5px;
}
button { margin-top: 15px; padding: 10px; background-color: #0077b6; color: white; border: none; border-radius: 6px; cursor: pointer;}
button:hover { opacity: 0.9; }
</style>
</head>
<body>

<?php include("../includes/header.php"); ?>

<div class="container">
  <h2>✏️ تعديل بيانات المحامي</h2>
  <?= $message ?>
  <form method="POST">
    <label>الاسم الكامل:</label>
    <input type="text" name="full_name" value="<?= htmlspecialchars($lawyer['lawyer_name'] ?? $lawyer['user_full_name']) ?>" required>

    <label>الرقم الوطني:</label>
    <input type="text" name="national_id" value="<?= htmlspecialchars($lawyer['national_id'] ?? $lawyer['user_national_id']) ?>" required>

    <label>عنوان المكتب:</label>
    <input type="text" name="office_address" value="<?= htmlspecialchars($lawyer['office_address'] ?? $lawyer['lawyer_office']) ?>">

    <label>رقم الهاتف:</label>
    <input type="text" name="phone" value="<?= htmlspecialchars($lawyer['phone'] ?? $lawyer['user_phone']) ?>">

    <label>البريد الإلكتروني:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($lawyer['email'] ?? $lawyer['user_email']) ?>">

    <label>كلمة المرور (اتركها فارغة إذا لا تريد تغييرها):</label>
    <input type="password" name="password">

    <button type="submit">💾 حفظ التعديلات</button>
  </form>

  <p><a href="master_lawyers.php">⬅️ العودة إلى القائمة</a></p>
</div>
</body>
</html>
