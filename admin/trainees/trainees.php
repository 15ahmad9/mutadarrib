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
    FROM trainees s
    JOIN users u ON s.user_id = u.user_id
";

// إذا تم البحث نضيف شرط WHERE
if ($search) {
    $query .= " WHERE u.full_name LIKE :s OR u.national_id LIKE :s ";
}

$query .= " ORDER BY u.full_name ASC";

$stmt = $pdo->prepare($query);

if ($search) {
    $stmt->execute([":s" => "%$search%"]);
} else {
    $stmt->execute();
}

$trainees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>قائمة الطلاب</title>
<link rel="stylesheet" href="../../assets/css/admin.css"></head>
<body>



<div class="admin-container">
<?php include("../includes/sidebar.php"); ?>
<div class="container">

<h2>قائمة الطلاب</h2>

<form method="GET">
    <input type="text" name="search" placeholder="بحث بالاسم أو الرقم الوطني..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">🔍 بحث</button>
    <a href="add_trainee.php" class="btn" style="background:#52b788;">➕ إضافة متدرب جديد</a>
</form>

<?php if ($trainees): ?>
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
    <th>العنوان</th>
    <th>الشهادة الثانوية</th>
    <th>الدرجة الجامعية</th>
    <th>الضمان الاجتماعي</th>
    <th>رقم الضمان</th>
    <th>الإجراءات</th>
</tr>
</thead>
<tbody>

<?php foreach ($trainees as $i => $s): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><?= htmlspecialchars($s['full_name']) ?></td>
    <td><?= htmlspecialchars($s['first_name']) ?></td>
    <td><?= htmlspecialchars($s['father_name']) ?></td>
    <td><?= htmlspecialchars($s['grandfather_name']) ?></td>
    <td><?= htmlspecialchars($s['family_name']) ?></td>
    <td><?= htmlspecialchars($s['national_id']) ?></td>
    <td><?= htmlspecialchars($s['phone']) ?></td>
    <td><?= htmlspecialchars($s['email']) ?></td>
    <td><?= htmlspecialchars($s['home_address']) ?></td>
    <td><?= htmlspecialchars($s['highschool_certificate']) ?></td>
    <td><?= htmlspecialchars($s['university_degree']) ?></td>
    <td><?= htmlspecialchars($s['social_security']) ?></td>
    <td><?= htmlspecialchars($s['social_security_number']) ?></td>

    <td>
        <a class="btn edit-btn" href="edit_trainee.php?id=<?= $s['trainee_id'] ?>">تعديل</a>
        <a class="btn delete-btn" onclick="return confirm('هل أنت متأكد من حذف هذا المتدرب؟')" href="delete_trainee.php?id=<?= $s['trainee_id'] ?>">حذف</a>
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
