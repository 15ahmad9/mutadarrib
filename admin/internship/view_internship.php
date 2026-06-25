<?php
session_start();
require_once("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /mutadarrib/auth/login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("رقم الفرصة غير صالح.");
}

$stmt = $pdo->prepare("
    SELECT *
    FROM specialization_internships
    WHERE internship_id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$internship = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$internship) {
    die("الفرصة غير موجودة.");
}

$stmt = $pdo->prepare("
    SELECT 
        a.*,
        u.full_name,
        u.national_id,
        u.phone,
        u.email
    FROM specialization_applications a
    JOIN users u ON a.user_id = u.user_id
    WHERE a.internship_id = ?
    ORDER BY a.applied_at DESC
");
$stmt->execute([$id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

function specName($slug) {
    return match ($slug) {
        'business' => 'الأعمال',
        'arts' => 'الآداب',
        'architecture-design' => 'العمارة والتصميم',
        'medical-support' => 'الدعم الطبي',
        default => $slug
    };
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تفاصيل فرصة التدريب</title>
<link rel="stylesheet" href="/mutadarrib/assets/css/admin.css">

<style>
.details-card {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
th, td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: center;
}
th {
    background: #0077b6;
    color: #fff;
}
</style>
</head>
<body>

<?php include("../includes/header.php"); ?>

<div class="admin-container">
<?php include("../includes/sidebar.php"); ?>

<div class="container">
    <h2>تفاصيل فرصة التدريب</h2>

    <div class="details-card">
        <p><strong>التخصص:</strong> <?= htmlspecialchars(specName($internship['specialization_slug'])) ?></p>
        <p><strong>العنوان:</strong> <?= htmlspecialchars($internship['title']) ?></p>
        <p><strong>مزود التدريب:</strong> <?= htmlspecialchars($internship['provider_name']) ?></p>
        <p><strong>الوصف:</strong><br><?= nl2br(htmlspecialchars($internship['description'] ?? '-')) ?></p>
        <p><strong>الموقع:</strong> <?= htmlspecialchars($internship['location'] ?? '-') ?></p>
        <p><strong>نوع التدريب:</strong> <?= htmlspecialchars($internship['training_type']) ?></p>
        <p><strong>المقاعد:</strong> <?= (int)$internship['seats'] ?></p>
        <p><strong>الحالة:</strong> <?= $internship['status'] === 'open' ? 'مفتوحة' : 'مغلقة' ?></p>
        <p><strong>تاريخ البداية:</strong> <?= htmlspecialchars($internship['start_date'] ?? '-') ?></p>
        <p><strong>تاريخ النهاية:</strong> <?= htmlspecialchars($internship['end_date'] ?? '-') ?></p>
    </div>

    <h3>طلبات التقديم على هذه الفرصة</h3>

    <?php if (!$applications): ?>
        <p>لا توجد طلبات على هذه الفرصة.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>اسم المتقدم</th>
                    <th>الرقم الوطني</th>
                    <th>الهاتف</th>
                    <th>البريد</th>
                    <th>الحالة</th>
                    <th>تاريخ التقديم</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $i => $app): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($app['full_name']) ?></td>
                    <td><?= htmlspecialchars($app['national_id']) ?></td>
                    <td><?= htmlspecialchars($app['phone']) ?></td>
                    <td><?= htmlspecialchars($app['email']) ?></td>
                    <td><?= htmlspecialchars($app['status']) ?></td>
                    <td><?= htmlspecialchars($app['applied_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <br>
    <a href="internships.php">العودة لجميع الفرص</a>
</div>
</div>

</body>
</html>