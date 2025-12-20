<?php
require_once("../config/db.php");
include("includes/auth_check.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("غير مصرح");
}

$request_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($request_id <= 0) {
    die("رقم الطلب غير صالح.");
}

// جلب بيانات طلب الامتحان + المتدرب + المستخدم
$stmt = $pdo->prepare("
    SELECT 
        r.request_id,
        r.status       AS req_status,
        r.exam_date,
        r.application_id,
        r.trainee_id,
        r.lawyer_id,
        r.created_at,

        tr.user_id     AS trainee_user_id,
        tr.full_name   AS trainee_full_name,
        tr.first_name,
        tr.father_name,
        tr.grandfather_name,
        tr.family_name,
        tr.national_id,
        tr.phone,
        tr.email,
        tr.home_address,
        tr.no_conviction_doc,
        tr.good_conduct_doc,
        tr.social_security,
        tr.social_security_number,
        tr.highschool_certificate,
        tr.university_degree,

        u.password     AS user_password

    FROM syndicate_exam_requests r
    JOIN trainees tr ON r.trainee_id = tr.trainee_id
    JOIN users   u  ON tr.user_id = u.user_id
    WHERE r.request_id = ?
");
$stmt->execute([$request_id]);
$req = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$req) {
    die("طلب الامتحان غير موجود.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $result    = $_POST['result'] ?? '';
    $exam_date = $_POST['exam_date'] ?? null;

    if (!in_array($result, ['scheduled','passed','failed'], true)) {
        die("نتيجة غير صالحة.");
    }

    try {
        $pdo->beginTransaction();

        // 1) تحديث جدول طلبات الامتحان
        $up = $pdo->prepare("
            UPDATE syndicate_exam_requests
            SET status = ?, exam_date = ?
            WHERE request_id = ?
        ");
        $up->execute([$result, $exam_date ?: null, $request_id]);

        // لو كانت scheduled أو failed => لا ترقية
        if ($result !== 'passed') {
            $pdo->commit();
            header("Location: exams.php?updated=1");
            exit;
        }

        // 2) في حالة النجاح: ترقية المستخدم إلى محامي
        $user_id   = (int)$req['trainee_user_id'];
        $trainee_id = (int)$req['trainee_id'];

        $pdo->prepare("
            UPDATE users
            SET role = 'lawyer', updated_at = NOW()
            WHERE user_id = ?
        ")->execute([$user_id]);

        // 3) جلب syndicate_id من جدول النقابة (إن وجد)
        $synStmt = $pdo->prepare("
            SELECT syndicate_id
            FROM lawyers_syndicate
            WHERE national_id = ?
            LIMIT 1
        ");
        $synStmt->execute([$req['national_id']]);
        $syndicate_id = $synStmt->fetchColumn();
        if (!$syndicate_id) $syndicate_id = null;

        // 4) إنشاء/تحديث سجل في lawyers
        $chk = $pdo->prepare("SELECT lawyer_id FROM lawyers WHERE user_id = ? LIMIT 1");
        $chk->execute([$user_id]);
        $existingLawyerId = $chk->fetchColumn();

        if ($existingLawyerId) {
            $updLawyer = $pdo->prepare("
                UPDATE lawyers
                SET 
                    syndicate_id           = ?,
                    full_name              = ?,
                    first_name             = ?,
                    father_name            = ?,
                    grandfather_name       = ?,
                    family_name            = ?,
                    national_id            = ?,
                    phone                  = ?,
                    email                  = ?,
                    home_address           = ?,
                    no_conviction_doc      = ?,
                    good_conduct_doc       = ?,
                    social_security        = ?,
                    highschool_certificate = ?,
                    university_degree      = ?,
                    social_security_number = ?,
                    password               = ?,
                    verified               = 1,
                    updated_at             = NOW()
                WHERE lawyer_id = ?
            ");
            $updLawyer->execute([
                $syndicate_id,
                $req['trainee_full_name'],
                $req['first_name'],
                $req['father_name'],
                $req['grandfather_name'],
                $req['family_name'],
                $req['national_id'],
                $req['phone'],
                $req['email'],
                $req['home_address'],
                $req['no_conviction_doc'],
                $req['good_conduct_doc'],
                $req['social_security'],
                $req['highschool_certificate'],
                $req['university_degree'],
                $req['social_security_number'],
                $req['user_password'],
                $existingLawyerId
            ]);
        } else {
            $insLawyer = $pdo->prepare("
                INSERT INTO lawyers (
                    user_id,
                    syndicate_id,
                    office_address,
                    password,
                    verified,
                    created_at,
                    full_name,
                    first_name,
                    father_name,
                    grandfather_name,
                    family_name,
                    national_id,
                    phone,
                    email,
                    home_address,
                    no_conviction_doc,
                    good_conduct_doc,
                    social_security,
                    highschool_certificate,
                    university_degree,
                    social_security_number
                ) VALUES (
                    ?, ?, NULL, ?, 1, NOW(),
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");
            $insLawyer->execute([
                $user_id,
                $syndicate_id,
                $req['user_password'],

                $req['trainee_full_name'],
                $req['first_name'],
                $req['father_name'],
                $req['grandfather_name'],
                $req['family_name'],
                $req['national_id'],
                $req['phone'],
                $req['email'],
                $req['home_address'],
                $req['no_conviction_doc'],
                $req['good_conduct_doc'],
                $req['social_security'],
                $req['highschool_certificate'],
                $req['university_degree'],
                $req['social_security_number']
            ]);
        }

        // 5) أرشفة المتدرب (طريقة A) بدل الحذف
        $pdo->prepare("
            UPDATE trainees
            SET is_archived = 1, archived_at = NOW()
            WHERE trainee_id = ?
        ")->execute([$trainee_id]);

        $pdo->commit();

        header("Location: exams.php?updated=1");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("خطأ أثناء حفظ نتيجة الامتحان: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>نتيجة امتحان المزاولة | الإدارة</title>
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<?php include("includes/header.php"); ?>
<?php include("includes/sidebar.php"); ?>

<div class="admin-container">

    <h2>تسجيل / تحديث نتيجة امتحان المزاولة</h2>

    <div class="card">
        <p><strong>اسم المتدرب:</strong> <?= htmlspecialchars($req['trainee_full_name']) ?></p>
        <p><strong>الرقم الوطني:</strong> <?= htmlspecialchars($req['national_id']) ?></p>
        <p><strong>الحالة الحالية:</strong> <?= htmlspecialchars($req['req_status']) ?></p>
        <p><strong>تاريخ الامتحان الحالي:</strong> <?= $req['exam_date'] ? htmlspecialchars($req['exam_date']) : '-' ?></p>
    </div>

    <form method="POST">
        <label>الحالة الجديدة:</label>
        <select name="result" required>
            <option value="">اختر الحالة</option>
            <option value="scheduled" <?= $req['req_status'] === 'scheduled' ? 'selected' : '' ?>>scheduled</option>
            <option value="passed"    <?= $req['req_status'] === 'passed'    ? 'selected' : '' ?>>passed</option>
            <option value="failed"    <?= $req['req_status'] === 'failed'    ? 'selected' : '' ?>>failed</option>
        </select>

        <br><br>

        <label>تاريخ الامتحان:</label>
        <input type="date" name="exam_date" value="<?= htmlspecialchars($req['exam_date'] ?? '') ?>">

        <br><br>

        <button class="btn">حفظ</button>
        <a class="btn" href="exams.php">رجوع</a>
    </form>

</div>

<?php include("includes/footer.php"); ?>
</body>
</html>
