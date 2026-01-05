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
  return trim(preg_replace('/\s+/', ' ', (string)$s));
}

/**
 * تنظيف الرقم الوطني: أرقام فقط + أول 10 أرقام
 */
function normalizeNationalId($s) {
  $digits = preg_replace('/\D+/', '', (string)$s);
  return substr($digits, 0, 10);
}

$message = "";

// قيم افتراضية للعرض في الفورم (من بروفايل المستخدم إن وجد)
$def_first  = htmlspecialchars($profile['first_name'] ?? '');
$def_father = htmlspecialchars($profile['father_name'] ?? '');
$def_grand  = htmlspecialchars($profile['grandfather_name'] ?? '');
$def_notes  = htmlspecialchars($profile['notes'] ?? '');
$def_family = htmlspecialchars($profile['family_name'] ?? '');
$def_full_preview = normalizeSpaces(($profile['full_name'] ?? ''));


// AJAX: تحقق سريع من وجود الرقم الوطني في جدول النقابة
if (isset($_GET['action']) && $_GET['action'] === 'check_national_id') {
  header('Content-Type: application/json; charset=utf-8');

  $nidRaw = $_POST['national_id'] ?? $_GET['national_id'] ?? '';
  $nid = normalizeNationalId($nidRaw);

  // لا نفحص DB إلا إذا كان 10 أرقام
  if (strlen($nid) !== 10) {
    echo json_encode(['ok' => true, 'valid' => false, 'exists' => false]);
    exit;
  }

  try {
    $stmt = $pdo->prepare("SELECT syndicate_id FROM lawyers_syndicate WHERE national_id=? LIMIT 1");
    $stmt->execute([$nid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'valid' => true, 'exists' => (bool)$row]);
  } catch (Throwable $t) {
    echo json_encode(['ok' => false, 'error' => 'db_error']);
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {

    // إذا ضيف: يختار النوع من الفورم
    if (!$isKnownApplicant) {
      $applicantType = $_POST['role'] ?? '';
      if (!in_array($applicantType, ['trainee','lawyer'], true)) {
        throw new Exception("يرجى اختيار نوع مقدم الطلب.");
      }
    }

    // =========================
    // الرقم الوطني (10 أرقام فقط)
    // =========================
    $national_id = normalizeNationalId($_POST['national_id'] ?? '');

    if ($national_id === '') {
      throw new Exception("الرقم الوطني مطلوب.");
    }
    if (!preg_match('/^\d{10}$/', $national_id)) {
      throw new Exception("الرقم الوطني يجب أن يكون 10 أرقام فقط.");
    }

    // منع تقديم طلب انتساب إذا كان مسجلاً مسبقاً لدى النقابة (lawyers_syndicate)
    $chkSynd = $pdo->prepare("SELECT COUNT(*) FROM lawyers_syndicate WHERE national_id = ? LIMIT 1");
    $chkSynd->execute([$national_id]);
    if ((int)$chkSynd->fetchColumn() > 0) {
      throw new Exception("انت مسجل في النقابة يمكنك انشاء حساب");
    }

    // =========================
    // قراءة باقي الحقول
    // =========================
    $office_address   = normalizeSpaces($_POST['office_address'] ?? '');
    $phone            = normalizeSpaces($_POST['phone'] ?? '');
    $email            = normalizeSpaces($_POST['email'] ?? '');
    $notes            = normalizeSpaces($_POST['notes'] ?? '');
    $highschool       = normalizeSpaces($_POST['highschool_certificate'] ?? 'لا');
    $university       = normalizeSpaces($_POST['university_degree'] ?? '');
    $social_security  = normalizeSpaces($_POST['social_security'] ?? 'لا');
    $social_number    = normalizeSpaces($_POST['social_security_number'] ?? '');

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

    // وثائق (حسب نموذجك: أنت عاملها required في HTML)
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
    $message = "<div class='alert alert-error'>" . htmlspecialchars($e->getMessage()) . "</div>";
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
  /* إخفاء/إظهار بقية الحقول بدون كسر Grid */
  .extra-fields.hidden { display: none; }
  .extra-fields { display: contents; }

  /* رسالة التحقق */
  .syndicate-alert{
    background: rgba(70, 99, 180, 0.08);
    border: 1px solid rgba(70, 99, 180, 0.25);
    color: #0f1b3f;
    padding: 12px 14px;
    border-radius: 12px;
    font-weight: 700;
    line-height: 1.6;
  }
  .syndicate-alert a{
    color: #1b3a8a;
    text-decoration: underline;
  }
  .syndicate-hint{
    font-size: 13px;
    color: #64748b;
    margin-top: 6px;
  }
</style>
</head>
<body class="layout-sticky" data-theme="<?= htmlspecialchars($theme) ?>">

<?php include(__DIR__ . "/../includes/header.php"); ?>

<main class="main-content auth-shell">
  <div class="auth-card auth-card--wide">
    <div class="auth-head auth-head--split">
      <div>
        <h1 class="auth-title">طلب الانتساب</h1>
        <p class="auth-subtitle">يرجى تعبئة البيانات المطلوبة ورفع الوثائق لإكمال الطلب.</p>
      </div>
      <div class="auth-head-actions">
        <a class="btn-card" href="/mutadarrib/membership/track_request.php">تتبّع طلب الانتساب</a>
      </div>
    </div>

    <?= $message ?>

    <form method="POST" enctype="multipart/form-data" class="auth-form">
      <div class="auth-grid">

        <?php if (!$isKnownApplicant): ?>
          <div class="auth-field col-6">
            <label>نوع مقدم الطلب</label>
            <select name="role" required>
              <option value="">اختر...</option>
              <option value="trainee">متدرب</option>
              <option value="lawyer">مزاول</option>
            </select>
          </div>
        <?php else: ?>
          <div class="auth-field col-6">
            <label>نوع مقدم الطلب</label>
            <input type="text" class="readonly" value="<?= ($applicantType==='trainee'?'متدرب':'مزاول') ?>" readonly>
          </div>
        <?php endif; ?>

        <div class="auth-field col-6">
          <label>الرقم الوطني</label>
          <input
            type="text"
            name="national_id"
            required
            value="<?= $def_national ?>"
            maxlength="10"
            inputmode="numeric"
            pattern="\d{10}"
            autocomplete="off"
          >
          <div class="syndicate-hint" id="nidHint" style="display:none;">
            يجب إدخال 10 أرقام لفتح باقي الحقول.
          </div>
        </div>

        <div class="auth-field col-12" id="syndicateMsg" style="display:none;"></div>

        <div id="extraFields" class="extra-fields hidden">

          <div class="auth-field col-12">
            <label>الاسم الرباعي (كل مقطع منفرد)</label>
          </div>

          <div class="auth-field col-3">
            <label for="first_name">الاسم الأول</label>
            <input id="first_name" type="text" name="first_name" required value="<?= $def_first ?>">
          </div>

          <div class="auth-field col-3">
            <label for="father_name">اسم الأب</label>
            <input id="father_name" type="text" name="father_name" required value="<?= $def_father ?>">
          </div>

          <div class="auth-field col-3">
            <label for="grandfather_name">اسم الجد</label>
            <input id="grandfather_name" type="text" name="grandfather_name" required value="<?= $def_grand ?>">
          </div>

          <div class="auth-field col-3">
            <label for="family_name">اسم العائلة</label>
            <input id="family_name" type="text" name="family_name" required value="<?= $def_family ?>">
          </div>

          <div class="auth-field col-12">
            <label>الاسم الكامل</label>
            <input type="text" id="full_name_preview" class="readonly" readonly>
          </div>

          <div class="auth-field col-6">
            <label>الهاتف</label>
            <input type="text" name="phone" value="<?= $def_phone ?>">
          </div>

          <div class="auth-field col-6">
            <label>البريد الإلكتروني</label>
            <input type="email" name="email" value="<?= $def_email ?>">
          </div>

          <div class="auth-field col-6">
            <label>عنوان المكتب (اختياري)</label>
            <input type="text" name="office_address" value="<?= $def_office ?>">
          </div>

          <div class="auth-field col-6">
            <label>شهادة ثانوية</label>
            <select name="highschool_certificate">
              <option value="لا"  <?= ($def_highschool==='لا')?'selected':''; ?>>لا</option>
              <option value="نعم" <?= ($def_highschool==='نعم')?'selected':''; ?>>نعم</option>
            </select>
          </div>

          <div class="auth-field col-6">
            <label>هل يوجد ضمان اجتماعي؟</label>
            <select name="social_security" id="ssSel" onchange="toggleSS()">
              <option value="لا"  <?= ($def_ss==='لا')?'selected':''; ?>>لا</option>
              <option value="نعم" <?= ($def_ss==='نعم')?'selected':''; ?>>نعم</option>
            </select>
          </div>

          <div class="auth-field col-6">
            <label>رقم الضمان الاجتماعي</label>
            <input type="text" name="social_security_number" id="ssNum" value="<?= $def_ssn ?>">
          </div>

          <div class="auth-field col-12">
            <label>الدرجة العلمية</label>
            <select name="university_degree">
              <option value="" <?= ($def_uni==='')?'selected':''; ?>>---</option>
              <option value="بكالوريوس" <?= ($def_uni==='بكالوريوس')?'selected':''; ?>>بكالوريوس</option>
              <option value="ماجستير"   <?= ($def_uni==='ماجستير')?'selected':''; ?>>ماجستير</option>
              <option value="دكتوراه"   <?= ($def_uni==='دكتوراه')?'selected':''; ?>>دكتوراه</option>
            </select>
          </div>

          <div class="auth-field col-12">
            <label>ملاحظات (اختياري)</label>
            <textarea name="notes" rows="4"><?= $def_notes ?></textarea>
          </div>

          <hr class="auth-divider">

          <div class="auth-field col-6">
            <label>صورة الهوية (أمامي)</label>
            <input type="file" name="identity_front" accept=".jpg,.jpeg,.png,.pdf" required>
          </div>

          <div class="auth-field col-6">
            <label>صورة الهوية (خلفي)</label>
            <input type="file" name="identity_back" accept=".jpg,.jpeg,.png,.pdf" required>
          </div>

          <div class="auth-field col-6">
            <label>عدم محكومية</label>
            <input type="file" name="no_conviction_doc" accept=".jpg,.jpeg,.png,.pdf" required>
          </div>

          <div class="auth-field col-6">
            <label>حسن السيرة والسلوك</label>
            <input type="file" name="good_conduct_doc" accept=".jpg,.jpeg,.png,.pdf" required>
          </div>

          <div class="auth-field col-12">
            <button type="submit" class="auth-submit">إرسال الطلب</button>
          </div>

        </div><!-- /extraFields -->

      </div><!-- /auth-grid -->
    </form>

  </div>
</main>

<?php include(__DIR__ . "/../includes/footer.php"); ?>

<script>
// ===== Helpers =====
function normalizeSpaces(s){
  return (s || '').replace(/\s+/g,' ').trim();
}
function sanitizeNID(val){
  return (val || '').replace(/\D/g,'').slice(0,10);
}

// ===== Full name preview =====
function updateFullName(){
  const a = normalizeSpaces(document.getElementById('first_name')?.value);
  const b = normalizeSpaces(document.getElementById('father_name')?.value);
  const c = normalizeSpaces(document.getElementById('grandfather_name')?.value);
  const d = normalizeSpaces(document.getElementById('family_name')?.value);
  const out = [a,b,c,d].filter(Boolean).join(' ');
  const prev = document.getElementById('full_name_preview');
  if(prev) prev.value = normalizeSpaces(out);
}
['first_name','father_name','grandfather_name','family_name'].forEach(id=>{
  const el = document.getElementById(id);
  if(el) el.addEventListener('input', updateFullName);
});
updateFullName();

// ===== Social security toggle =====
function toggleSS(){
  const ss = document.getElementById('ssSel');
  const num = document.getElementById('ssNum');
  if(!ss || !num) return;
  num.disabled = (ss.value !== 'نعم');
}
toggleSS();

// ===== National ID gating + syndicate check =====
const nationalInput  = document.querySelector('input[name="national_id"]');
const extraFields    = document.getElementById('extraFields');
const syndicateMsg   = document.getElementById('syndicateMsg');
const nidHint        = document.getElementById('nidHint');

function toggleExtraFieldsDisabled(disabled){
  if(!extraFields) return;
  extraFields.querySelectorAll('input, select, textarea, button').forEach(el=>{
    el.disabled = !!disabled;
  });
}

function setSyndicateState(state){
  if(!extraFields || !syndicateMsg) return;

  if(state === 'need10'){
    if(nidHint) nidHint.style.display = '';
    syndicateMsg.style.display = 'none';
    syndicateMsg.innerHTML = '';
    toggleExtraFieldsDisabled(true);
    extraFields.classList.add('hidden');
    return;
  }

  if(nidHint) nidHint.style.display = 'none';

  if(state === 'exists'){
    toggleExtraFieldsDisabled(true);
    extraFields.classList.add('hidden');
    syndicateMsg.style.display = '';
    syndicateMsg.innerHTML = `
      <div class="syndicate-alert">
        انت مسجل في النقابة يمكنك انشاء حساب
        <div class="syndicate-hint">
          يمكنك الانتقال لصفحة إنشاء الحساب من هنا:
          <a href="/mutadarrib/auth/register.php">إنشاء حساب</a>
        </div>
      </div>`;
    return;
  }

  if(state === 'not_exists'){
    syndicateMsg.style.display = 'none';
    syndicateMsg.innerHTML = '';
    extraFields.classList.remove('hidden');
    toggleExtraFieldsDisabled(false);
    return;
  }

  if(state === 'error'){
    // نفتح الحقول (طالما الرقم 10 أرقام) لكن نعرض تنبيه خفيف
    syndicateMsg.style.display = '';
    syndicateMsg.innerHTML = `
      <div class="syndicate-alert">
        تعذر التحقق من سجل النقابة حالياً. يمكنك المتابعة، وسيتم التحقق عند الإرسال.
      </div>`;
    extraFields.classList.remove('hidden');
    toggleExtraFieldsDisabled(false);
    return;
  }
}

// إغلاق الحقول مبدئياً
setSyndicateState('need10');

let _nidTimer = null;

async function checkNationalId(){
  if(!nationalInput) return;

  const nid = sanitizeNID(nationalInput.value);
  nationalInput.value = nid;

  // لا نفتح ولا نفحص إلا إذا 10 أرقام
  if(nid.length !== 10){
    setSyndicateState('need10');
    return;
  }

  try{
    const res = await fetch(window.location.pathname + '?action=check_national_id', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams({ national_id: nid }).toString()
    });

    const data = await res.json();

    if(data && data.ok && data.valid){
      setSyndicateState(data.exists ? 'exists' : 'not_exists');
    }else{
      setSyndicateState('error');
    }
  }catch(e){
    setSyndicateState('error');
  }
}

function scheduleCheck(){
  if(_nidTimer) clearTimeout(_nidTimer);
  _nidTimer = setTimeout(checkNationalId, 250);
}

if(nationalInput){
  nationalInput.addEventListener('input', () => {
    nationalInput.value = sanitizeNID(nationalInput.value);
    scheduleCheck();
  });
  nationalInput.addEventListener('blur', checkNationalId);

  // تشغيل مبدئي لو كان الرقم موجوداً مسبقاً (معبأ من البروفايل)
  checkNationalId();
}
</script>

</body>
</html>
