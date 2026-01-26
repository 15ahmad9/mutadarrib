<?php
require_once __DIR__ . '/../includes/theme_init.php';

session_start();
require_once("../config/db.php");
include("../includes/header.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $identifier = trim($_POST["identifier"] ?? "");
    $password   = trim($_POST["password"] ?? "");

    $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

    /* ======================================
        1) تسجيل دخول المدير Admin
    ====================================== */
    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE " . ($isEmail ? "email = ?" : "national_id = ?") . "
          AND role = 'admin'
        LIMIT 1
    ");
    $stmt->execute([$identifier]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {

        $_SESSION['user_id']   = (int) $admin['user_id'];
        $_SESSION['role']      = 'admin';
        $_SESSION['full_name'] = $admin['full_name'];

        header("Location: ../admin/dashboard.php");
        exit;
    }

    /* ======================================
        2) تسجيل دخول موظف النقابة Syndicate Admin
    ====================================== */
    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE " . ($isEmail ? "email = ?" : "national_id = ?") . "
          AND role = 'syndicate_admin'
        LIMIT 1
    ");
    $stmt->execute([$identifier]);
    $syndicate = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($syndicate && password_verify($password, $syndicate['password'])) {

        $_SESSION['user_id']   = (int) $syndicate['user_id'];
        $_SESSION['role']      = 'syndicate_admin';
        $_SESSION['full_name'] = $syndicate['full_name'];

        header("Location: ../syndicate/dashboard.php");
        exit;
    }

    /* ======================================
        3) تسجيل دخول المحامي Lawyer
        - يدعم الدخول بالإيميل أو الرقم الوطني
        - شرط: لازم يكون مسجل بالنقابة (حسب الرقم الوطني)
    ====================================== */

    // أولاً: اجلب user الذي role=lawyer
    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE " . ($isEmail ? "email = ?" : "national_id = ?") . "
          AND role = 'lawyer'
        LIMIT 1
    ");
    $stmt->execute([$identifier]);
    $lawyerUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lawyerUser && password_verify($password, $lawyerUser['password'])) {

        // لازم يكون عنده رقم وطني حتى نتحقق من النقابة
        $lawyerNID = $lawyerUser['national_id'] ?? null;

        if (!$lawyerNID) {
            $message = "<p class='error'>❌ لا يمكن التحقق من النقابة لأن الرقم الوطني غير موجود لحساب المحامي.</p>";
        } else {
            // التحقق من تسجيله في النقابة
            $chk = $pdo->prepare("
                SELECT syndicate_id
                FROM lawyers_syndicate
                WHERE national_id = ?
                LIMIT 1
            ");
            $chk->execute([$lawyerNID]);
            $inSyndicate = $chk->fetch(PDO::FETCH_ASSOC);

            if (!$inSyndicate) {
                $message = "<p class='error'>❌ غير مسموح — الرقم الوطني غير مسجل في نقابة المحامين.</p>";
            } else {

                $_SESSION['user_id']   = (int) $lawyerUser['user_id'];
                $_SESSION['role']      = 'lawyer';
                $_SESSION['full_name'] = $lawyerUser['full_name'];

                header("Location: /mutadarrib/index.php");
                exit;
            }
        }
    }

    /* ======================================
        4) تسجيل دخول المتدرب Trainee
        - يدعم الدخول بالإيميل أو الرقم الوطني
    ====================================== */
    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE " . ($isEmail ? "email = ?" : "national_id = ?") . "
          AND role = 'trainee'
        LIMIT 1
    ");
    $stmt->execute([$identifier]);
    $traineeUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($traineeUser && password_verify($password, $traineeUser['password'])) {

        $_SESSION['user_id']   = (int) $traineeUser['user_id'];
        $_SESSION['role']      = 'trainee';
        $_SESSION['full_name'] = $traineeUser['full_name'];

        header("Location: /mutadarrib/index.php");
        exit;
    }

    /* ======================================
        5) تسجيل دخول IT Provider
        - الأفضل يكون بالإيميل (لأن ممكن ما عنده رقم وطني)
    ====================================== */
    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE " . ($isEmail ? "email = ?" : "national_id = ?") . "
          AND role = 'IT_Provider'
        LIMIT 1
    ");
    $stmt->execute([$identifier]);
    $itProvider = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($itProvider && password_verify($password, $itProvider['password'])) {

        $_SESSION['user_id']   = (int) $itProvider['user_id'];
        $_SESSION['role']      = 'IT_Provider';
        $_SESSION['full_name'] = $itProvider['full_name'];

        header("Location: ../it/provider_dashboard.php"); // عدّل المسار حسب مشروعك
        exit;
    }

    /* ======================================
        6) تسجيل دخول IT Trainee
    ====================================== */
    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE " . ($isEmail ? "email = ?" : "national_id = ?") . "
          AND role = 'IT_Trainee'
        LIMIT 1
    ");
    $stmt->execute([$identifier]);
    $itTrainee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($itTrainee && password_verify($password, $itTrainee['password'])) {

        $_SESSION['user_id']   = (int) $itTrainee['user_id'];
        $_SESSION['role']      = 'IT_Trainee';
        $_SESSION['full_name'] = $itTrainee['full_name'];

        header("Location: ../index.php"); // عدّل المسار حسب مشروعك
        exit;
    }

    /* ======================================
        فشل جميع محاولات الدخول
    ====================================== */
    $message = "<p class='error'>❌ الرقم الوطني / البريد الإلكتروني أو كلمة المرور غير صحيحة.</p>";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body data-theme="<?= htmlspecialchars($theme) ?>">

<main class="auth-shell">
    <section class="auth-card">
        <div class="auth-head">
            <h2 class="auth-title">تسجيل الدخول</h2>
            <p class="auth-subtitle">أدخل الرقم الوطني أو البريد الإلكتروني وكلمة المرور للمتابعة.</p>
        </div>

        <?= $message ?>

        <form method="POST" class="auth-form" autocomplete="on">
            <div class="auth-field">
                <label for="identifier">الرقم الوطني أو البريد الإلكتروني</label>
                <input
                    id="identifier"
                    type="text"
                    name="identifier"
                    required
                    autocomplete="username"
                    placeholder="0123456789 أو user@email.com"
                    value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
                >
            </div>

            <div class="auth-field">
                <label for="password">كلمة المرور</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
            </div>

            <button class="auth-submit" type="submit">تسجيل الدخول</button>
        </form>

        <div class="auth-foot">
            <span>ليس لديك حساب؟</span>
            <a class="auth-link" href="choose_specialization.php">إنشاء حساب</a>
        </div>
    </section>
</main>

<?php include("../includes/footer.php"); ?>
</body>
</html>
