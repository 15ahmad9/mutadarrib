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

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $account_type = $_POST['account_type'] ?? ''; // trainee | provider
  $email        = trim($_POST['email'] ?? '');
  $passwordRaw  = trim($_POST['password'] ?? '');
  $phone        = trim($_POST['phone'] ?? '');
  $address      = trim($_POST['address'] ?? '');

  // التخصص ثابت لهذه الصفحة
  $specialization = "business"; // الأعمال

  // trainee fields (الاسم الرباعي)
  $first_name       = trim($_POST['first_name'] ?? '');
  $father_name      = trim($_POST['father_name'] ?? '');
  $grandfather_name = trim($_POST['grandfather_name'] ?? '');
  $family_name      = trim($_POST['family_name'] ?? '');

  // الاسم الكامل
  $full_name = trim($_POST['full_name'] ?? '');
  // (حماية) إذا JS ما اشتغل
  if ($full_name === '') {
    $full_name = trim(implode(' ', array_filter([$first_name, $father_name, $grandfather_name, $family_name])));
  }

  $university = trim($_POST['university'] ?? '');

  // multi-select majors (array)
$major = trim($_POST['major'] ?? '');
$major = ($major !== '') ? $major : null;

  $skills     = trim($_POST['skills'] ?? '');
  $linkedin   = norm_url($_POST['linkedin_url'] ?? '');

  // provider fields
  $company_name = trim($_POST['company_name'] ?? '');
  $description  = trim($_POST['description'] ?? '');
  $city         = trim($_POST['city'] ?? '');

  $errors = [];

  if (!in_array($account_type, ['trainee','provider'], true)) $errors[] = "يرجى اختيار نوع الحساب.";
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "البريد الإلكتروني غير صحيح.";
  if (mb_strlen($passwordRaw) < 6) $errors[] = "كلمة المرور يجب أن تكون 6 أحرف/أرقام على الأقل.";

  if ($account_type === 'trainee') {
    if ($first_name==='' || $father_name==='' || $grandfather_name==='' || $family_name==='') {
      $errors[] = "يرجى تعبئة الاسم الرباعي بالكامل.";
    }
  }

  if ($account_type === 'provider' && $company_name === '') $errors[] = "اسم الشركة مطلوب لمزود التدريب.";

  if (!$errors) {
    try {
      // تحقق هل الإيميل موجود
      $chk = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
      $chk->execute([$email]);
      if ($chk->fetchColumn()) {
        $errors[] = "هذا البريد مستخدم مسبقاً.";
      } else {
        $pdo->beginTransaction();

        $passwordHash = password_hash($passwordRaw, PASSWORD_BCRYPT);

        // role حسب النوع
        $role = ($account_type === 'provider') ? 'provider' : 'trainee';

        // إدخال المستخدم في users
        $ins = $pdo->prepare("
          INSERT INTO users (full_name, email, phone, address, password, role, specialization)
          VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $displayName = ($account_type === 'provider') ? $company_name : $full_name;

        $ins->execute([
          $displayName,
          $email,
          ($phone !== '' ? $phone : null),
          ($address !== '' ? $address : null),
          $passwordHash,
          $role,
          $specialization
        ]);

        $userId = (int)$pdo->lastInsertId();

        // إدخال تفاصيل حسب نوع الحساب (في جدول تخصص الأعمال)
        if ($account_type === 'trainee') {
          $insT = $pdo->prepare("
            INSERT INTO business_trainees (user_id, university, major, skills, linkedin_url)
            VALUES (?, ?, ?, ?, ?)
          ");
          $insT->execute([
            $userId,
            ($university !== '' ? $university : null),
            $major,
            ($skills !== '' ? $skills : null),
            $linkedin,
          ]);
        } else {
          $insP = $pdo->prepare("
            INSERT INTO business_providers (user_id, company_name, description, city)
            VALUES (?, ?, ?, ?)
          ");
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
      $message = "<p class='error'>❌ خطأ: " . h($e->getMessage()) . "</p>";
    }
  } else {
    $message = "<p class='error'>❌ " . implode("<br>", array_map('h', $errors)) . "</p>";
  }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تسجيل الأعمال</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body data-theme="<?= h($theme) ?>">

<main class="auth-shell">
  <section class="auth-card auth-card--wide">
    <div class="auth-head">
      <h2 class="auth-title">إنشاء حساب - تخصص الأعمال</h2>
      <p class="auth-subtitle">اختر متدرب أو مزود تدريب ثم أكمل البيانات</p>
    </div>

    <?= $message ?>

    <form method="POST" class="auth-form">
      <div class="auth-grid">

        <div class="auth-field col-6">
          <label>نوع الحساب</label>
          <select name="account_type" id="account_type" required>
            <option value="">اختر</option>
            <option value="trainee" <?= (($_POST['account_type'] ?? '')==='trainee'?'selected':'') ?>>متدرب</option>
            <option value="provider" <?= (($_POST['account_type'] ?? '')==='provider'?'selected':'') ?>>مزود تدريب</option>
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

        <div class="auth-field col-12">
          <label>كلمة المرور</label>
          <input type="password" name="password" required>
        </div>

        <!-- trainee -->
        <div id="trainee_fields" style="display:none;width:100%;">

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

          <!-- الاسم الكامل يظهر للمستخدم -->
          <div class="auth-field col-12">
            <label>الاسم الكامل</label>
            <input type="text" name="full_name" id="full_name" readonly class="readonly"
                   value="<?= h($_POST['full_name'] ?? '') ?>">
          </div>

          <div class="auth-field col-6">
            <label>الجامعة (اختياري)</label>
            <input type="text" name="university" value="<?= h($_POST['university'] ?? '') ?>">
          </div>

          <!-- majors multi select -->
<div class="auth-field col-6">
  <label>التخصص</label>
  <?php $majorSel = $_POST['major'] ?? ''; ?>

  <select name="major" id="major">
    <option value="">اختر التخصص</option>
    <?php
      $options = ['إدارة أعمال','محاسبة','تسويق','اقتصاد','تمويل','إدارة موارد بشرية'];
      foreach ($options as $opt):
        $selected = ($opt === $majorSel) ? 'selected' : '';
    ?>
      <option value="<?= h($opt) ?>" <?= $selected ?>><?= h($opt) ?></option>
    <?php endforeach; ?>
  </select>
</div>

          </div>

          <div class="auth-field col-12">
            <label>المهارات (اختياري)</label>
            <input type="text" name="skills" placeholder="مثل: Excel, Accounting, Communication"
                   value="<?= h($_POST['skills'] ?? '') ?>">
          </div>

          <div class="auth-field col-12">
            <label>LinkedIn (اختياري)</label>
            <input type="text" name="linkedin_url" placeholder="linkedin.com/in/username"
                   value="<?= h($_POST['linkedin_url'] ?? '') ?>">
          </div>

        </div>

        <!-- provider -->
        <div id="provider_fields" style="display:none;width:100%;">
          <div class="auth-field col-12">
            <label>اسم الشركة</label>
            <input type="text" name="company_name" value="<?= h($_POST['company_name'] ?? '') ?>">
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
function updateFullName(){
  const first = $("#first_name").val()?.trim() || "";
  const father = $("#father_name").val()?.trim() || "";
  const grand = $("#grandfather_name").val()?.trim() || "";
  const family = $("#family_name").val()?.trim() || "";
  const full = [first, father, grand, family].filter(Boolean).join(" ");
  $("#full_name").val(full);
}

$("#first_name, #father_name, #grandfather_name, #family_name").on("input", updateFullName);

function toggleFields(){
  const t = $("#account_type").val();
  if(t === "trainee"){
    $("#trainee_fields").show();
    $("#provider_fields").hide();
  }else if(t === "provider"){
    $("#provider_fields").show();
    $("#trainee_fields").hide();
  }else{
    $("#trainee_fields").hide();
    $("#provider_fields").hide();
  }
}

$("#account_type").on("change", toggleFields);

$(document).ready(function(){
  toggleFields();
  updateFullName();
});
</script>

<style>
/* تحسين بسيط للـ multi-select */
#majors{
  width:100%;
  padding:10px 12px;
  border-radius:12px;
  border:1px solid rgba(15,23,42,.14);
  background:#fff;
}
.muted{color:#8890b4;font-size:12px}
.readonly{background:#f6f7ff}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
