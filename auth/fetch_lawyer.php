<?php
require_once("../config/db.php");
header('Content-Type: application/json');

$national_id = $_POST['national_id'] ?? '';

if (!$national_id) {
    echo json_encode(['found' => false]);
    exit;
}

// جلب بيانات المحامي من جدول النقابة
$stmt = $pdo->prepare("SELECT * FROM lawyers_syndicate WHERE national_id = ?");
$stmt->execute([$national_id]);
$lawyer = $stmt->fetch(PDO::FETCH_ASSOC);

if ($lawyer) {
    echo json_encode([
        'found' => true,

        // الاسم الكامل مقسم
        'first_name'         => $lawyer['first_name'] ?? '',
        'father_name'        => $lawyer['father_name'] ?? '',
        'grandfather_name'   => $lawyer['grandfather_name'] ?? '',
        'family_name'        => $lawyer['family_name'] ?? '',

        // الرقم الوطني
        'national_id'        => $lawyer['national_id'] ?? '',

        // رقم الضمان الاجتماعي (مفعل الآن)
        'social_security'    => $lawyer['social_security'] ?? '',

        // العناوين
        'residence_address'  => $lawyer['residence_address'] ?? '',
        'office_address'     => $lawyer['office_address'] ?? '',

        // الشهادات
        'highschool_certificate' => $lawyer['highschool_certificate'] ?? '',
        'university_degree'      => $lawyer['university_degree'] ?? '',

        // معلومات التواصل
        'phone'              => $lawyer['phone'] ?? '',
        'email'              => $lawyer['email'] ?? ''
    ]);
} else {
    echo json_encode(['found' => false]);
}
