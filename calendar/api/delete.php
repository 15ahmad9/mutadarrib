<?php
session_start();
require_once("../../config/db.php");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents("php://input"), true) ?? [];

$event_id = isset($input["event_id"]) ? (int)$input["event_id"] : 0;
if ($event_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid id"]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM calendar_events WHERE event_id=? AND user_id=?");
$stmt->execute([$event_id, $user_id]);

echo json_encode(["success" => true, "message" => "Deleted"], JSON_UNESCAPED_UNICODE);
