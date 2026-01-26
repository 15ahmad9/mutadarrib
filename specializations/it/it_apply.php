<?php
require_once __DIR__ . '/../includes/theme_init.php';

session_start();
require_once("../config/db.php");
include("../includes/header.php");

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// لازم يكون IT_Trainee
if (($_SESSION['role'] ?? null) !== 'IT_Trainee') {
  echo "<p class='error' style='padding:20px'>❌ هذه الصفحة للمتدربين IT فقط. يرجى تسجيل الدخول.</p>";
  include("../includes/footer.php");
  exit;
}

$trainee_user_id = (int)($_SESSION['user_id'] ?? 0);

$internship_id = (int)($_GET['internship_id'] ?? ($_POST['internship_id'] ?? 0));
if ($internship_id <= 0) {
  echo "<p class='error' style='padding:20px'>معرّف الفرصة غير صحيح.</p>";
  include("../includes/footer.php");
  exit;
}

// جلب بيانات الفرصة (للتحقق + عرض مختصر)
$stmt = $pdo->prepare("
  SELECT i.internship_id, i.title, i.status, p.company_name
  FROM it_internships i
  JOIN it_providers p ON p.user_id = i.provider_user_id
  WHERE i.internship_id = ?
  LIMIT 1
");
$stmt->execute([$internship_id]);
$internship = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$internship || $internship['status'] !== 'published') {
  echo "<p class='error' style='padding:20px'>هذه الفرصة غير متاحة حالياً.</p>";
  include("../includes/footer.php");
  exit;
}

$message = "";

// هل قدم سابقاً؟
$stmtChk = $pdo->prepare("
  SELECT application_id, status, applied_at
  FROM it_applications
  WHERE internship_id = ? AND trainee_user_id = ?
  LIMIT 1
");
$stmtChk->execute([$internship_id, $trainee_user_id]);
$existing = $stmtChk->fetch(PDO::FETCH_ASSOC);

if ($existing && $_SERVER['REQUEST_METHOD'] !== 'POST') {
  $message = "<p class='error'>⚠️ أنت بالفعل قدّمت على هذه الفرصة. الحالة: <strong>" . h($existing['status']) . "</strong></p>";
}

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // إذا قدم سابقاً، لا تعيد التقديم
  if ($existing) {
    $message = "<p class='error'>⚠️ لا يمكنك التقديم مرة أخرى على نفس الفرصة.</p>";
  } else {

    $cover_letter = trim($_POST['cover_letter'] ?? '');
    $cv_path = null;
    $uploadedCvAbsPath = null;

    try {
      // CV اختياري: إذا رفعه المتدرب هنا
      if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES['cv_file']['error'] !== UPLOAD_ERR_OK) {
          throw new Exception("حدث خطأ أثناء رفع ملف السيرة الذاتية.");
        }

        $allowed = [
          'application/pdf' => 'pdf',
          'application/msword' => 'doc',
          'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
        ];

        $mime = @mime_content_type($_FILES['cv_file']['tmp_name']);
        if (!$mime || !isset($allowed[$mime])) {
          throw new Exception("صيغة CV غير مدعومة. المسموح: PDF/DOC/DOCX");
        }

        if ($_FILES['cv_file']['size'] > 5 * 1024 * 1024) {
          throw new Exception("حجم CV كبير. الحد الأقصى 5MB");
        }

        $ext = $allowed[$mime];

        $uploadDir = __DIR__ . "/../uploads/applications/";
        if (!is_dir($uploadDir)) {
          if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception("تعذر إنشاء مجلد رفع الملفات.");
          }
        }

        $safeName = "app_cv_{$trainee_user_id}_{$internship_id}_" . time() . "." . $ext;
        $destPath = $uploadDir . $safeName;

        if (!move_uploaded_file($_FILES['cv_file']['tmp_name'], $destPath)) {
          throw new Exception("تعذر حفظ ملف CV.");
        }

        $uploadedCvAbsPath = $destPath;
        $cv_path = "uploads/applications/" . $safeName;
      }

      // حفظ الطلب
      $stmtIns = $pdo->prepare("
        INSERT INTO it_applications
        (internship_id, trainee_user_id, cover_letter, cv_file_path, status, applied_at)
        VALUES (?, ?, ?, ?, 'submitted', NOW())
      ");

      try {
        $stmtIns->execute([
          $internship_id,
          $trainee_user_id,
          ($cover_letter !== '' ? $cover_letter : null),
          $cv_path
        ]);
      } catch (PDOException $ex) {
        // منع التكرار بسبب UNIQUE(internship_id, trainee_user_id)
        if ((int)$ex->errorInfo[1] === 1062) {
          // cleanup uploaded file
          if ($uploadedCvAbsPath && file_exists($uploadedCvAbsPath)) @unlink($uploadedCvAbsPath);
          $message = "<p class='error'>⚠️ أنت بالفعل قدّمت على هذه الفرصة سابقاً.</p>";
        } else {
          throw $ex;
        }
      }

      if ($message === "") {
        $message = "<p class='success'>✅ تم إرسال طلب التقديم بنجاح!</p>";
      }

    } catch (Exception $e) {
      if ($uploadedCvAbsPath && file_exists($uploadedCvAbsPath)) @unlink($uploadedCvAbsPath);
      $message = "<p class='error'>❌ خطأ: " . h($e->getMessage()) . "</p>";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تقديم على فرصة تدريب</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-theme="<?= h($theme) ?>">

<main class="page-shell">
  <a href="it_internship_view.php?id=<?= (int)$internship_id ?>" class="btn btn-ghost" style="margin-bottom:12px; display:inline-flex;">← الرجوع لتفاصيل الفرصة</a>

  <section class="apply-card">
    <div class="apply-head">
      <h2 class="apply-title">التقديم على فرصة تدريب</h2>
      <p class="apply-subtitle">
        <strong><?= h($internship['title']) ?></strong> — <?= h($internship['company_name']) ?>
      </p>
    </div>

    <?= $message ?>

    <?php if (!$existing): ?>
      <form method="POST" enctype="multipart/form-data" class="auth-form">
        <input type="hidden" name="internship_id" value="<?= (int)$internship_id ?>">

        <div class="auth-field">
          <label for="cover_letter">رسالة التقديم (اختياري)</label>
          <textarea id="cover_letter" name="cover_letter" rows="5" placeholder="اكتب نبذة قصيرة عنك ولماذا أنت مناسب..."><?= h($_POST['cover_letter'] ?? '') ?></textarea>
        </div>

        <div class="auth-field">
          <label for="cv_file">رفع السيرة الذاتية (اختياري) PDF/DOC/DOCX</label>
          <input type="file" id="cv_file" name="cv_file" accept=".pdf,.doc,.docx">
        </div>

        <button type="submit" class="auth-submit">إرسال الطلب</button>
      </form>
    <?php endif; ?>

  </section>
</main>

<style>
/* fallback styles */
.page-shell{max-width:900px;margin:0 auto;padding:28px 16px 70px}
.btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 16px;border-radius:14px;text-decoration:none;font-weight:800;border:0;cursor:pointer}
.btn-ghost{background:#f4f6ff;color:#1b2a7a}
.apply-card{background:#fff;border:1px solid rgba(15,23,42,.06);border-radius:18px;box-shadow:0 10px 26px rgba(0,0,0,.06);padding:18px}
.apply-title{margin:0 0 8px;color:#0b0f5c}
.apply-subtitle{margin:0 0 12px;color:#5b5f85}
.auth-field textarea{width:100%;padding:12px;border-radius:12px;border:1px solid rgba(15,23,42,.12)}
</style>

<?php include("../includes/footer.php"); ?>
</body>
</html>
