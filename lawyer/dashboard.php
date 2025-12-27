<?php
require_once __DIR__ . '/../includes/theme_init.php';

require_once("includes/auth_check.php");
require_once("../config/db.php");

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
    die("❌ لم يتم العثور على حساب محامي مرتبط.");
}

// عدد الطلبات
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM training_applications ta
    JOIN trainings t ON ta.training_id = t.training_id
    WHERE t.lawyer_id = ?
");
$stmt->execute([$lawyer_id]);
$applicationsCount = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>لوحة المحامي</title>
<link rel="stylesheet" href="../assets/css/lawyers.css">
</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<?php include("includes/header.php"); ?>
<div class="layout">
<?php include("includes/sidebar.php"); ?>

<main class="content">

<h2>لوحة تحكم المحامي</h2>

<div class="stats">
    <div class="card">
        <h3>عدد طلبات التدريب</h3>
        <p><?= $applicationsCount ?></p>
    </div>
</div>

</main>
</div>
<?php include("includes/footer.php"); ?>

</body>
</html>
