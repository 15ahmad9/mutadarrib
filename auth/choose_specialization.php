<?php
require_once __DIR__ . '/../includes/theme_init.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
  <head>
    <meta charset="UTF-8">
    <title>اختيار التخصص</title>
    <link rel="stylesheet" href="../assets/css/style.css">
  </head>
  <body class="layout-sticky" data-theme="<?= htmlspecialchars($theme) ?>">

    <?php include("../includes/header.php"); ?>

    <main class="main-content auth-shell">
      <section class="auth-card">
        <div class="auth-head">
          <h2 class="auth-title">اختر التخصص للتسجيل</h2>
          <p class="auth-subtitle">اختر تخصصك ثم اضغط متابعة للانتقال لصفحة إنشاء الحساب.</p>
        </div>

        <div class="auth-form">
          <div class="auth-field">
            <label for="specialization">اختر التخصص</label>
            <select id="specialization" required>
              <option value="">اختر</option>
              <option value="it">تكنولوجيا معلومات</option>
              <option value="lawyer" class="disabled-option" disabled>محاماه (غير متاح حاليًا)</option>
              <option value="engineering" class="disabled-option" disabled>هندسة (غير متاح حاليًا)</option>
              <option value="pharmacy" class="disabled-option" disabled>صيدلة (غير متاح حاليًا)</option>
              <option value="nursing" class="disabled-option" disabled>تمريض (غير متاح حاليًا)</option>
              <option value="business" class="disabled-option" disabled>الأعمال (غير متاح حاليًا)</option>
            </select>
          </div>

          <button type="button" class="auth-submit" onclick="goToRegister()">متابعة</button>
        </div>
      </section>
    </main>

<script>
function goToRegister() {
    let s = document.getElementById("specialization").value;

    if (!s) {
        alert("يرجى اختيار تخصص.");
        return;
    }

    // تحويل حسب التخصص
    switch(s) {
        case "lawyer":
            window.location.href = "register.php";
            break;

        case "engineering":
            window.location.href = "register_engineer.php";
            break;

        case "pharmacy":
            window.location.href = "register_pharmacy.php";
            break;

        case "nursing":
            window.location.href = "register_nursing.php";
            break;

        case "it":
            window.location.href = "register_it.php";
            break;

            case "business":
            window.location.href = "register_business.php";
            break;

        default:
            alert("تخصص غير معروف");
    }
}
</script>

    <?php include("../includes/footer.php"); ?>

  </body>
</html>