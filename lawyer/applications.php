<?php
require_once("includes/auth_check.php");
require_once("../config/db.php");

if ($_SESSION['role'] !== 'lawyer') {
    die("❌ غير مصرح");
}

// $lawyer_id = $_SESSION['user_id'];

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT lawyer_id
    FROM lawyers
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$lawyer_id = $stmt->fetchColumn();

if(!$lawyer_id){
    die("لم يتم العثور على حساب محام مرتبط.");
}


$stmt = $pdo->prepare("
    SELECT 
    ta.application_id AS application_id,
    ta.status,
    ta.applied_at,
    t.title,
    tr.full_name,
    tr.phone,
    tr.email
    FROM training_applications ta

    JOIN trainings t ON ta.training_id = t.training_id
    JOIN trainees tr ON ta.trainee_id = tr.trainee_id

    WHERE t.lawyer_id = ?
    ORDER BY ta.applied_at DESC
");

$stmt->execute([$lawyer_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>طلبات التدريب</title>
<link rel="stylesheet" href="../assets/css/lawyers.css">
</head>
<body>

<?php include("includes/header.php"); ?>
<?php include("includes/sidebar.php"); ?>

<main class="content">

<h2>📋 طلبات المتدربين</h2>

<table class="table">

<thead>
<tr>
<th>اسم المتدرب</th>
<th>الهاتف</th>
<th>التدريب</th>
<th>تاريخ الطلب</th>
<th>الحالة</th>
<th>الإجراء</th>
</tr>
</thead>

<tbody>

<?php foreach($applications as $row): ?>

<tr>
<td><?= htmlspecialchars($row['full_name']) ?></td>

<td><?= htmlspecialchars($row['phone']) ?></td>

<td><?= htmlspecialchars($row['title']) ?></td>

<td><?= $row['applied_at'] ?></td>

<td>
<span class="status <?= $row['status'] ?>">
<?= $row['status'] ?>
</span>
</td>

<td>
<?php if($row['status'] == 'pending'): ?>
    <a class="btn approve" href="review.php?id=<?=$row['application_id']?>&action=accept">قبول</a>
    <a class="btn reject"  href="review.php?id=<?=$row['application_id']?>&action=reject">رفض</a>
<?php endif; ?>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</main>

<?php include("includes/footer.php"); ?>

</body>
</html>
