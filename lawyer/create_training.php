<?php
require_once("includes/auth_check.php");
require_once("../config/db.php");

// السماح للمحامي فقط
if ($_SESSION['role'] !== 'lawyer') {
    die("❌ غير مصرح بالوصول");
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

$message = "";

// ========================
// إنشاء تدريب جديد
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $duration    = intval($_POST['duration_months']);
    $location    = trim($_POST['location']);
    $start_date  = $_POST['start_date'];
    $end_date    = $_POST['end_date'];
    $seats       = intval($_POST['seats']);

    if ($seats <= 0) {
        $message = "<p class='error'>❌ عدد المقاعد غير صحيح.</p>";
    } else {

        $stmt = $pdo->prepare("
            INSERT INTO trainings
                (lawyer_id, title, description, duration_months, location,
                start_date, end_date, seats, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')
        ");

        $stmt->execute([
            $lawyer_id,
            $title,
            $description,
            $duration,
            $location,
            $start_date,
            $end_date,
            $seats
        ]);

        $message = "<p class='success'>✅ تم إنشاء التدريب بنجاح.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إنشاء تدريب جديد</title>
<link rel="stylesheet" href="../assets/css/lawyers.css">
</head>
<body>

<?php include("includes/header.php"); ?>
<?php include("includes/sidebar.php"); ?>

<main class="content">

<h2>📚 إنشاء تدريب جديد</h2>

<?= $message ?>

<form method="POST" class="form-card">

<label>عنوان التدريب:</label>
<input type="text" name="title" required>

<label>وصف التدريب:</label>
<textarea name="description" rows="4"></textarea>

<label>مدة التدريب (بالأشهر):</label>
<input type="number" name="duration_months" required>

<label>الموقع:</label>
<input type="text" name="location">

<label>تاريخ البداية:</label>
<input type="date" name="start_date">

<label>تاريخ النهاية:</label>
<input type="date" name="end_date">

<label>عدد المقاعد:</label>
<input type="number" name="seats" required>

<button type="submit">✅ إنشاء التدريب</button>

</form>

</main>

<?php include("includes/footer.php"); ?>

</body>
</html>