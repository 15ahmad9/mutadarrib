<?php
session_start();
require_once("../../config/db.php");
include("../includes/header.php");

// حماية الصفحة: فقط المدير يمكنه الدخول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// البحث في المحامين
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// جلب المحامين مع بيانات المستخدم المرتبط (إن وجد)
$query = "
    SELECT lm.*, u.full_name, u.national_id AS user_national_id, u.phone, u.email
    FROM lawyers_syndicate lm
    LEFT JOIN lawyers l ON lm.syndicate_id = l.syndicate_id
    LEFT JOIN users u ON l.user_id = u.user_id
";

if ($search) {
    $query .= " WHERE lm.lawyer_name LIKE :s OR lm.national_id LIKE :s OR u.full_name LIKE :s OR u.national_id LIKE :s ";
}

$query .= " ORDER BY lm.lawyer_name ASC";

$stmt = $pdo->prepare($query);

if ($search) {
    $stmt->execute([":s" => "%$search%"]);
} else {
    $stmt->execute();
}

$lawyers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>قائمة المحامين</title>
<link rel="stylesheet" href="../../assets/css/admin.css">
<style>
table { width:100%; border-collapse:collapse; margin-top:20px; font-size:14px; }
th, td { padding:8px; border:1px solid #ccc; text-align:center; }
th { background:#0077b6; color:white; }
.btn { padding:6px 10px; border-radius:6px; color:white; text-decoration:none; }
.edit-btn { background:#00b4d8; }
.delete-btn { background:#d62828; }
</style>
</head>
<body>

<h2>قائمة المحامين</h2>

<form method="GET">
    <input type="text" name="search" placeholder="بحث بالاسم أو الرقم الوطني..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">🔍 بحث</button>
    <a href="add_syndicate_lawyer.php" class="btn" style="background:#52b788;">➕ إضافة محامي جديد</a>
</form>

<div class="admin-container">
<?php include("../includes/sidebar.php"); ?>
<div class="container">

<?php if ($lawyers): ?>
<table>
<thead>
<tr>
    <th>#</th>
    <th>الاسم الكامل</th>
    <th>الاسم الأول</th>
    <th>اسم الأب</th>
    <th>اسم الجد</th>
    <th>اسم العائلة</th>
    <th>الرقم الوطني</th>
    <th>الهاتف</th>
    <th>البريد</th>
    <th>عنوان السكن</th>
    <th>رقم السجل</th>
    <th>عنوان المكتب</th>
    <th>تاريخ التسجيل</th>
    <th>الإجراءات</th>
</tr>
</thead>
<tbody>

<?php foreach ($lawyers as $i => $l): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><?= htmlspecialchars($l['full_name'] ?? $l['full_name']) ?></td>
    <td><?= htmlspecialchars($l['first_name'] ?? '-') ?></td>
    <td><?= htmlspecialchars($l['father_name'] ?? '-') ?></td>
    <td><?= htmlspecialchars($l['grandfather_name'] ?? '-') ?></td>
    <td><?= htmlspecialchars($l['family_name'] ?? '-') ?></td>
    <td><?= htmlspecialchars($l['national_id'] ?? $l['user_national_id']) ?></td>
    <td><?= htmlspecialchars($l['phone'] ?? '-') ?></td>
    <td><?= htmlspecialchars($l['email'] ?? '-') ?></td>
    <td><?= htmlspecialchars($l['home_address'] ?? '-') ?></td>
    <td><?= htmlspecialchars($l['syndicate_id']) ?></td>
    <td><?= htmlspecialchars($l['office_address'] ?? '-') ?></td>
    <td><?= htmlspecialchars($l['created_at'] ?? '-') ?></td>
    <td>
        <a href="edit_syndicate_lawyer.php?id=<?= $l['syndicate_id'] ?>" class="btn edit-btn">تعديل</a>
        <a href="delete_syndicate_lawyer.php?id=<?= $l['syndicate_id'] ?>" class="btn delete-btn" onclick="return confirm('هل أنت متأكد من حذف هذا المحامي؟')">حذف</a>
    </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<?php else: ?>
<p class="no-data">لا توجد بيانات مطابقة لبحثك.</p>
<?php endif; ?>

</div>
</div>
</body>
</html>
