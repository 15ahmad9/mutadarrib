<?php
require_once("../config/db.php");
header('Content-Type: application/json');

$national_id = $_POST['national_id'] ?? '';
if(!$national_id){
    echo json_encode(['found'=>false]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM lawyers_master WHERE national_id = ?");
$stmt->execute([$national_id]);
$lawyer = $stmt->fetch(PDO::FETCH_ASSOC);

if($lawyer){
    echo json_encode([
        'found' => true,
        'first_name' => $lawyer['first_name'] ?? '',
        'father_name' => $lawyer['father_name'] ?? '',
        'grandfather_name' => $lawyer['grandfather_name'] ?? '',
        'family_name' => $lawyer['family_name'] ?? '',
        'national_id' => $lawyer['national_id'] ?? '',
        'social_security' => $lawyer['social_security'] ?? '',
        'residence_address' => $lawyer['residence_address'] ?? '',
        'office_address' => $lawyer['office_address'] ?? '',
        'highschool_certificate' => $lawyer['highschool_certificate'] ?? '',
        'university_degree' => $lawyer['university_degree'] ?? '',
        'phone' => $lawyer['phone'] ?? '',
        'email' => $lawyer['email'] ?? ''
    ]);
} else {
    echo json_encode(['found'=>false]);
}
