<?php
session_start();
require_once("../../config/db.php");
include("../includes/header.php");

// حماية الصفحة: فقط المدير يمكنه الدخول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// البحث
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// جلب كل بيانات المستخدمين
$query = "SELECT * FROM users";
$params = [];

if ($search) {
    $query .= " WHERE full_name LIKE :s OR email LIKE :s OR phone LIKE :s";
    $params = [":s" => "%$search%"];
}

$query .= " ORDER BY full_name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>قائمة المستخدمين</title>
<link rel="stylesheet" href="../../assets/css/admin.css"></head>
<body>

<h2>قائمة المستخدمين</h2>

<form method="GET">
    <input type="text" name="search" placeholder="ابحث بالاسم، اسم المستخدم، البريد أو الهاتف..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">🔍 بحث</button>
</form>

<div class="admin-container">
<?php include("../includes/sidebar.php"); ?>
<div class="container">

<?php if ($users): ?>
<table>
<thead>
<tr>
    <th>#</th>
    <th>الاسم الكامل</th>
    <th>البريد</th>
    <th>الهاتف</th>
    <th>الدور</th>
    <th>تاريخ التسجيل</th>
    <th>الإجراءات</th>
</tr>
</thead>
<tbody>
<?php foreach ($users as $i => $u): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><?= htmlspecialchars($u['full_name']) ?></td>
    <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
    <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
    <td><?= htmlspecialchars($u['role']) ?></td>
    <td><?= htmlspecialchars($u['created_at'] ?? '-') ?></td>
    <td>
        <a href="edit_user.php?id=<?= $u['user_id'] ?>" class="btn edit-btn">تعديل</a>
        <a href="delete_user.php?id=<?= $u['user_id'] ?>" class="btn delete-btn" onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">حذف</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p class="no-data">لا توجد نتائج مطابقة.</p>
<?php endif; ?>

</div>
</div>
</body>
</html>
