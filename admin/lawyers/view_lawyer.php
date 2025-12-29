<?php
require_once __DIR__ . '/../../includes/theme_init.php';

session_start();
require_once("../../config/db.php");
include("../includes/header.php");

// حماية الدخول: المدير فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$lawyer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($lawyer_id <= 0) {
    die("رقم المحامي غير صالح.");
}

/**
 * تجهيز رابط الملف كما هو مخزن في DB
 * - إذا كان الرابط يبدأ بـ http أو / نستخدمه كما هو.
 * - غير ذلك نضيف ../../ لأننا داخل admin/lawyers/
 */
function fileUrl(?string $path): ?string {
    $path = trim((string)$path);
    if ($path === '') return null;

    if (preg_match('~^(https?://|/)~i', $path)) {
        return $path;
    }

    return "../../" . ltrim($path, "/");
}

// جلب بيانات المحامي + بيانات المستخدم
$stmt = $pdo->prepare("
    SELECT
        l.*,
        u.full_name   AS user_full_name,
        u.national_id AS user_national_id,
        u.phone       AS user_phone,
        u.email       AS user_email,
        u.address     AS user_address,
        u.role        AS user_role,
        u.created_at  AS user_created_at,
        u.updated_at  AS user_updated_at
    FROM lawyers l
    JOIN users u ON u.user_id = l.user_id
    WHERE l.lawyer_id = ?
    LIMIT 1
");
$stmt->execute([$lawyer_id]);
$lawyer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lawyer) {
    die("المحامي غير موجود.");
}

// إحصائيات تخص هذا المحامي
$cntTrainings = $pdo->prepare("SELECT COUNT(*) FROM trainings WHERE lawyer_id = ?");
$cntTrainings->execute([$lawyer_id]);
$total_trainings = (int)$cntTrainings->fetchColumn();

$cntOpenTrainings = $pdo->prepare("SELECT COUNT(*) FROM trainings WHERE lawyer_id = ? AND status = 'open'");
$cntOpenTrainings->execute([$lawyer_id]);
$open_trainings = (int)$cntOpenTrainings->fetchColumn();

$cntApps = $pdo->prepare("
    SELECT COUNT(*)
    FROM training_applications ta
    JOIN trainings t ON ta.training_id = t.training_id
    WHERE t.lawyer_id = ?
");
$cntApps->execute([$lawyer_id]);
$total_apps = (int)$cntApps->fetchColumn();

// آخر التدريبات (اختياري)
$trainStmt = $pdo->prepare("
    SELECT training_id, title, location, status, seats, created_at
    FROM trainings
    WHERE lawyer_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$trainStmt->execute([$lawyer_id]);
$recent_trainings = $trainStmt->fetchAll(PDO::FETCH_ASSOC);

// مستندات
$noConvUrl = fileUrl($lawyer['no_conviction_doc'] ?? null);
$goodCondUrl = fileUrl($lawyer['good_conduct_doc'] ?? null);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>عرض بيانات المحامي</title>
<link rel="stylesheet" href="../../assets/css/admin.css"></head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<div class="admin-container">
<?php include("../includes/sidebar.php"); ?>

<div class="container">

    <h2>عرض بيانات المحامي</h2>

    <a class="btn btn-secondary" href="lawyers.php">رجوع إلى قائمة المحامين</a>

    <div class="card">
        <h3>معلومات عامة</h3>

        <div class="grid">
            <div class="item">
                <div class="label">رقم المحامي (lawyer_id)</div>
                <div class="value"><?= (int)$lawyer['lawyer_id'] ?></div>
            </div>

            <div class="item">
                <div class="label">رقم المستخدم (user_id)</div>
                <div class="value"><?= (int)$lawyer['user_id'] ?></div>
            </div>

            <div class="item">
                <div class="label">الاسم الكامل</div>
                <div class="value"><?= htmlspecialchars($lawyer['full_name'] ?? $lawyer['user_full_name'] ?? '-') ?></div>
            </div>

            <div class="item">
                <div class="label">الرقم الوطني</div>
                <div class="value"><?= htmlspecialchars($lawyer['national_id'] ?? $lawyer['user_national_id'] ?? '-') ?></div>
            </div>

            <div class="item">
                <div class="label">الهاتف</div>
                <div class="value"><?= htmlspecialchars($lawyer['phone'] ?? $lawyer['user_phone'] ?? '-') ?></div>
            </div>

            <div class="item">
                <div class="label">البريد الإلكتروني</div>
                <div class="value"><?= htmlspecialchars($lawyer['email'] ?? $lawyer['user_email'] ?? '-') ?></div>
            </div>

            <div class="item">
                <div class="label">رقم النقابة (syndicate_id)</div>
                <div class="value"><?= htmlspecialchars($lawyer['syndicate_id'] ?? '-') ?></div>
            </div>

            <div class="item">
                <div class="label">موثق</div>
                <div class="value">
                    <?php if ((int)$lawyer['verified'] === 1): ?>
                        <span class="badge badge-yes">نعم</span>
                    <?php else: ?>
                        <span class="badge badge-no">لا</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="item">
                <div class="label">عنوان المكتب</div>
                <div class="value"><?= htmlspecialchars($lawyer['office_address'] ?? '-') ?></div>
            </div>

            <div class="item">
                <div class="label">عنوان السكن</div>
                <div class="value"><?= htmlspecialchars($lawyer['home_address'] ?? $lawyer['user_address'] ?? '-') ?></div>
            </div>

            <div class="item">
                <div class="label">تاريخ إنشاء حساب المستخدم</div>
                <div class="value"><?= htmlspecialchars($lawyer['user_created_at'] ?? '-') ?></div>
            </div>

            <div class="item">
                <div class="label">آخر تحديث</div>
                <div class="value"><?= htmlspecialchars($lawyer['updated_at'] ?? $lawyer['user_updated_at'] ?? '-') ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>الأسماء التفصيلية</h3>

        <div class="grid">
            <div class="item">
                <div class="label">الاسم الأول</div>
                <div class="value"><?= htmlspecialchars($lawyer['first_name'] ?? '-') ?></div>
            </div>
            <div class="item">
                <div class="label">اسم الأب</div>
                <div class="value"><?= htmlspecialchars($lawyer['father_name'] ?? '-') ?></div>
            </div>
            <div class="item">
                <div class="label">اسم الجد</div>
                <div class="value"><?= htmlspecialchars($lawyer['grandfather_name'] ?? '-') ?></div>
            </div>
            <div class="item">
                <div class="label">اسم العائلة</div>
                <div class="value"><?= htmlspecialchars($lawyer['family_name'] ?? '-') ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>المستندات والمؤهلات</h3>

        <div class="grid">
            <div class="item">
                <div class="label">شهادة الثانوية</div>
                <div class="value"><?= htmlspecialchars($lawyer['highschool_certificate'] ?? '-') ?></div>
            </div>

            <div class="item">
                <div class="label">الدرجة الجامعية</div>
                <div class="value"><?= htmlspecialchars($lawyer['university_degree'] ?? '-') ?></div>
            </div>

            <div class="item">
                <div class="label">الضمان الاجتماعي</div>
                <div class="value"><?= htmlspecialchars($lawyer['social_security'] ?? '-') ?></div>
            </div>

            <div class="item">
                <div class="label">رقم الضمان الاجتماعي</div>
                <div class="value"><?= htmlspecialchars($lawyer['social_security_number'] ?? '-') ?></div>
            </div>

            <div class="item">
                <div class="label">شهادة عدم محكومية</div>
                <div class="value">
                    <?php if ($noConvUrl): ?>
                        <a class="file-link" href="<?= htmlspecialchars($noConvUrl) ?>" target="_blank">فتح الملف</a>
                        <div style="font-size:12px;color:#666;margin-top:4px;">
                            <?= htmlspecialchars($lawyer['no_conviction_doc']) ?>
                        </div>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>

            <div class="item">
                <div class="label">شهادة حسن سلوك</div>
                <div class="value">
                    <?php if ($goodCondUrl): ?>
                        <a class="file-link" href="<?= htmlspecialchars($goodCondUrl) ?>" target="_blank">فتح الملف</a>
                        <div style="font-size:12px;color:#666;margin-top:4px;">
                            <?= htmlspecialchars($lawyer['good_conduct_doc']) ?>
                        </div>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>إحصائيات النشاط</h3>

        <div class="grid">
            <div class="item">
                <div class="label">إجمالي التدريبات</div>
                <div class="value"><?= $total_trainings ?></div>
            </div>
            <div class="item">
                <div class="label">التدريبات المفتوحة</div>
                <div class="value"><?= $open_trainings ?></div>
            </div>
            <div class="item">
                <div class="label">إجمالي طلبات التدريب على تدريباته</div>
                <div class="value"><?= $total_apps ?></div>
            </div>
            <div class="item">
                <div class="label">حالة الدور</div>
                <div class="value"><?= htmlspecialchars($lawyer['user_role'] ?? '-') ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>آخر التدريبات</h3>

        <?php if (empty($recent_trainings)): ?>
            <p>لا يوجد تدريبات لهذا المحامي.</p>
        <?php else: ?>
            <div class="table-card"><div class="table-wrap">
<table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>العنوان</th>
                        <th>الموقع</th>
                        <th>الحالة</th>
                        <th>المقاعد</th>
                        <th>تاريخ الإنشاء</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recent_trainings as $i => $t): ?>
                    <tr>
                        <td><?= (int)$t['training_id'] ?></td>
                        <td><?= htmlspecialchars($t['title']) ?></td>
                        <td><?= htmlspecialchars($t['location'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($t['status']) ?></td>
                        <td><?= (int)$t['seats'] ?></td>
                        <td><?= htmlspecialchars($t['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
</div></div>
        <?php endif; ?>
    </div>

</div>
</div>

</body>
</html>
