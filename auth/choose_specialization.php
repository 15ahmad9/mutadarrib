<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>اختيار التخصص</title>
<style>
body { font-family: Arial; background:#f4f4f4; }
.container { width: 400px; margin: 70px auto; background:white; padding:20px; border-radius:8px; box-shadow:0 0 10px #ccc; }
select, button { width:100%; padding:10px; margin-top:10px; border-radius:6px; border:1px solid #aaa; }
button { background:#0077b6; color:white; cursor:pointer; }
.disabled-option { color:#888; }
</style>
</head>
<body>

<div class="container">
    <h2>اختر التخصص للتسجيل</h2>

    <label>اختر التخصص:</label>
    <select id="specialization" required>
        <option value="">اختر</option>
        <option value="lawyer" >محاماه</option>
        <option value="it" class="disabled-option" disabled>تكنولوجيا معلومات  (غير متاح حاليًا)</option>
        <option value="engineering" class="disabled-option" disabled>هندسة  (غير متاح حاليًا)</option>
        <option value="pharmacy" class="disabled-option" disabled>صيدلة  (غير متاح حاليًا)</option>
        <option value="nursing" class="disabled-option" disabled>تمريض  (غير متاح حاليًا)</option>
    </select>

    <button onclick="goToRegister()">متابعة</button>
</div>

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

        default:
            alert("تخصص غير معروف");
    }
}
</script>

</body>
</html>
