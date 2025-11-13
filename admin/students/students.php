<?php
session_start();
require_once("../../config/db.php");
include("../includes/header.php");

// حماية الدخول: المدير فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$search = $_GET['search'] ?? "";

// جلب بيانات الطلاب مع بيانات المستخدم
$query = "
    SELECT s.*, u.full_name, u.national_id, u.phone, u.email
    FROM students s
    JOIN users u ON s.user_id = u.user_id
";

if ($search) {
    $query .= " WHERE u.full_name LIKE :s OR u.national_id LIKE :s ";
}

$query .= " ORDER BY u.full_name ASC ";

$stmt = $pdo->prepare($query);
$stmt->execute(["s" => "%$search%"]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>قائمة الطلاب</title>
<link rel="stylesheet" href="../../assets/css/admin.css">
<style>
table { width:100%; border-collapse:collapse; margin-top:20px; }
th,td { padding:10px; border:1px solid #ccc; text-align:center; }
th { background:#0077b6; color:#fff; }
.btn { padding:6px 10px; border-radius:6px; color:white; text-decoration:none; }
.edit-btn { background:#0096c7; }
.delete-btn { background:#d62828; }
</style>
</head>
<body>

<h2>🎓 قائمة الطلاب</h2>

<form method="GET">
    <input type="text" name="search" placeholder="بحث بالاسم أو الرقم الوطني..." value="<?= $search ?>">
    <button type="submit">🔍 بحث</button>
    <a href="add_student.php" class="btn" style="background:#52b788;">➕ إضافة طالب جديد</a>
</form>

<div class="admin-container">
<?php include("../includes/sidebar.php"); ?>
<div class="container">

<?php if ($students): ?>
<table>
<thead>
<tr>
    <th>#</th>
    <th>الاسم</th>
    <th>الرقم الوطني</th>
    <th>الهاتف</th>
    <th>البريد</th>
    <th>الشهادة الجامعية</th>
    <th>الضمان الاجتماعي</th>
    <th>الإجراءات</th>
</tr>
</thead>
<tbody>

<?php foreach ($students as $i => $s): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><?= htmlspecialchars($s['full_name']) ?></td>
    <td><?= htmlspecialchars($s['national_id']) ?></td>
    <td><?= htmlspecialchars($s['phone']) ?></td>
    <td><?= htmlspecialchars($s['email']) ?></td>
    <td><?= htmlspecialchars($s['university_degree']) ?></td>
    <td><?= htmlspecialchars($s['social_security']) ?></td>

    <td>
        <a class="btn edit-btn" href="edit_student.php?id=<?= $s['student_id'] ?>">✏️ تعديل</a>
        <a class="btn delete-btn" onclick="return confirm('هل أنت متأكد من حذف هذا الطالب؟')" href="delete_student.php?id=<?= $s['student_id'] ?>">🗑 حذف</a>
    </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<?php else: ?>
<p class="no-data">لا توجد نتائج.</p>
<?php endif; ?>

</div>
</div>
</body>
</html>
