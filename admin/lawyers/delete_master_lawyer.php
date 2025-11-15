<?php
session_start();
require_once("../../config/db.php");

// حماية الوصول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$master_id = $_GET['id'] ?? null;

if ($master_id) {
    try {
        // بدء معاملة
        $pdo->beginTransaction();

        // 1️الحصول على الـ national_id أولاً من جدول النقابة
        $stmt = $pdo->prepare("SELECT national_id FROM lawyers_master WHERE master_id = ?");
        $stmt->execute([$master_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $national_id = $row['national_id'];

            // 2️الحصول على user_id المرتبط بنفس الرقم الوطني
            $stmtUser = $pdo->prepare("SELECT user_id FROM users WHERE national_id = ?");
            $stmtUser->execute([$national_id]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $user_id = $user['user_id'];

                // 3️حذف من جدول lawyers باستخدام user_id (سيتم الحذف تلقائياً لو كان CASCADE)
                $delLawyer = $pdo->prepare("DELETE FROM lawyers WHERE user_id = ?");
                $delLawyer->execute([$user_id]);

                // 4️حذف المستخدم من جدول users
                $delUser = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $delUser->execute([$user_id]);
            }

            // 5️حذف المحامي من جدول النقابة
            $delMaster = $pdo->prepare("DELETE FROM lawyers_master WHERE master_id = ?");
            $delMaster->execute([$master_id]);
        }

        // تأكيد الحذف
        $pdo->commit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("خطأ أثناء الحذف: " . $e->getMessage());
    }
}

header("Location: master_lawyers.php");
exit;
?>