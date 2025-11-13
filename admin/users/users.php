<?php
session_start();
require_once("../../config/db.php");
include("../includes/header.php");

// حماية الصفحة: فقط المدير يمكنه الدخول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// البحث
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE full_name LIKE ?");
    $stmt->execute(["%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY full_name ASC");
}

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>قائمة المستخدمين</title>
  <link rel="stylesheet" href="../../assets/css/admin.css">

  <style>
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
th, td {
    border: 1px solid #ccc;
    padding: 10px;
    text-align: center;
}
th {
    background: #0077b6;
    color: white;
}
a.btn {
    padding: 5px 10px;
    border-radius: 6px;
    color: white;
    text-decoration: none;
    font-size: 14px;
}
.edit-btn { background-color: #00b4d8; }
.delete-btn { background-color: #d62828; }
.btn:hover { opacity: 0.8; }
  </style>
</head>
<body>

<h2>👥 قائمة المستخدمين</h2>

<form method="GET" action="">
  <input type="text" name="search" placeholder="ابحث باسم المستخدم أو اسم الدخول..." 
         value="<?= htmlspecialchars($search) ?>">
  <button type="submit">🔍 بحث</button>
</form>

<div class="admin-container">
  <?php include("../includes/sidebar.php"); ?>

  <div class="container">

    <?php if (count($users) > 0): ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>الاسم الكامل</th>
            <th>الدور</th>
            <th>الإجراءات</th>
          </tr>
        </thead>

        <tbody>
          <?php foreach ($users as $index => $u): ?>
            <tr>
              <td><?= $index + 1 ?></td>
              <td><?= htmlspecialchars($u['full_name']) ?></td>
              <td><?= htmlspecialchars($u['role']) ?></td>

              <td>
                <a href="edit_user.php?id=<?= $u['user_id'] ?>" class="btn edit-btn">تعديل</a>
                <a href="delete_user.php?id=<?= $u['user_id'] ?>" class="btn delete-btn"
                   onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">حذف</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    <?php else: ?>
      <p class="no-data">لا توجد نتائج مطابقة.</p>
    <?php endif; ?>

  </div>
</div>

</body>
</html>
