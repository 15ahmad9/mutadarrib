<?php
require_once __DIR__ . '/../../includes/theme_init.php';

require_once("../../config/db.php");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>مكاتب المحامين | منصة متدرب</title>
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/lawyers.css">

</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<?php include("../../includes/header.php"); ?>
<div class="container">

<div class="page-header">
    <h1>مكاتب المحامين المعتمدين</h1>
    <p>اختر مكتب التدريب المناسب لك وابدأ رحلتك المهنية</p>
</div>

<!-- ================= Filters ================== -->
<div class="filters">
    <input type="text" placeholder="🔍 البحث باسم المحامي">
    <select>
        <option>الكل</option>
        <option>يقبل متدربين</option>
        <option>مغلق</option>
    </select>
</div>

<!-- ================= Lawyers Grid ================= -->
<div class="lawyers-grid">

<?php
$q = $pdo->query("
    SELECT 
        l.*, 
        u.full_name 
    FROM lawyers l
    LEFT JOIN users u ON u.user_id = l.user_id
    WHERE l.verified = 1
");

while ($lawyer = $q->fetch(PDO::FETCH_ASSOC)):
    
$status = ($lawyer['verified']) ? "يقبل متدربين" : "مغلق";
$tagClass = ($lawyer['verified']) ? "" : "closed";
?>

<div class="lawyer-card">

    <div class="lawyer-header">
        <div class="lawyer-avatar">⚖️</div>
        <div class="lawyer-name">
            <?= htmlspecialchars($lawyer['full_name']) ?>
        </div>
    </div>

    <div class="lawyer-info">
        <p><strong>📍 المكتب:</strong> <?= htmlspecialchars($lawyer['office_address']) ?></p>
        <p><strong>📞 الهاتف:</strong> <?= htmlspecialchars($lawyer['phone']) ?></p>
        <p><strong>📧 البريد:</strong> <?= htmlspecialchars($lawyer['email']) ?></p>

        <span class="tag <?= $tagClass ?>">
            <?= $status ?>
        </span>
    </div>

<?php if ($lawyer['verified']): ?>
    <a href="lawyer_profile.php?id=<?= $lawyer['lawyer_id'] ?>" class="btn">
        عرض تفاصيل المكتب
    </a>
<?php else: ?>
    <a class="btn disabled">لا يقبل حالياً</a>
<?php endif; ?>

</div>

<?php endwhile; ?>

</div>

</div>

<?php include("../../includes/footer.php"); ?>

</body>
</html>
