<?php
require_once __DIR__ . '/../../includes/theme_init.php';

session_start();
require_once("../../config/db.php");
include("../includes/header.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /mutadarrib/auth/login.php");
    exit;
}

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';
$spec   = $_GET['spec'] ?? 'all';

$params = [];

/*
  توحيد البيانات من جدول:
  specialization_internships
  وجدول:
  it_internships
*/
$sql = "
SELECT *
FROM (
    SELECT 
        i.internship_id,
        'specialization' AS source_table,
        i.specialization_slug,
        i.provider_name,
        i.title,
        i.description,
        i.location,
        i.training_type,
        i.seats,
        i.status,
        i.start_date,
        i.end_date,
        i.created_at,
        COUNT(a.application_id) AS applications_count
    FROM specialization_internships i
    LEFT JOIN specialization_applications a 
        ON i.internship_id = a.internship_id
    GROUP BY i.internship_id

    UNION ALL

    SELECT
        it.internship_id,
        'it' AS source_table,
        'it' AS specialization_slug,
        COALESCE(u.full_name, 'مزود تدريب IT') AS provider_name,
        it.title,
        it.description,
        CONCAT(
            COALESCE(it.city, ''),
            CASE 
              WHEN it.city IS NOT NULL AND it.country IS NOT NULL THEN ' - '
              ELSE ''
            END,
            COALESCE(it.country, '')
        ) AS location,
        CASE 
            WHEN it.internship_type = 'onsite' THEN 'حضوري'
            WHEN it.internship_type = 'remote' THEN 'عن بعد'
            WHEN it.internship_type = 'hybrid' THEN 'هجين'
            ELSE it.internship_type
        END AS training_type,
        it.seats,
        CASE 
            WHEN it.status = 'published' THEN 'open'
            WHEN it.status = 'closed' THEN 'closed'
            WHEN it.status = 'draft' THEN 'draft'
            ELSE it.status
        END AS status,
        it.start_date,
        it.end_date,
        it.created_at,
        0 AS applications_count
    FROM it_internships it
    LEFT JOIN users u 
        ON it.provider_user_id = u.user_id
) AS all_internships
WHERE 1=1
";

if ($search !== '') {
    $sql .= " AND (title LIKE ? OR provider_name LIKE ? OR location LIKE ?) ";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status !== 'all') {
    $sql .= " AND status = ? ";
    $params[] = $status;
}

if ($spec !== 'all') {
    $sql .= " AND specialization_slug = ? ";
    $params[] = $spec;
}

$sql .= " ORDER BY created_at DESC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$internships = $stmt->fetchAll(PDO::FETCH_ASSOC);

function specName($slug) {
    return match ($slug) {
        'business' => 'الأعمال',
        'arts' => 'الآداب',
        'architecture-design' => 'العمارة والتصميم',
        'medical-support' => 'الدعم الطبي',
        'it' => 'تكنولوجيا المعلومات',
        default => $slug
    };
}

function statusName($status) {
    return match ($status) {
        'open' => 'مفتوحة',
        'closed' => 'مغلقة',
        'draft' => 'مسودة',
        default => $status
    };
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>جميع فرص التدريب | الإدارة</title>
<link rel="stylesheet" href="/mutadarrib/assets/css/admin.css">

<style>
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 14px;
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
.btn {
    padding: 6px 10px;
    border-radius: 7px;
    color: #fff;
    text-decoration: none;
    display: inline-block;
    margin: 2px;
}
.view-btn { background: #4c6ef5; }
.open-btn { background: #2d6a4f; }
.close-btn { background: #f77f00; }
.delete-btn { background: #d62828; }
.badge {
    padding: 4px 10px;
    border-radius: 20px;
    color: #fff;
    font-size: 12px;
}
.open { background: #2d6a4f; }
.closed { background: #d62828; }
.draft { background: #6c757d; }
.filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 15px;
}
.filters input, .filters select {
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 7px;
}
</style>
</head>

<body data-theme="<?= htmlspecialchars($theme ?? 'light') ?>">

<?php include("../includes/header.php"); ?>

<div class="admin-container">
<?php include("../includes/sidebar.php"); ?>

<div class="container">
    <h2>جميع فرص التدريب حسب التخصصات</h2>

    <form method="GET" class="filters">
        <input type="text" name="search" placeholder="بحث بالعنوان أو المزود أو الموقع" value="<?= htmlspecialchars($search) ?>">

        <select name="spec">
            <option value="all">كل التخصصات</option>
            <option value="business" <?= $spec==='business'?'selected':'' ?>>الأعمال</option>
            <option value="arts" <?= $spec==='arts'?'selected':'' ?>>الآداب</option>
            <option value="architecture-design" <?= $spec==='architecture-design'?'selected':'' ?>>العمارة والتصميم</option>
            <option value="medical-support" <?= $spec==='medical-support'?'selected':'' ?>>الدعم الطبي</option>
            <option value="it" <?= $spec==='it'?'selected':'' ?>>تكنولوجيا المعلومات</option>
        </select>

        <select name="status">
            <option value="all">كل الحالات</option>
            <option value="open" <?= $status==='open'?'selected':'' ?>>مفتوحة / منشورة</option>
            <option value="closed" <?= $status==='closed'?'selected':'' ?>>مغلقة</option>
            <option value="draft" <?= $status==='draft'?'selected':'' ?>>مسودة</option>
        </select>

        <button type="submit">بحث</button>
    </form>

    <?php if (!$internships): ?>
        <p>لا توجد فرص تدريب.</p>
    <?php else: ?>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>التخصص</th>
                <th>عنوان الفرصة</th>
                <th>مزود التدريب</th>
                <th>الموقع</th>
                <th>نوع التدريب</th>
                <th>المقاعد</th>
                <th>عدد الطلبات</th>
                <th>الحالة</th>
                <th>تاريخ البداية</th>
                <th>تاريخ النهاية</th>
                <th>الإجراءات</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($internships as $i => $row): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars(specName($row['specialization_slug'])) ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['provider_name']) ?></td>
                <td><?= htmlspecialchars($row['location'] ?: '-') ?></td>
                <td><?= htmlspecialchars($row['training_type'] ?: '-') ?></td>
                <td><?= (int)$row['seats'] ?></td>
                <td><?= (int)$row['applications_count'] ?></td>

                <td>
                    <span class="badge <?= htmlspecialchars($row['status']) ?>">
                        <?= htmlspecialchars(statusName($row['status'])) ?>
                    </span>
                </td>

                <td><?= htmlspecialchars($row['start_date'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['end_date'] ?? '-') ?></td>

                <td>
                    <?php if ($row['source_table'] === 'it'): ?>
                        <a class="btn view-btn" href="view_it_internship.php?id=<?= (int)$row['internship_id'] ?>">عرض</a>
                    <?php else: ?>
                        <a class="btn view-btn" href="view_internship.php?id=<?= (int)$row['internship_id'] ?>">عرض</a>
                    <?php endif; ?>

                    <?php if ($row['status'] === 'open'): ?>
                        <a class="btn close-btn"
                           href="toggle_internship_status.php?id=<?= (int)$row['internship_id'] ?>&source=<?= htmlspecialchars($row['source_table']) ?>&status=closed"
                           onclick="return confirm('هل تريد إغلاق هذه الفرصة؟')">
                           إغلاق
                        </a>
                    <?php else: ?>
                        <a class="btn open-btn"
                           href="toggle_internship_status.php?id=<?= (int)$row['internship_id'] ?>&source=<?= htmlspecialchars($row['source_table']) ?>&status=open"
                           onclick="return confirm('هل تريد فتح هذه الفرصة؟')">
                           فتح
                        </a>
                    <?php endif; ?>

                    <a class="btn delete-btn"
                       href="delete_internship.php?id=<?= (int)$row['internship_id'] ?>&source=<?= htmlspecialchars($row['source_table']) ?>"
                       onclick="return confirm('هل أنت متأكد من حذف هذه الفرصة؟')">
                       حذف
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>
</div>
</div>

<?php include("../includes/footer.php"); ?>
</body>
</html>