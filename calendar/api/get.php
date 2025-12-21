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
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid id"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM calendar_events
    WHERE event_id = ? AND user_id = ?
    LIMIT 1
");
$stmt->execute([$event_id, $user_id]);
$ev = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ev) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Not found"]);
    exit;
}

// تجهيز قيم datetime-local
function toLocalInput($dt) {
    if (!$dt) return null;
    $d = new DateTime($dt);
    return $d->format('Y-m-d\TH:i');
}

$ev["start_at_local"] = toLocalInput($ev["start_at"]);
$ev["end_at_local"]   = toLocalInput($ev["end_at"]);

echo json_encode(["success" => true, "event" => $ev], JSON_UNESCAPED_UNICODE);
