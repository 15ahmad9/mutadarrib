<?php
require_once __DIR__ . '/../includes/theme_init.php';
session_start();
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function norm_url($url){
  $url = trim($url ?? '');
  if ($url === '') return null;
  if (!preg_match('~^https?://~i', $url)) $url = 'https://' . $url;
  return $url;
}

/**
 * ✅ إعدادات التخصصات (5 تخصصات)
 * - key: specialization key
 * - label: اسم التخصص بالعربي
 * - trainee_table/provider_table: اسم جداول DB
 * - majors: خيارات dropdown للتخصص الفرعي للمتدرب
 */
$SPECIALIZATIONS = [
  'business' => [
    'label' => 'الأعمال',
    'trainee_table' => 'business_trainees',
    'provider_table' => 'business_providers',
    'majors' => ['إدارة أعمال','محاسبة','تسويق','اقتصاد','تمويل','إدارة موارد بشرية'],
  ],
  'arts' => [
    'label' => 'الآداب',
    'trainee_table' => 'arts_trainees',
    'provider_table' => 'arts_providers',
    'majors' => ['لغة عربية','لغة إنجليزية','ترجمة','إعلام','علم اجتماع','علم نفس'],
  ],
  'architecture_design' => [
    'label' => 'العمارة والتصميم',
    'trainee_table' => 'architecture_design_trainees',
    'provider_table' => 'architecture_design_providers',
    'majors' => ['هندسة معمارية','تصميم داخلي','تصميم جرافيك','تصميم صناعي','تخطيط حضري'],
  ],
  'allied_medical' => [
    'label' => 'العلوم الطبية المساندة',
    'trainee_table' => 'allied_medical_trainees',
    'provider_table' => 'allied_medical_providers',
    'majors' => ['مختبرات طبية','علاج طبيعي','علاج وظيفي','أشعة','تغذية','سمع ونطق'],
  ],
  'it' => [
    'label' => 'تكنولوجيا المعلومات',
    'trainee_table' => 'it_trainees',
    'provider_table' => 'it_providers',
    'majors' => ['علوم الحاسوب','هندسة البرمجيات','نظم معلومات','أمن سيبراني','ذكاء اصطناعي','علم بيانات','شبكات'],
  ],
];

$message = "";

// ✅ اختيار تخصص افتراضي من GET (اختياري)
$pref_spec = trim($_GET['specialization'] ?? '');
if ($pref_spec !== '' && !isset($SPECIALIZATIONS[$pref_spec])) $pref_spec = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $account_type = $_POST['account_type'] ?? ''; // trainee | provider
  $spec_key     = trim($_POST['specialization'] ?? '');

  // shared
  $email       = trim($_POST['email'] ?? '');
  $passwordRaw = trim($_POST['password'] ?? '');
  $phone       = trim($_POST['phone'] ?? '');
  $address     = trim($_POST['address'] ?? '');

  // trainee
  $first_name       = trim($_POST['first_name'] ?? '');
  $father_name      = trim($_POST['father_name'] ?? '');
  $grandfather_name = trim($_POST['grandfather_name'] ?? '');
  $family_name      = trim($_POST['family_name'] ?? '');
  $full_name        = trim($_POST['full_name'] ?? '');
  if ($full_name === '') {
    $full_name = trim(implode(' ', array_filter([$first_name,$father_name,$grandfather_name,$family_name])));
  }

  $university   = trim($_POST['university'] ?? '');
  $major        = trim($_POST['major'] ?? '');
  $major        = ($major !== '') ? $major : null;

  $skills       = trim($_POST['skills'] ?? '');
  $linkedin_url = norm_url($_POST['linkedin_url'] ?? '');
  $github_url   = norm_url($_POST['github_url'] ?? ''); // ✅ يظهر/يُستخدم فقط لتخصص IT

  // provider
  $company_name = trim($_POST['company_name'] ?? '');
  $city         = trim($_POST['city'] ?? '');
  $description  = trim($_POST['description'] ?? '');

  // ✅ Validations
  $errors = [];

  if (!in_array($account_type, ['trainee','provider'], true)) $errors[] = "يرجى اختيار نوع الحساب.";
  if (!isset($SPECIALIZATIONS[$spec_key])) $errors[] = "يرجى اختيار التخصص بشكل صحيح.";

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "البريد الإلكتروني غير صحيح.";
  if (mb_strlen($passwordRaw) < 6) $errors[] = "كلمة المرور يجب أن تكون 6 أحرف/أرقام على الأقل.";

  if ($account_type === 'trainee') {
    if ($first_name==='' || $father_name==='' || $grandfather_name==='' || $family_name==='') {
      $errors[] = "يرجى تعبئة الاسم الرباعي بالكامل.";
    }
  } else {
    if ($company_name === '') $errors[] = "اسم الشركة مطلوب لمزود التدريب.";
  }

  // ✅ تحقق major ضمن خيارات التخصص (إذا تم اختيارها)
  if (isset($SPECIALIZATIONS[$spec_key]) && $account_type === 'trainee' && $major !== null) {
    if (!in_array($major, $SPECIALIZATIONS[$spec_key]['majors'], true)) {
      $errors[] = "التخصص الفرعي المختار غير صحيح.";
    }
  }

  if ($errors) {
    $message = "<p class='error'>❌ " . implode("<br>", array_map('h', $errors)) . "</p>";
  } else {
    $uploadedCvAbsPath = null;
    $cv_path = null;

    try {
      // email unique
      $chk = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
      $chk->execute([$email]);
      if ($chk->fetchColumn()) {
        $message = "<p class='error'>❌ هذا البريد مستخدم مسبقاً.</p>";
      } else {

        $pdo->beginTransaction();

        $passwordHash = password_hash($passwordRaw, PASSWORD_BCRYPT);

        // ✅ role عام
        $role = ($account_type === 'provider') ? 'provider' : 'trainee';
        $displayName = ($account_type === 'provider') ? $company_name : $full_name;

        // 1) users
        $insU = $pdo->prepare("
          INSERT INTO users (full_name, email, phone, address, password, role)
          VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insU->execute([
          $displayName,
          $email,
          ($phone !== '' ? $phone : null),
          ($address !== '' ? $address : null),
          $passwordHash,
          $role,
        ]);

        $userId = (int)$pdo->lastInsertId();

        // 2) specialization tables
        $tTable = $SPECIALIZATIONS[$spec_key]['trainee_table'];
        $pTable = $SPECIALIZATIONS[$spec_key]['provider_table'];

        if ($account_type === 'trainee') {

          // ✅ رفع CV اختياري لكل المجالات (للمتدرب)
          if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES['cv_file']['error'] !== UPLOAD_ERR_OK) {
              throw new Exception("حدث خطأ أثناء رفع ملف السيرة الذاتية.");
            }

            $allowed = [
              'application/pdf' => 'pdf',
              'application/msword' => 'doc',
              'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            ];

            $mime = @mime_content_type($_FILES['cv_file']['tmp_name']);
            if (!$mime || !isset($allowed[$mime])) {
              throw new Exception("صيغة CV غير مدعومة. المسموح: PDF/DOC/DOCX");
            }

            if ($_FILES['cv_file']['size'] > 5 * 1024 * 1024) {
              throw new Exception("حجم CV كبير. الحد الأقصى 5MB");
            }

            $ext = $allowed[$mime];

            $uploadDir = __DIR__ . "/../uploads/cv/";
            if (!is_dir($uploadDir)) {
              if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception("تعذر إنشاء مجلد رفع CV.");
              }
            }

            $safeName = "cv_{$userId}_" . time() . "." . $ext;
            $destPath = $uploadDir . $safeName;

            if (!move_uploaded_file($_FILES['cv_file']['tmp_name'], $destPath)) {
              throw new Exception("تعذر حفظ ملف CV.");
            }

            $uploadedCvAbsPath = $destPath;
            $cv_path = "uploads/cv/" . $safeName;
          }

          // ✅ إدخال المتدرب (ملاحظة: يجب أن الأعمدة موجودة في كل trainee tables)
          // university, major, skills, linkedin_url, github_url, cv_file_path
          $sqlT = "
            INSERT INTO {$tTable} (user_id, university, major, skills, linkedin_url, github_url, cv_file_path)
            VALUES (?, ?, ?, ?, ?, ?, ?)
          ";
          $insT = $pdo->prepare($sqlT);
          $insT->execute([
            $userId,
            ($university !== '' ? $university : null),
            $major,
            ($skills !== '' ? $skills : null),
            $linkedin_url,
            ($spec_key === 'it' ? $github_url : null),
            $cv_path
          ]);

        } else {

          // ✅ إدخال المزود
          $sqlP = "
            INSERT INTO {$pTable} (user_id, company_name, description, city)
            VALUES (?, ?, ?, ?)
          ";
          $insP = $pdo->prepare($sqlP);
          $insP->execute([
            $userId,
            $company_name,
            ($description !== '' ? $description : null),
            ($city !== '' ? $city : null),
          ]);
        }

        $pdo->commit();
        $message = "<p class='success'>✅ تم إنشاء الحساب بنجاح. يمكنك تسجيل الدخول الآن.</p>";
      }

    } catch (Exception $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();

      // حذف الملف إذا انرفع وفشلنا
      if ($uploadedCvAbsPath && file_exists($uploadedCvAbsPath)) {
        @unlink($uploadedCvAbsPath);
      }

      $message = "<p class='error'>❌ خطأ: " . h($e->getMessage()) . "</p>";
    }
  }
}

// خيارات التخصصات للعرض
$specSel = ($_POST['specialization'] ?? $pref_spec ?? '');
$accSel  = ($_POST['account_type'] ?? '');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>إنشاء حساب</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    .auth-card--wide{ width:min(980px, 94vw); }
    .role-group{
      grid-column: span 12;
      display:none;
      grid-template-columns: repeat(12, minmax(0, 1fr));
      gap:12px 14px;
      margin-top:8px;
      padding-top:6px;
      border-top:1px solid rgba(15,23,42,.08);
    }
    .role-group.active{display:grid;}
    .form-section-title{
      grid-column: span 12;
      font-weight:900;
      color:#0b0f5c;
      margin:6px 0 -2px;
      font-size:16px;
    }
    #major, #specialization{
      width:100%;
      padding:10px 12px;
      border-radius:12px;
      border:1px solid rgba(15,23,42,.14);
      background:#fff;
    }
    .readonly{background:#f6f7ff}
    .muted{color:#8890b4;font-size:12px}
    @media (max-width: 820px){
      .role-group .auth-field.col-6,
      .role-group .auth-field.col-4,
      .role-group .auth-field.col-3,
      .role-group .auth-field.col-12{ grid-column: span 12; }
    }
  </style>
</head>

<body data-theme="<?= h($theme) ?>">

<main class="auth-shell">
  <section class="auth-card auth-card--wide">
    <div class="auth-head">
      <h2 class="auth-title">إنشاء حساب</h2>
      <p class="auth-subtitle">اختر التخصص ونوع الحساب ثم أكمل البيانات</p>
    </div>

    <?= $message ?>

    <!-- ✅ enctype لرفع الملفات -->
    <form method="POST" class="auth-form" autocomplete="on" enctype="multipart/form-data">
      <div class="auth-grid">

        <div class="auth-field col-6">
          <label>التخصص</label>
          <select name="specialization" id="specialization" required>
            <option value="">اختر التخصص</option>
            <?php foreach ($SPECIALIZATIONS as $k => $cfg): ?>
              <option value="<?= h($k) ?>" <?= ($specSel===$k ? 'selected':'') ?>>
                <?= h($cfg['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="auth-field col-6">
          <label>نوع الحساب</label>
          <select name="account_type" id="account_type" required>
            <option value="">اختر</option>
            <option value="trainee" <?= ($accSel==='trainee'?'selected':'') ?>>متدرب</option>
            <option value="provider" <?= ($accSel==='provider'?'selected':'') ?>>مزود تدريب</option>
          </select>
        </div>

        <div class="auth-field col-6">
          <label>البريد الإلكتروني</label>
          <input type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>">
        </div>

        <div class="auth-field col-6">
          <label>رقم الهاتف (اختياري)</label>
          <input type="text" name="phone" value="<?= h($_POST['phone'] ?? '') ?>">
        </div>

        <div class="auth-field col-6">
          <label>العنوان (اختياري)</label>
          <input type="text" name="address" value="<?= h($_POST['address'] ?? '') ?>">
        </div>

        <div class="auth-field col-6">
          <label>كلمة المرور</label>
          <input type="password" name="password" required>
        </div>

        <!-- ===== trainee fields ===== -->
        <div id="trainee_fields" class="role-group">
          <div class="form-section-title">بيانات المتدرب</div>

          <div class="auth-field col-3">
            <label>الاسم الأول</label>
            <input type="text" id="first_name" name="first_name" value="<?= h($_POST['first_name'] ?? '') ?>">
          </div>
          <div class="auth-field col-3">
            <label>اسم الأب</label>
            <input type="text" id="father_name" name="father_name" value="<?= h($_POST['father_name'] ?? '') ?>">
          </div>
          <div class="auth-field col-3">
            <label>اسم الجد</label>
            <input type="text" id="grandfather_name" name="grandfather_name" value="<?= h($_POST['grandfather_name'] ?? '') ?>">
          </div>
          <div class="auth-field col-3">
            <label>اسم العائلة</label>
            <input type="text" id="family_name" name="family_name" value="<?= h($_POST['family_name'] ?? '') ?>">
          </div>

          <div class="auth-field col-12">
            <label>الاسم الكامل</label>
            <input type="text" name="full_name" id="full_name" readonly class="readonly"
                   value="<?= h($_POST['full_name'] ?? '') ?>">
          </div>

          <div class="auth-field col-6">
            <label>الجامعة (اختياري)</label>
            <input type="text" name="university" value="<?= h($_POST['university'] ?? '') ?>">
          </div>

          <div class="auth-field col-6">
            <label>التخصص الفرعي (اختياري)</label>
            <select name="major" id="major">
              <option value="">اختر</option>
            </select>
            <div class="muted" style="margin-top:6px;">يتغير حسب التخصص المختار بالأعلى.</div>
          </div>

          <div class="auth-field col-12">
            <label>المهارات (اختياري)</label>
            <input type="text" name="skills" placeholder="مثل: Excel, Communication"
                   value="<?= h($_POST['skills'] ?? '') ?>">
          </div>

          <div class="auth-field col-12">
            <label>LinkedIn (اختياري)</label>
            <input type="text" name="linkedin_url" placeholder="linkedin.com/in/username"
                   value="<?= h($_POST['linkedin_url'] ?? '') ?>">
          </div>

          <!-- ✅ GitHub يظهر فقط إذا specialization = it -->
          <div class="auth-field col-12" id="github_field" style="display:none;">
            <label>GitHub (اختياري)</label>
            <input type="text" name="github_url" id="github_url" placeholder="github.com/username"
                   value="<?= h($_POST['github_url'] ?? '') ?>">
          </div>

          <!-- ✅ CV لكل المجالات (للمتدرب) -->
          <div class="auth-field col-12">
            <label>السيرة الذاتية (PDF/DOC/DOCX) (اختياري)</label>
            <input type="file" name="cv_file" id="cv_file" accept=".pdf,.doc,.docx">
            <div class="muted" style="margin-top:6px;">الحد الأقصى 5MB.</div>
          </div>
        </div>

        <!-- ===== provider fields ===== -->
        <div id="provider_fields" class="role-group">
          <div class="form-section-title">بيانات مزود التدريب</div>

          <div class="auth-field col-12">
            <label>اسم الشركة</label>
            <input type="text" id="company_name" name="company_name" value="<?= h($_POST['company_name'] ?? '') ?>">
          </div>

          <div class="auth-field col-6">
            <label>المدينة (اختياري)</label>
            <input type="text" name="city" value="<?= h($_POST['city'] ?? '') ?>">
          </div>

          <div class="auth-field col-12">
            <label>وصف (اختياري)</label>
            <textarea name="description" rows="3"><?= h($_POST['description'] ?? '') ?></textarea>
          </div>
        </div>

      </div>

      <button type="submit" class="auth-submit">إنشاء الحساب</button>
    </form>

    <div class="auth-foot">
      <span>لديك حساب؟</span>
      <a class="auth-link" href="login_email.php">تسجيل الدخول</a>
    </div>

  </section>
</main>

<script>
// ✅ majors map من PHP إلى JS
const majorsBySpec = <?= json_encode(array_map(fn($cfg)=>$cfg['majors'], $SPECIALIZATIONS), JSON_UNESCAPED_UNICODE); ?>;

// ✅ احتفظ بالقيمة السابقة إذا صار POST وفيه أخطاء
const prevMajor = <?= json_encode($_POST['major'] ?? '', JSON_UNESCAPED_UNICODE); ?>;

function updateFullName(){
  const first = ($("#first_name").val() || "").trim();
  const father = ($("#father_name").val() || "").trim();
  const grand = ($("#grandfather_name").val() || "").trim();
  const family = ($("#family_name").val() || "").trim();
  $("#full_name").val([first,father,grand,family].filter(Boolean).join(" "));
}

function fillMajors(){
  const spec = ($("#specialization").val() || "").trim();
  const majorSelect = $("#major");

  majorSelect.empty();
  majorSelect.append(new Option("اختر", ""));

  const options = majorsBySpec[spec] || [];
  options.forEach(opt => {
    const o = new Option(opt, opt);
    if (opt === prevMajor) o.selected = true;
    majorSelect.append(o);
  });

  majorSelect.prop("disabled", spec === "");
}

// ✅ GitHub يظهر فقط لتخصص IT
function toggleGithub(){
  const spec = ($("#specialization").val() || "").trim();
  if (spec === "it") {
    $("#github_field").show();
  } else {
    $("#github_field").hide();
    $("#github_url").val("");
  }
}

function toggleFields(){
  const t = $("#account_type").val();
  $("#trainee_fields,#provider_fields").removeClass("active");

  if(t === "trainee"){
    $("#trainee_fields").addClass("active");
    $("#company_name").prop("required", false);
    $("#first_name,#father_name,#grandfather_name,#family_name").prop("required", true);
  } else if(t === "provider"){
    $("#provider_fields").addClass("active");
    $("#company_name").prop("required", true);
    $("#first_name,#father_name,#grandfather_name,#family_name").prop("required", false);
  } else {
    $("#company_name").prop("required", false);
    $("#first_name,#father_name,#grandfather_name,#family_name").prop("required", false);
  }
}

$("#first_name,#father_name,#grandfather_name,#family_name").on("input", updateFullName);

$("#account_type").on("change", function(){
  toggleFields();
  updateFullName();
});

$("#specialization").on("change", function(){
  fillMajors();
  toggleGithub();
});

$(document).ready(function(){
  toggleFields();
  updateFullName();
  fillMajors();
  toggleGithub();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
