<?php
require_once __DIR__ . '/../includes/theme_init.php';

require_once("../config/db.php");
include("../includes/header.php");

$message = "";

// ===== Helpers =====
function norm_url($url) {
    $url = trim($url ?? '');
    if ($url === '') return null;
    if (!preg_match('~^https?://~i', $url)) $url = 'https://' . $url;
    return $url;
}
function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $role         = $_POST['role'] ?? '';
    $raw_password = $_POST['password'] ?? '';

    // ===== Shared fields =====
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // ===== Provider fields =====
    $company_name            = trim($_POST['company_name'] ?? '');
    $company_registration_no = trim($_POST['company_registration_no'] ?? '');
    $website                 = norm_url($_POST['website'] ?? '');
    $description             = trim($_POST['description'] ?? '');
    $city                    = trim($_POST['city'] ?? '');
    $country                 = trim($_POST['country'] ?? '');

    // ===== Trainee fields =====
    $first_name        = trim($_POST['first_name'] ?? '');
    $father_name       = trim($_POST['father_name'] ?? '');
    $grandfather_name  = trim($_POST['grandfather_name'] ?? '');
    $family_name       = trim($_POST['family_name'] ?? '');
    $full_name         = trim("$first_name $father_name $grandfather_name $family_name");

    $university      = trim($_POST['university'] ?? '');
    $major           = trim($_POST['major'] ?? '');
    $graduation_year = $_POST['graduation_year'] ?? null;
    $skills          = trim($_POST['skills'] ?? '');
    $github_url      = norm_url($_POST['github_url'] ?? '');
    $linkedin_url    = norm_url($_POST['linkedin_url'] ?? '');

    // ===== Validations =====
    $errors = [];

    if (!in_array($role, ['IT_Provider', 'IT_Trainee'], true)) {
        $errors[] = "يرجى اختيار نوع حساب IT صحيح.";
    }

    if (mb_strlen($raw_password) < 6) {
        $errors[] = "كلمة المرور يجب أن تكون 6 أحرف/أرقام على الأقل.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "البريد الإلكتروني غير صحيح.";
    }

    if (!preg_match('/^[0-9+\s\-]{8,30}$/', $phone)) {
        $errors[] = "رقم الهاتف غير صحيح.";
    }

    if ($address === '') {
        $errors[] = "العنوان مطلوب.";
    }

    // role-specific
    if ($role === 'IT_Provider') {
        if ($company_name === '') $errors[] = "اسم الشركة/الجهة مطلوب.";
    }

    if ($role === 'IT_Trainee') {
        if ($first_name === '' || $father_name === '' || $grandfather_name === '' || $family_name === '') {
            $errors[] = "يرجى تعبئة الاسم الرباعي بالكامل.";
        }
    }

    if (!empty($errors)) {
        $message = "<p class='error'>❌ " . implode("<br>", array_map('h', $errors)) . "</p>";
    } else {

        $password = password_hash($raw_password, PASSWORD_BCRYPT);

        $uploadedCvAbsPath = null;
        $cv_path = null;

        try {
            // email uniqueness
            $checkEmail = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $checkEmail->execute([$email]);
            if ($checkEmail->rowCount() > 0) {
                $message = "<p class='error'>هذا البريد الإلكتروني مستخدم مسبقاً.</p>";
            } else {

                $pdo->beginTransaction();

                // ===== Create user =====
                $users_full_name = ($role === 'IT_Provider') ? $company_name : $full_name;

                // ✅ لا رقم وطني نهائياً
                $users_national_id = null;

                $insertUser = $pdo->prepare("
                    INSERT INTO users (full_name, national_id, phone, email, address, password, role)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $insertUser->execute([
                    $users_full_name,
                    $users_national_id,
                    $phone,
                    $email,
                    $address,
                    $password,
                    $role
                ]);

                $user_id = (int)$pdo->lastInsertId();

                // ===== Role-specific insert =====
                if ($role === 'IT_Provider') {

                    $insertProvider = $pdo->prepare("
                        INSERT INTO it_providers
                        (user_id, company_name, company_registration_no, website, description, city, country)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insertProvider->execute([
                        $user_id,
                        $company_name,
                        ($company_registration_no !== '' ? $company_registration_no : null),
                        $website,
                        ($description !== '' ? $description : null),
                        ($city !== '' ? $city : null),
                        ($country !== '' ? $country : null),
                    ]);

                    $pdo->commit();
                    $message = "<p class='success'>✅ تم إنشاء حساب الشركة/مزود IT بنجاح!</p>";

                } else { // IT_Trainee

                    // Upload CV optional
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

                        $safeName = "cv_{$user_id}_" . time() . "." . $ext;
                        $destPath = $uploadDir . $safeName;

                        if (!move_uploaded_file($_FILES['cv_file']['tmp_name'], $destPath)) {
                            throw new Exception("تعذر حفظ ملف CV.");
                        }

                        $uploadedCvAbsPath = $destPath;
                        $cv_path = "uploads/cv/" . $safeName;
                    }

                    $gy = ($graduation_year !== '' && $graduation_year !== null) ? (int)$graduation_year : null;

                    $insertTrainee = $pdo->prepare("
                        INSERT INTO it_trainees
                        (user_id, university, major, graduation_year, skills, github_url, linkedin_url, cv_file_path)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insertTrainee->execute([
                        $user_id,
                        ($university !== '' ? $university : null),
                        ($major !== '' ? $major : null),
                        $gy,
                        ($skills !== '' ? $skills : null),
                        $github_url,
                        $linkedin_url,
                        $cv_path
                    ]);

                    $pdo->commit();
                    $message = "<p class='success'>✅ تم إنشاء حساب متدرب IT بنجاح!</p>";
                }
            }

        } catch (Exception $e) {

            if ($pdo->inTransaction()) $pdo->rollBack();

            if ($uploadedCvAbsPath && file_exists($uploadedCvAbsPath)) {
                @unlink($uploadedCvAbsPath);
            }

            $message = "<p class='error'>❌ خطأ: " . h($e->getMessage()) . "</p>";
        }
    }
}

// Preselect role
$pref_role = $_GET['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تسجيل IT</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- تحسينات ستايل للصفحة لتطلع مثل الصورة -->
  <style>
    .auth-card--wide{ width:min(980px, 94vw); }

    /* مجموعات حقول role: لازم تكون Grid وتاخذ كامل العرض */
    .role-group{
      grid-column: span 12;
      display: none;            /* مخفية افتراضياً */
      grid-template-columns: repeat(12, minmax(0, 1fr));
      gap: 12px 14px;
      margin-top: 8px;
      padding-top: 6px;
      border-top: 1px solid rgba(15,23,42,.08);
    }
    .role-group.active{ display: grid; }

    /* عنوان قسم داخل الفورم */
    .form-section-title{
      grid-column: span 12;
      font-weight: 900;
      color: #0b0f5c;
      margin: 6px 0 -2px;
      font-size: 16px;
    }

    /* تحسين عرض select/input */
    .auth-field select,
    .auth-field input,
    .auth-field textarea{
      width: 100%;
    }

    /* على الشاشات الصغيرة: كل الأعمدة تصير سطر كامل */
    @media (max-width: 820px){
      .role-group .auth-field.col-6,
      .role-group .auth-field.col-4,
      .role-group .auth-field.col-3,
      .role-group .auth-field.col-12{
        grid-column: span 12;
      }
    }

    #major{
  width:100%;
  padding:10px 12px;
  border-radius:12px;
  border:1px solid rgba(15,23,42,.14);
  background:#fff;
}

  </style>
</head>

<body data-theme="<?= h($theme) ?>">

<main class="auth-shell">
  <div class="auth-card auth-card--wide">
    <div class="auth-head">
      <h2 class="auth-title">إنشاء حساب IT</h2>
      <p class="auth-subtitle">يرجى تعبئة البيانات التالية</p>
    </div>

    <?= $message ?>

    <form method="POST" class="auth-form" autocomplete="on" enctype="multipart/form-data">
      <div class="auth-grid">

        <!-- ===== Shared ===== -->
        <div class="auth-field col-6">
          <label for="role">نوع الحساب:</label>
          <select name="role" id="role" required>
              <option value="">اختر</option>
              <option value="IT_Trainee" <?= ($pref_role==='IT_Trainee' ? 'selected' : '') ?>>متدرب IT</option>
              <option value="IT_Provider" <?= ($pref_role==='IT_Provider' ? 'selected' : '') ?>>شركة / مزود IT</option>
          </select>
        </div>

        <div class="auth-field col-6">
          <label for="phone">الهاتف:</label>
          <input type="text" name="phone" id="phone" required value="<?= h($_POST['phone'] ?? '') ?>">
        </div>

        <div class="auth-field col-6">
          <label for="email">البريد الإلكتروني:</label>
          <input type="email" name="email" id="email" required value="<?= h($_POST['email'] ?? '') ?>">
        </div>

        <div class="auth-field col-6">
          <label for="address">العنوان:</label>
          <input type="text" name="address" id="address" required value="<?= h($_POST['address'] ?? '') ?>">
        </div>

        <div class="auth-field col-12">
          <label for="password">كلمة المرور:</label>
          <input type="password" name="password" id="password" required>
        </div>

        <!-- ========================= -->
        <!-- ===== Provider fields ==== -->
        <!-- ========================= -->
        <div id="provider_fields" class="role-group">
          <div class="form-section-title">بيانات الشركة / مزود IT</div>

          <div class="auth-field col-12">
            <label for="company_name">اسم الشركة / الجهة:</label>
            <input type="text" name="company_name" id="company_name" value="<?= h($_POST['company_name'] ?? '') ?>">
          </div>

          <div class="auth-field col-6">
            <label for="company_registration_no">رقم تسجيل الشركة (اختياري):</label>
            <input type="text" name="company_registration_no" id="company_registration_no" value="<?= h($_POST['company_registration_no'] ?? '') ?>">
          </div>

          <div class="auth-field col-6">
            <label for="website">الموقع الإلكتروني (اختياري):</label>
            <input type="text" name="website" id="website" placeholder="example.com" value="<?= h($_POST['website'] ?? '') ?>">
          </div>

          <div class="auth-field col-6">
            <label for="city">المدينة (اختياري):</label>
            <input type="text" name="city" id="city" value="<?= h($_POST['city'] ?? '') ?>">
          </div>

          <div class="auth-field col-6">
            <label for="country">الدولة (اختياري):</label>
            <input type="text" name="country" id="country" value="<?= h($_POST['country'] ?? 'الأردن') ?>">
          </div>

          <div class="auth-field col-12">
            <label for="description">وصف عن الشركة (اختياري):</label>
            <textarea name="description" id="description" rows="3"><?= h($_POST['description'] ?? '') ?></textarea>
          </div>
        </div>

        <!-- ========================= -->
        <!-- ===== Trainee fields ===== -->
        <!-- ========================= -->
        <div id="trainee_fields" class="role-group">
          <div class="form-section-title">بيانات المتدرب IT</div>

          <!-- ✅ الآن رح تطلع 4 حقول على نفس السطر مثل الصورة -->
          <div class="auth-field col-3">
            <label for="first_name">الاسم الأول:</label>
            <input type="text" name="first_name" id="first_name" value="<?= h($_POST['first_name'] ?? '') ?>">
          </div>

          <div class="auth-field col-3">
            <label for="father_name">اسم الأب:</label>
            <input type="text" name="father_name" id="father_name" value="<?= h($_POST['father_name'] ?? '') ?>">
          </div>

          <div class="auth-field col-3">
            <label for="grandfather_name">اسم الجد:</label>
            <input type="text" name="grandfather_name" id="grandfather_name" value="<?= h($_POST['grandfather_name'] ?? '') ?>">
          </div>

          <div class="auth-field col-3">
            <label for="family_name">اسم العائلة:</label>
            <input type="text" name="family_name" id="family_name" value="<?= h($_POST['family_name'] ?? '') ?>">
          </div>

          <div class="auth-field col-12">
            <label for="full_name">الاسم الكامل:</label>
            <input type="text" name="full_name" id="full_name" readonly class="readonly" value="<?= h($_POST['full_name'] ?? '') ?>">
          </div>

          <div class="auth-field col-6">
            <label for="university">الجامعة (اختياري):</label>
            <input type="text" name="university" id="university" value="<?= h($_POST['university'] ?? '') ?>">
          </div>

<div class="auth-field col-6">
  <label for="major">التخصص (اختياري):</label>
  <?php $majorSel = $_POST['major'] ?? ''; ?>
  <select name="major" id="major">
    <option value="">اختر التخصص</option>
    <?php
      $options = [
        'علوم الحاسوب',
        'هندسة البرمجيات',
        'نظم المعلومات',
        'الأمن السيبراني',
        'الذكاء الاصطناعي',
        'علم البيانات',
        'شبكات الحاسوب',
        'تطوير تطبيقات الموبايل',
        'تطوير الويب',
        'هندسة الحاسوب'
      ];
      foreach ($options as $opt):
        $selected = ($opt === $majorSel) ? 'selected' : '';
    ?>
      <option value="<?= h($opt) ?>" <?= $selected ?>><?= h($opt) ?></option>
    <?php endforeach; ?>
  </select>
</div>


          <div class="auth-field col-6">
            <label for="graduation_year">سنة التخرج (اختياري):</label>
            <input type="number" name="graduation_year" id="graduation_year" min="1990" max="2100" value="<?= h($_POST['graduation_year'] ?? '') ?>">
          </div>

          <div class="auth-field col-6">
            <label for="skills">المهارات (اختياري):</label>
            <input type="text" name="skills" id="skills" placeholder="HTML, CSS, PHP..." value="<?= h($_POST['skills'] ?? '') ?>">
          </div>

          <div class="auth-field col-6">
            <label for="github_url">GitHub (اختياري):</label>
            <input type="text" name="github_url" id="github_url" placeholder="github.com/username" value="<?= h($_POST['github_url'] ?? '') ?>">
          </div>

          <div class="auth-field col-6">
            <label for="linkedin_url">LinkedIn (اختياري):</label>
            <input type="text" name="linkedin_url" id="linkedin_url" placeholder="linkedin.com/in/..." value="<?= h($_POST['linkedin_url'] ?? '') ?>">
          </div>

          <div class="auth-field col-6">
            <label for="cv_file">السيرة الذاتية (PDF/DOC/DOCX) (اختياري):</label>
            <input type="file" name="cv_file" id="cv_file" accept=".pdf,.doc,.docx">
          </div>

        </div>

      </div>

      <button type="submit" class="auth-submit">إنشاء الحساب</button>
    </form>
  </div>
</main>

<script>
function updateFullName() {
  const first = $("#first_name").val().trim();
  const father = $("#father_name").val().trim();
  const grand = $("#grandfather_name").val().trim();
  const family = $("#family_name").val().trim();
  $("#full_name").val([first, father, grand, family].filter(Boolean).join(' '));
}

$("#first_name, #father_name, #grandfather_name, #family_name").on('input', updateFullName);

function toggleRoleFields(){
  const role = $("#role").val();

  // اخفاء الكل
  $("#provider_fields, #trainee_fields").removeClass("active");

  if(role === "IT_Provider"){
    $("#provider_fields").addClass("active");
    $("#company_name").prop("required", true);

    $("#first_name, #father_name, #grandfather_name, #family_name").prop("required", false);

  } else if(role === "IT_Trainee"){
    $("#trainee_fields").addClass("active");

    $("#first_name, #father_name, #grandfather_name, #family_name").prop("required", true);
    $("#company_name").prop("required", false);

  } else {
    $("#company_name").prop("required", false);
    $("#first_name, #father_name, #grandfather_name, #family_name").prop("required", false);
  }
}

$("#role").on("change", function(){
  toggleRoleFields();
  updateFullName();
});

$(document).ready(function(){
  updateFullName();
  toggleRoleFields();
});
</script>

<?php include("../includes/footer.php"); ?>
</body>
</html>
