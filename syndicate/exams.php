<?php
require_once("includes/auth_check.php");
require_once("../config/db.php");

// جلب جميع طلبات الامتحان للنقابة
$stmt = $pdo->prepare("
    SELECT 
        r.request_id,
        r.status,
        r.exam_date,
        r.created_at,

        tr.full_name      AS trainee_name,
        tr.national_id    AS trainee_national,
        tr.phone          AS trainee_phone,
        tr.email          AS trainee_email,

        l.full_name       AS lawyer_name

    FROM syndicate_exam_requests r
    JOIN trainees tr ON r.trainee_id = tr.trainee_id
    JOIN lawyers  l  ON r.lawyer_id  = l.lawyer_id
    ORDER BY r.created_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>طلبات امتحان المزاولة</title>
<link rel="stylesheet" href="../assets/css/lawyers.css">
</head>
<body>

<?php include("includes/header.php"); ?>

<div class="layout">
    <?php include("includes/sidebar.php"); ?>

    <main class="content">

        <h2>📋 طلبات امتحان المزاولة</h2>

        <?php if (empty($requests)): ?>
            <p>لا يوجد طلبات امتحان حالياً.</p>
        <?php else: ?>

        <table class="table">
            <thead>
                <tr>
                    <th>المتدرب</th>
                    <th>الرقم الوطني</th>
                    <th>الهاتف</th>
                    <th>المحامي المشرف</th>
                    <th>الحالة</th>
                    <th>تاريخ إنشاء الطلب</th>
                    <th>تاريخ الامتحان</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($requests as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['trainee_name']) ?></td>
                        <td><?= htmlspecialchars($row['trainee_national']) ?></td>
                        <td><?= htmlspecialchars($row['trainee_phone']) ?></td>
                        <td><?= htmlspecialchars($row['lawyer_name']) ?></td>
                        <td>
                            <span class="status <?= $row['status'] ?>">
                                <?php
                                if ($row['status'] === 'waiting_exam')  echo "جاهز للامتحان";
                                elseif ($row['status'] === 'scheduled') echo "امتحان مجدول";
                                elseif ($row['status'] === 'passed')    echo "ناجح";
                                elseif ($row['status'] === 'failed')    echo "راسب";
                                else echo $row['status'];
                                ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td><?= $row['exam_date'] ? htmlspecialchars($row['exam_date']) : '-' ?></td>
                        <td>
                            <a class="btn" href="exam_review.php?id=<?= (int)$row['request_id'] ?>">
                                تحديث نتيجة الامتحان
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php endif; ?>

    </main>
</div>

<?php include("includes/footer.php"); ?>

</body>
</html>
