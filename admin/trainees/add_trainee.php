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

    $national_id      = normalizeSpaces($_POST['national_id'] ?? '');
    $phone            = normalizeSpaces($_POST['phone'] ?? '');
    $email            = normalizeSpaces($_POST['email'] ?? '');
    $home_address     = normalizeSpaces($_POST['home_address'] ?? '');

    $degree           = $_POST['university_degree'] ?? null;
    $highschool       = $_POST['highschool_certificate'] ?? 'لا';

    $social           = $_POST['social_security'] ?? 'لا';
    $social_no        = normalizeSpaces($_POST['social_security_number'] ?? '');
    $social_no        = ($social === 'نعم') ? ($social_no ?: null) : null;

    $password         = (string)($_POST['password'] ?? '');
    $password_hashed  = password_hash($password, PASSWORD_BCRYPT);

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

        $pdo->beginTransaction();

        // 1) إضافة المستخدم في جدول users (ديناميكي حسب الأعمدة الموجودة)
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
            'highschool_certificate' => $highschool,
            'university_degree'      => $degree,
            'no_conviction_doc'      => $no_conviction_path,
            'good_conduct_doc'       => $good_conduct_path,
            'social_security'        => $social,
            'social_security_number' => $social_no,
            'password'               => $password_hashed,
            'role'                   => 'trainee',
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
        $stmt = $pdo->prepare($sqlUsers);
        $stmt->execute($values);

        $user_id = (int)$pdo->lastInsertId();

        // 2) إضافة المتدرب في جدول trainees (مع الوثائق)
        $stmt2 = $pdo->prepare("
            INSERT INTO trainees
            (user_id,
             full_name, first_name, father_name, grandfather_name, family_name,
             national_id, phone, email, home_address,
             highschool_certificate, university_degree,
             no_conviction_doc, good_conduct_doc,
             social_security, social_security_number,
             created_at)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt2->execute([
            $user_id,
            $full_name, $first_name, $father_name, $grandfather_name, $family_name,
            $national_id, $phone, $email, $home_address,
            $highschool, $degree,
            $no_conviction_path, $good_conduct_path,
            $social, $social_no
        ]);

        $pdo->commit();
        $message = "<div class='alert alert-success'>✅ تم إضافة المتدرب بنجاح!</div>";

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
<title>إضافة متدرب جديد</title>
<link rel="stylesheet" href="/mutadarrib/assets/css/admin.css">
<link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<?php include(__DIR__ . "/../includes/header.php"); ?>
<div class="admin-container">
  <?php include(__DIR__ . "/../includes/sidebar.php"); ?>

  <div class="container">
    <h2>➕ إضافة متدرب جديد</h2>
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
          <label>الهاتف</label>
          <input type="text" name="phone">
        </div>

        <div class="auth-field col-6">
          <label>البريد الإلكتروني</label>
          <input type="email" name="email">
        </div>

        <div class="auth-field col-6">
          <label>عنوان السكن</label>
          <input type="text" name="home_address">
        </div>

        <div class="auth-field col-6">
          <label>كلمة المرور</label>
          <input type="password" name="password" required>
        </div>

        <div class="auth-field col-6">
          <label>شهادة الثانوية</label>
          <select name="highschool_certificate">
            <option value="لا" selected>لا</option>
            <option value="نعم">نعم</option>
          </select>
        </div>

        <div class="auth-field col-6">
          <label>الدرجة الجامعية</label>
          <select name="university_degree">
            <option value="بكالوريوس">بكالوريوس</option>
            <option value="ماجستير">ماجستير</option>
            <option value="دكتوراه">دكتوراه</option>
          </select>
        </div>

        <div class="auth-field col-6">
          <label>الضمان الاجتماعي</label>
          <select name="social_security" id="social_select" onchange="toggleField()">
            <option value="لا" selected>لا</option>
            <option value="نعم">نعم</option>
          </select>
        </div>

        <div class="auth-field col-6">
          <label>رقم الضمان</label>
          <input type="text" name="social_security_number" id="social_input" disabled>
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
          <button type="submit" class="auth-submit">حفظ</button>
        </div>

      </div>
    </form>
  </div>
</div>

<script>
function toggleField() {
  const select = document.getElementById('social_select');
  const input = document.getElementById('social_input');
  input.disabled = (select.value === "لا");
}
toggleField();
</script>

</body>
</html>
