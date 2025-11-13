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

if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM lawyers_master WHERE lawyer_name LIKE ? OR national_id LIKE ?");
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM lawyers_master ORDER BY lawyer_name ASC");
}

$lawyers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>قائمة المحامين المسجلين في النقابة</title>
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

<h2>⚖️ قائمة المحامين المسجلين في النقابة</h2>

<form method="GET" action="">
  <input type="text" name="search" placeholder="ابحث باسم المحامي أو الرقم الوطني..." value="<?= htmlspecialchars($search) ?>">
  <button type="submit">🔍 بحث</button>
  <a href="add_master_lawyer.php" class="add-btn">➕ إضافة محامي جديد</a>
</form>


<div class="admin-container">
  <?php include("../includes/sidebar.php"); ?>
  <div class="container">
    <?php if (count($lawyers) > 0): ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>الاسم الكامل</th>
            <th>الرقم الوطني</th>
            <th>رقم السجل</th>
            <th>عنوان المكتب</th>
            <th>تاريخ التسجيل</th>
            <th>الإجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lawyers as $index => $lawyer): ?>
            <tr>
              <td><?= $index + 1 ?></td>
              <td><?= htmlspecialchars($lawyer['lawyer_name']) ?></td>
              <td><?= htmlspecialchars($lawyer['national_id']) ?></td>
              <td><?= htmlspecialchars($lawyer['master_id']) ?></td>
              <td><?= htmlspecialchars($lawyer['office_address']) ?></td>
              <td><?= htmlspecialchars($lawyer['created_at'] ?? '-') ?></td>
              <td>
                <a href="edit_master_lawyer.php?id=<?= $lawyer['master_id'] ?>" class="btn edit-btn">تعديل</a>
                <a href="delete_master_lawyer.php?id=<?= $lawyer['master_id'] ?>" class="btn delete-btn" onclick="return confirm('هل أنت متأكد من حذف هذا المحامي؟')">حذف</a>
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
