<?php include("../../includes/header.php"); ?>
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/lawyers.css">

<div class="container hero-lawyers">

    <h1>⚖️ قسم المحامين</h1>
    <p>
        بوابتك إلى التدريب القانوني المهني المعتمد
        بإشراف نخبة من كبار المحامين.
    </p>

    <div class="lawyers-sections">

        <div class="lawyer-box">
            <h3>🏢 مكاتب المحامين</h3>
            <p>
                تصفّح جميع المكاتب المعتمدة التي تستقبل متدربين.
            </p>
            <a class="btn" href="lawyers_offices.php">
                عرض المكاتب
            </a>
        </div>

        <div class="lawyer-box">
            <h3>📝 التسجيل كمحامي</h3>
            <p>
                هل أنت محامٍ مزاول وتريد استقبال متدربين؟
            </p>
            <a class="btn secondary" href="../../auth/register.php?specialization=lawyer">
                تسجيل محامٍ
            </a>
        </div>

        <div class="lawyer-box">
            <h3>🎓 التسجيل كمتدرب</h3>
            <p>
                ابدأ مسيرتك المهنية القانونية الآن.
            </p>
            <a class="btn secondary" href="../../auth/choose_specialization.php">
                تسجيل متدرب
            </a>
        </div>

    </div>

</div>

<?php include("../../includes/footer.php"); ?>
