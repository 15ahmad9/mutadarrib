<?php
require_once("includes/auth_check.php");
require_once("../config/db.php");

$lawyer_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
SELECT *
FROM trainings
WHERE lawyer_id=?
ORDER BY created_at DESC
");

$stmt->execute([$lawyer_id]);
$trainings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>التدريبات</title>
<link rel="stylesheet" href="../assets/css/lawyers.css">
</head>

<body>

<?php include("includes/header.php"); ?>
<?php include("includes/sidebar.php"); ?>

<main class="content">

<h2>📚 تدريباتي</h2>

<table class="table">
<thead>
<tr>
    <th>العنوان</th>
    <th>المقاعد المتبقية</th>
    <th>الحالة</th>
</tr>
</thead>

<tbody>

<?php foreach($trainings as $t): ?>

<tr>
<td><?= $t['title']; ?></td>

<td><?= $t['seats']; ?></td>

<td>
<span class="<?= ($t['status']=='open'?'open':'closed'); ?>">
<?= $t['status']=='open'?'✅ مفتوح':'❌ مغلق' ?>
</span>
</td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

</main>
</body>
</html>
