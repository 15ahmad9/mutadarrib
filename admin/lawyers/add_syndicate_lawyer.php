<?php
require_once __DIR__ . '/../../includes/theme_init.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../config/db.php");

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$message = "";

/**
 * إرجاع أسماء الأعمدة الموجودة فعليًا في جدول
 */
function getTableColumns(PDO $pdo, string $table): array {
    $stmt = $pdo->query("DESCRIBE `$table`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return array_map(fn($r) => $r['Field'], $rows);
}

/**
 * رفع ملف وإرجاع المسار النسبي
 */
function saveUpload(string $fileKey, string $destDirRel, array $allowedExt = ['jpg','jpeg','png','pdf'], int $maxBytes = 5_000_000): ?string {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmp  = $_FILES[$fileKey]['tmp_name'];
    $name = $_FILES[$fileKey]['name'];
    $size = (int)$_FILES[$fileKey]['size'];

    if ($size <= 0 || $size > $maxBytes) {
        throw new Exception("حجم الملف غير صالح: {$fileKey}");
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new Exception("امتداد غير مسموح: {$fileKey}");
    }

    $destDirAbs = __DIR__ . "/../.." . $destDirRel;
    if (!is_dir($destDirAbs)) {
        if (!mkdir($destDirAbs, 0775, true)) {
            throw new Exception("فشل إنشاء مجلد الرفع.");
        }
    }

    $newName = bin2hex(random_bytes(16)) . "." . $ext;
    $destAbs = rtrim($destDirAbs, "/\\") . DIRECTORY_SEPARATOR . $newName;

    if (!move_uploaded_file($tmp, $destAbs)) {
        throw new Exception("فشل رفع الملف: {$fileKey}");
    }

    return rtrim($destDirRel, "/") . "/" . $newName;
}

function normalizeSpaces($s) {
    return trim(preg_replace('/\s+/', ' ', (string)$s));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name       = normalizeSpaces($_POST['first_name'] ?? '');
    $father_name      = normalizeSpaces($_POST['father_name'] ?? '');
    $grandfather_name = normalizeSpaces($_POST['grandfather_name'] ?? '');
    $family_name      = normalizeSpaces($_POST['family_name'] ?? '');
    $full_name        = normalizeSpaces("$first_name $father_name $grandfather_name $family_name");

    $national_id     = normalizeSpaces($_POST['national_id'] ?? '');
    $phone           = normalizeSpaces($_POST['phone'] ?? '');
    $email           = normalizeSpaces($_POST['email'] ?? '');
    $home_address    = normalizeSpaces($_POST['home_address'] ?? '');
    $office_address  = normalizeSpaces($_POST['office_address'] ?? '');

    $highschool      = $_POST['highschool_certificate'] ?? 'لا';
    $university      = $_POST['university_degree'] ?? null;

    $social_security = $_POST['social_security'] ?? 'لا';
    $social_number   = normalizeSpaces($_POST['social_security_number'] ?? '');
    $social_number   = ($social_security === 'نعم') ? ($social_number ?: null) : null;

    $password        = (string)($_POST['password'] ?? '');
    $password_hashed = password_hash($password, PASSWORD_BCRYPT);

    try {
        if ($full_name === '' || $national_id === '' || $password === '') {
            throw new Exception("يرجى تعبئة الاسم الرباعي والرقم الوطني وكلمة المرور.");
        }

        // رفع ملفات الوثائق (مطلوبة مثل طلب الانتساب)
        $no_conviction_path = saveUpload('no_conviction_doc', '/uploads/user_docs');
        $good_conduct_path  = saveUpload('good_conduct_doc',  '/uploads/user_docs');

        if (!$no_conviction_path || !$good_conduct_path) {
            throw new Exception("يجب رفع عدم المحكومية وحسن السيرة والسلوك.");
        }

        // التأكد من عدم وجود الرقم الوطني في سجل النقابة
        $check = $pdo->prepare("SELECT syndicate_id FROM lawyers_syndicate WHERE national_id = ? LIMIT 1");
        $check->execute([$national_id]);
        if ($check->fetch()) {
            throw new Exception("المحامي موجود مسبقًا في سجل النقابة!");
        }

        $pdo->beginTransaction();

        // 1) إضافة في جدول النقابة (lawyers_syndicate)
        $stmt1 = $pdo->prepare("
            INSERT INTO lawyers_syndicate 
            (full_name, first_name, father_name, grandfather_name, family_name,
             lawyer_name, national_id, phone, email, office_address,
             highschool_certificate, university_degree,
             no_conviction_doc, good_conduct_doc,
             social_security, social_security_number, created_at, role)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'lawyer')
        ");
        $stmt1->execute([
            $full_name, $first_name, $father_name, $grandfather_name, $family_name,
            $full_name, $national_id, $phone, $email, $office_address,
            $highschool, $university,
            $no_conviction_path, $good_conduct_path,
            $social_security, $social_number
        ]);

        $syndicate_id = (int)$pdo->lastInsertId();

        // 2) إضافة المستخدم في جدول users (ديناميكي حسب الأعمدة الموجودة)
        $usersCols = getTableColumns($pdo, 'users');

        $map = [
            'full_name'              => $full_name,
            'first_name'             => $first_name,
            'father_name'            => $father_name,
            'grandfather_name'       => $grandfather_name,
            'family_name'            => $family_name,
            'national_id'            => $national_id,
            'phone'                  => $phone,
            'email'                  => $email,
            'home_address'           => $home_address,
            'office_address'         => $office_address,
            'highschool_certificate' => $highschool,
            'university_degree'      => $university,
            'no_conviction_doc'      => $no_conviction_path,
            'good_conduct_doc'       => $good_conduct_path,
            'social_security'        => $social_security,
            'social_security_number' => $social_number,
            'password'               => $password_hashed,
            'role'                   => 'lawyer',
        ];

        $insertCols = [];
        $placeholders = [];
        $values = [];

        foreach ($map as $col => $val) {
            if (in_array($col, $usersCols, true)) {
                $insertCols[] = $col;
                $placeholders[] = '?';
                $values[] = $val;
            }
        }

        if (empty($insertCols)) {
            throw new Exception("لا يمكن الإدخال في users: لم يتم العثور على أعمدة مناسبة.");
        }

        $sqlUsers = "INSERT INTO users (" . implode(',', $insertCols) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt2 = $pdo->prepare($sqlUsers);
        $stmt2->execute($values);

        $user_id = (int)$pdo->lastInsertId();

        // 3) إضافة المحامي في جدول lawyers
        $stmt3 = $pdo->prepare("
            INSERT INTO lawyers
            (user_id, syndicate_id, office_address, password, verified,
             full_name, first_name, father_name, grandfather_name, family_name,
             national_id, phone, email, home_address,
             no_conviction_doc, good_conduct_doc,
             social_security, highschool_certificate, university_degree, social_security_number,
             created_at)
            VALUES
            (?, ?, ?, ?, 1,
             ?, ?, ?, ?, ?,
             ?, ?, ?, ?,
             ?, ?,
             ?, ?, ?, ?,
             NOW())
        ");
        $stmt3->execute([
            $user_id, $syndicate_id, $office_address, $password_hashed,
            $full_name, $first_name, $father_name, $grandfather_name, $family_name,
            $national_id, $phone, $email, $home_address,
            $no_conviction_path, $good_conduct_path,
            $social_security, $highschool, $university, $social_number
        ]);

        $pdo->commit();
        $message = "<div class='alert alert-success'>✅ تمت إضافة المحامي بنجاح!</div>";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "<div class='alert alert-error'>❌ خطأ: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إضافة محامي جديد</title>
<link rel="stylesheet" href="/mutadarrib/assets/css/admin.css">
<link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<?php include(__DIR__ . "/../includes/header.php"); ?>
<div class="admin-container">
  <?php include(__DIR__ . "/../includes/sidebar.php"); ?>

  <div class="container">
    <h2>إضافة محامي جديد</h2>
    <?= $message ?>

    <form method="POST" enctype="multipart/form-data" class="auth-form">
      <div class="auth-grid">

        <div class="auth-field col-3">
          <label>الاسم الأول</label>
          <input type="text" name="first_name" required>
        </div>

        <div class="auth-field col-3">
          <label>اسم الأب</label>
          <input type="text" name="father_name" required>
        </div>

        <div class="auth-field col-3">
          <label>اسم الجد</label>
          <input type="text" name="grandfather_name" required>
        </div>

        <div class="auth-field col-3">
          <label>اسم العائلة</label>
          <input type="text" name="family_name" required>
        </div>

        <div class="auth-field col-6">
          <label>الرقم الوطني</label>
          <input type="text" name="national_id" required>
        </div>

        <div class="auth-field col-6">
          <label>رقم الهاتف</label>
          <input type="text" name="phone">
        </div>

        <div class="auth-field col-6">
          <label>البريد الإلكتروني</label>
          <input type="email" name="email" required>
        </div>

        <div class="auth-field col-6">
          <label>عنوان السكن</label>
          <input type="text" name="home_address">
        </div>

        <div class="auth-field col-12">
          <label>عنوان المكتب</label>
          <input type="text" name="office_address">
        </div>

        <div class="auth-field col-6">
          <label>شهادة ثانوية</label>
          <select name="highschool_certificate">
            <option value="لا" selected>لا</option>
            <option value="نعم">نعم</option>
          </select>
        </div>

        <div class="auth-field col-6">
          <label>الدرجة الجامعية</label>
          <select name="university_degree">
            <option value="">---</option>
            <option value="بكالوريوس">بكالوريوس</option>
            <option value="ماجستير">ماجستير</option>
            <option value="دكتوراه">دكتوراه</option>
          </select>
        </div>

        <div class="auth-field col-6">
          <label>الضمان الاجتماعي</label>
          <select name="social_security" id="social_security" onchange="toggleSocialNumber()">
            <option value="لا" selected>لا</option>
            <option value="نعم">نعم</option>
          </select>
        </div>

        <div class="auth-field col-6">
          <label>رقم الضمان الاجتماعي</label>
          <input type="text" name="social_security_number" id="social_number" disabled>
        </div>

        <div class="auth-field col-6">
          <label>عدم محكومية (ملف) *</label>
          <input type="file" name="no_conviction_doc" accept=".jpg,.jpeg,.png,.pdf" required>
        </div>

        <div class="auth-field col-6">
          <label>حسن السيرة والسلوك (ملف) *</label>
          <input type="file" name="good_conduct_doc" accept=".jpg,.jpeg,.png,.pdf" required>
        </div>

        <div class="auth-field col-12">
          <label>كلمة المرور</label>
          <input type="password" name="password" required>
        </div>

        <div class="auth-field col-12">
          <button type="submit" class="auth-submit">حفظ</button>
        </div>

      </div>
    </form>

    <a href="syndicate_lawyers.php" class="back-link">العودة إلى قائمة المحامين</a>
  </div>
</div>

<script>
function toggleSocialNumber() {
  const ss = document.getElementById('social_security');
  const num = document.getElementById('social_number');
  num.disabled = (ss.value !== 'نعم');
}
toggleSocialNumber();
</script>

</body>
</html>
