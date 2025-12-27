<?php
require_once __DIR__ . '/../includes/theme_init.php';

session_start();
require_once __DIR__ . "/../config/db.php";

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$role   = $_SESSION['role'] ?? null;

// هل هو مستخدم مسجل من الأنواع المسموحة؟
$isKnownApplicant = ($userId && in_array($role, ['trainee','lawyer'], true));

$applicantType = null; // trainee | lawyer
if ($isKnownApplicant) {
  $applicantType = ($role === 'trainee') ? 'trainee' : 'lawyer';
}

$user = null;
$profile = [];

if ($isKnownApplicant) {
  $stmtU = $pdo->prepare("SELECT * FROM users WHERE user_id=? LIMIT 1");
  $stmtU->execute([$userId]);
  $user = $stmtU->fetch(PDO::FETCH_ASSOC);

  if ($role === 'trainee') {
    $stmtP = $pdo->prepare("SELECT * FROM trainees WHERE user_id=? LIMIT 1");
    $stmtP->execute([$userId]);
    $profile = $stmtP->fetch(PDO::FETCH_ASSOC) ?: [];
  } else {
    $stmtP = $pdo->prepare("SELECT * FROM lawyers WHERE user_id=? LIMIT 1");
    $stmtP->execute([$userId]);
    $profile = $stmtP->fetch(PDO::FETCH_ASSOC) ?: [];
  }
}

function saveUpload($fileKey, $destDirRel, $allowedExt = ['jpg','jpeg','png','pdf'], $maxBytes = 5_000_000) {
  if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) return null;

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

  $destDirAbs = __DIR__ . "/.." . $destDirRel;
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
  return trim(preg_replace('/\s+/', ' ', $s));
}

$message = "";

// قيم افتراضية للعرض في الفورم (من بروفايل المستخدم إن وجد)
$def_first  = htmlspecialchars($profile['first_name'] ?? '');
$def_father = htmlspecialchars($profile['father_name'] ?? '');
$def_gf     = htmlspecialchars($profile['grandfather_name'] ?? '');
$def_family = htmlspecialchars($profile['family_name'] ?? '');
$def_full_preview = normalizeSpaces(($profile['full_name'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {

    // إذا ضيف: يختار النوع من الفورم
    if (!$isKnownApplicant) {
      $applicantType = $_POST['role'] ?? '';
      if (!in_array($applicantType, ['trainee','lawyer'], true)) {
        throw new Exception("يرجى اختيار نوع مقدم الطلب.");
      }
    }

    // الاسم الرباعي (إجباري كأجزاء منفصلة)
    $first_name       = normalizeSpaces($_POST['first_name'] ?? '');
    $father_name      = normalizeSpaces($_POST['father_name'] ?? '');
    $grandfather_name = normalizeSpaces($_POST['grandfather_name'] ?? '');
    $family_name      = normalizeSpaces($_POST['family_name'] ?? '');

    if ($first_name === '' || $father_name === '' || $grandfather_name === '' || $family_name === '') {
      throw new Exception("يرجى تعبئة الاسم الرباعي بالكامل (كل جزء في حقل منفصل).");
    }

    // توليد الاسم الكامل تلقائياً
    $full_name = normalizeSpaces($first_name . " " . $father_name . " " . $grandfather_name . " " . $family_name);

    $national_id    = normalizeSpaces($_POST['national_id'] ?? '');

    // ==============================
// منع تقديم طلب انتساب إذا كان مسجلاً مسبقاً لدى النقابة (lawyers_syndicate)
// ==============================
$chkSynd = $pdo->prepare("SELECT COUNT(*) FROM lawyers_syndicate WHERE national_id = ? LIMIT 1");
$chkSynd->execute([$national_id]);

if ((int)$chkSynd->fetchColumn() > 0) {
  throw new Exception("انت مسجل لدى النقابة يمكنك انشاء حساب");
}


    $phone          = normalizeSpaces($_POST['phone'] ?? '');
    $email          = normalizeSpaces($_POST['email'] ?? '');
    $office_address = normalizeSpaces($_POST['office_address'] ?? '');
    $notes          = normalizeSpaces($_POST['notes'] ?? '');

    $highschool     = $_POST['highschool_certificate'] ?? 'لا';
    $university     = $_POST['university_degree'] ?? null;

    $social_security = $_POST['social_security'] ?? 'لا';
    $social_number   = normalizeSpaces($_POST['social_security_number'] ?? '');

    if ($national_id === '') throw new Exception("الرقم الوطني مطلوب.");

    // منع وجود طلب pending لنفس الرقم الوطني
    $chk = $pdo->prepare("SELECT COUNT(*) FROM membership_requests WHERE national_id=? AND status='pending'");
    $chk->execute([$national_id]);
    if ((int)$chk->fetchColumn() > 0) {
      throw new Exception("يوجد طلب انتساب قيد المراجعة لنفس الرقم الوطني بالفعل.");
    }

    // رفع الهوية (إلزامي)
    $idFront = saveUpload('identity_front', '/uploads/membership_ids');
    $idBack  = saveUpload('identity_back',  '/uploads/membership_ids');
    if (!$idFront || !$idBack) {
      throw new Exception("يجب رفع صورة الهوية من الجهتين.");
    }

    // وثائق اختيارية
    $noConviction = saveUpload('no_conviction_doc', '/uploads/membership_docs', ['jpg','jpeg','png','pdf'], 5_000_000);
    $goodConduct  = saveUpload('good_conduct_doc',  '/uploads/membership_docs', ['jpg','jpeg','png','pdf'], 5_000_000);

    // lawyer_name في الجدول (إن وجد) نجعله نفس full_name
    $lawyer_name = $full_name;

$publicCode = strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));

    $stmtIns = $pdo->prepare("
  INSERT INTO membership_requests
    (public_code, user_id, role, status,
     identity_front, identity_back,
     no_conviction_doc, good_conduct_doc,
     lawyer_name, national_id, office_address, phone, email, notes,
     full_name, first_name, father_name, grandfather_name, family_name,
     highschool_certificate, university_degree,
     social_security, social_security_number)
  VALUES
    (?, ?, ?, 'pending',
     ?, ?,
     ?, ?,
     ?, ?, ?, ?, ?, ?,
     ?, ?, ?, ?, ?,
     ?, ?,
     ?, ?)
");

$stmtIns->execute([
  $publicCode,
  $userId, $applicantType,
  $idFront, $idBack,
  $noConviction, $goodConduct,
  $lawyer_name, $national_id, $office_address, $phone, $email, $notes,
  $full_name, $first_name, $father_name, $grandfather_name, $family_name,
  $highschool, $university,
  $social_security, ($social_security === 'نعم' ? ($social_number ?: null) : null)
]);

$_SESSION['membership_public_code'] = $publicCode;
$_SESSION['membership_national_id'] = $national_id;
header("Location: /mutadarrib/membership/request_success.php");
exit;

  } catch (Exception $e) {
    $message = "<div style='color:#b00020;margin:10px 0;'>" . htmlspecialchars($e->getMessage()) . "</div>";
  }
}

// قيم افتراضية لعناصر أخرى
$def_national = htmlspecialchars($profile['national_id'] ?? $user['national_id'] ?? '');
$def_phone    = htmlspecialchars($profile['phone'] ?? $user['phone'] ?? '');
$def_email    = htmlspecialchars($profile['email'] ?? $user['email'] ?? '');
$def_office   = htmlspecialchars($profile['office_address'] ?? '');
$def_highschool = $profile['highschool_certificate'] ?? 'لا';
$def_uni = $profile['university_degree'] ?? '';
$def_ss = $profile['social_security'] ?? 'لا';
$def_ssn = htmlspecialchars($profile['social_security_number'] ?? '');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>طلب انتساب</title>
  <link rel="stylesheet" href="/mutadarrib/assets/css/style.css">
  <style>
    .container{max-width:900px;margin:20px auto;}
    label{display:block;margin-top:10px}
    input,select,textarea{width:100%;padding:8px;border:1px solid #ccc;border-radius:8px;margin-top:5px}
    button{margin-top:15px;padding:10px 14px;border:0;border-radius:10px;background:#0077b6;color:#fff;cursor:pointer}
    .grid4{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
    @media(max-width:900px){.grid4{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:520px){.grid4{grid-template-columns:1fr}}
    .readonly{background:#f6f6f6}
  </style>
</head>
<body data-theme="<?= htmlspecialchars($theme) ?>">

<?php include(__DIR__ . "/../includes/header.php"); ?>

<div class="container">
  <h2>طلب انتساب</h2>
  <a class="btn" href="/mutadarrib/membership/track_request.php">تتبّع طلب الانتساب</a>

  <?= $message ?>

  <form method="POST" enctype="multipart/form-data">

    <?php if (!$isKnownApplicant): ?>
      <label>نوع مقدم الطلب</label>
      <select name="role" required>
        <option value="">اختر...</option>
        <option value="trainee">متدرب</option>
        <option value="lawyer">مزاول</option>
      </select>
    <?php else: ?>
      <p>نوع مقدم الطلب: <strong><?= ($applicantType==='trainee'?'متدرب':'مزاول') ?></strong></p>
    <?php endif; ?>

    <label>الاسم الرباعي (كل مقطع منفرد)</label>
    <div class="grid4">
      <div>
        <input type="text" name="first_name" id="first_name" placeholder="الاسم الأول" required value="<?= $def_first ?>">
      </div>
      <div>
        <input type="text" name="father_name" id="father_name" placeholder="اسم الأب" required value="<?= $def_father ?>">
      </div>
      <div>
        <input type="text" name="grandfather_name" id="grandfather_name" placeholder="اسم الجد" required value="<?= $def_gf ?>">
      </div>
      <div>
        <input type="text" name="family_name" id="family_name" placeholder="اسم العائلة" required value="<?= $def_family ?>">
      </div>
    </div>

    <label>الاسم الكامل </label>
    <input type="text" id="full_name_preview" class="readonly" readonly
           value="<?= htmlspecialchars($def_full_preview) ?>">

    <label>الرقم الوطني</label>
    <input type="text" name="national_id" required value="<?= $def_national ?>">

    <label>الهاتف</label>
    <input type="text" name="phone" value="<?= $def_phone ?>">

    <label>البريد الإلكتروني</label>
    <input type="email" name="email" value="<?= $def_email ?>">

    <label>عنوان المكتب (اختياري)</label>
    <input type="text" name="office_address" value="<?= $def_office ?>">

    <label>ملاحظات (اختياري)</label>
    <textarea name="notes" rows="3"></textarea>

    <label>شهادة ثانوية</label>
    <select name="highschool_certificate">
      <option value="لا"  <?= ($def_highschool==='لا')?'selected':''; ?>>لا</option>
      <option value="نعم" <?= ($def_highschool==='نعم')?'selected':''; ?>>نعم</option>
    </select>

    <label>الدرجة الجامعية</label>
    <select name="university_degree">
      <option value="" <?= ($def_uni==='')?'selected':''; ?>>---</option>
      <option value="بكالوريوس" <?= ($def_uni==='بكالوريوس')?'selected':''; ?>>بكالوريوس</option>
      <option value="ماجستير"   <?= ($def_uni==='ماجستير')?'selected':''; ?>>ماجستير</option>
      <option value="دكتوراه"   <?= ($def_uni==='دكتوراه')?'selected':''; ?>>دكتوراه</option>
    </select>

    <label>هل يوجد ضمان اجتماعي؟</label>
    <select name="social_security" id="ssSel" onchange="toggleSS()">
      <option value="لا"  <?= ($def_ss==='لا')?'selected':''; ?>>لا</option>
      <option value="نعم" <?= ($def_ss==='نعم')?'selected':''; ?>>نعم</option>
    </select>

    <label>رقم الضمان الاجتماعي</label>
    <input type="text" name="social_security_number" id="ssNum" value="<?= $def_ssn ?>">

    <hr>

    <label>صورة الهوية (أمامي) </label>
    <input type="file" name="identity_front" accept=".jpg,.jpeg,.png,.pdf" required>

    <label>صورة الهوية (خلفي) </label>
    <input type="file" name="identity_back" accept=".jpg,.jpeg,.png,.pdf" required>

    <label>عدم محكومية</label>
    <input type="file" name="no_conviction_doc" accept=".jpg,.jpeg,.png,.pdf" required>

    <label>حسن السيرة والسلوك </label>
    <input type="file" name="good_conduct_doc" accept=".jpg,.jpeg,.png,.pdf" required>

    <button type="submit">إرسال الطلب</button>
  </form>
</div>

<script>
function normalizeSpaces(s){
  return (s || '').replace(/\s+/g,' ').trim();
}

function updateFullName(){
  const a = normalizeSpaces(document.getElementById('first_name').value);
  const b = normalizeSpaces(document.getElementById('father_name').value);
  const c = normalizeSpaces(document.getElementById('grandfather_name').value);
  const d = normalizeSpaces(document.getElementById('family_name').value);
  document.getElementById('full_name_preview').value = normalizeSpaces([a,b,c,d].filter(Boolean).join(' '));
}

['first_name','father_name','grandfather_name','family_name'].forEach(id=>{
  const el = document.getElementById(id);
  if(el) el.addEventListener('input', updateFullName);
});
updateFullName();

function toggleSS(){
  const ss = document.getElementById('ssSel');
  const num = document.getElementById('ssNum');
  num.disabled = (ss.value !== 'نعم');
}
toggleSS();
</script>

</body>
</html>

<?php include("../includes/footer.php"); ?>