<?php
require_once __DIR__ . '/../../includes/theme_init.php';

session_start();
require_once("../../config/db.php");
include("../includes/header.php");

// حماية الدخول: المدير فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$search = $_GET['search'] ?? "";

/*
  نجلب بيانات المحامي من جدول lawyers
  ونربطها مع جدول users لعرض الاسم/الرقم الوطني/الهاتف/الايميل (حسب نمط trainees.php)
*/
$query = "
    SELECT 
        l.*,
        u.full_name,
        u.national_id,
        u.phone,
        u.email
    FROM lawyers l
    JOIN users u ON l.user_id = u.user_id
";

// إذا تم البحث نضيف شرط WHERE
if ($search) {
    $query .= " WHERE (u.full_name LIKE :s OR u.national_id LIKE :s OR u.phone LIKE :s OR u.email LIKE :s)";
}

// ترتيب
$query .= " ORDER BY u.full_name ASC";

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
<title>إدارة المحامين</title>
<link rel="stylesheet" href="../../assets/css/admin.css"></head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<div class="admin-container">
<?php include("../includes/sidebar.php"); ?>

<div class="container">
<h2>إدارة المحامين</h2>

<form method="GET">
    <input type="text" name="search" placeholder="بحث بالاسم أو الرقم الوطني أو الهاتف أو البريد..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">بحث</button>

    <!-- اختياري: زر إضافة محامي إذا عندك صفحة add_lawyer.php -->
    <a href="add_lawyer.php" class="btn" style="background:#52b788;">إضافة محامي جديد</a>
</form>

<?php if ($lawyers): ?>
<table>
<thead>
<tr>
    <th>#</th>
    <th>الاسم الكامل</th>
    <th>الرقم الوطني</th>
    <th>الهاتف</th>
    <th>البريد</th>
    <th>رقم النقابة</th>
    <th>عنوان المكتب</th>
    <th>موثق</th>
    <th>تاريخ الإنشاء</th>
    <th>الإجراءات</th>
</tr>
</thead>
<tbody>

<?php foreach ($lawyers as $i => $l): ?>
<tr>
    <td><?= $i + 1 ?></td>
    <td><?= htmlspecialchars($l['full_name'] ?? '') ?></td>
    <td><?= htmlspecialchars($l['national_id'] ?? '') ?></td>
    <td><?= htmlspecialchars($l['phone'] ?? '') ?></td>
    <td><?= htmlspecialchars($l['email'] ?? '') ?></td>
    <td><?= htmlspecialchars($l['syndicate_id'] ?? '-') ?></td>
    <td><?= htmlspecialchars($l['office_address'] ?? '-') ?></td>
    <td>
        <?php if ((int)$l['verified'] === 1): ?>
            <span class="badge badge-yes">نعم</span>
        <?php else: ?>
            <span class="badge badge-no">لا</span>
        <?php endif; ?>
    </td>
    <td><?= htmlspecialchars($l['created_at'] ?? '-') ?></td>

    <td class="actions">
        <!-- صفحة تعديل بيانات المحامي (أنشئها لاحقاً إن لم تكن موجودة) -->
        <?php if (!empty($l['syndicate_id'])): ?>
  <a class="btn edit-btn" href="edit_syndicate_lawyer.php?id=<?= (int)$l['syndicate_id'] ?>">تعديل</a>
<?php else: ?>
  <a class="btn edit-btn" href="edit_lawyer.php?id=<?= (int)$l['lawyer_id'] ?>">تعديل</a>
<?php endif; ?>

        <!-- صفحة عرض تفصيلية (اختياري) -->
        <a class="btn view-btn" href="view_lawyer.php?id=<?= (int)$l['lawyer_id'] ?>">عرض</a>

        <!-- توثيق/إلغاء توثيق (اختياري) -->
        <?php if ((int)$l['verified'] === 1): ?>
            <a class="btn unverify-btn" href="toggle_verify.php?id=<?= (int)$l['lawyer_id'] ?>&v=0"
               onclick="return confirm('هل تريد إلغاء توثيق هذا المحامي؟')">إلغاء التوثيق</a>
        <?php else: ?>
            <a class="btn verify-btn" href="toggle_verify.php?id=<?= (int)$l['lawyer_id'] ?>&v=1"
               onclick="return confirm('هل تريد توثيق هذا المحامي؟')">توثيق</a>
        <?php endif; ?>
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
