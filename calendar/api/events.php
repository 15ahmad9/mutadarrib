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

$start = $_GET['start'] ?? null;
$end   = $_GET['end'] ?? null;

// start/end من FullCalendar بصيغة ISO. نحاول نستخدمها للتصفية (اختياري)
try {
    $startDt = $start ? (new DateTime($start))->format('Y-m-d H:i:s') : null;
    $endDt   = $end   ? (new DateTime($end))->format('Y-m-d H:i:s') : null;
} catch (Exception $e) {
    $startDt = null;
    $endDt = null;
}

$sql = "SELECT event_id, title, start_at, end_at, all_day, type
        FROM calendar_events
        WHERE user_id = ?";

$params = [$user_id];

if ($startDt && $endDt) {
    $sql .= " AND start_at < ? AND (end_at IS NULL OR end_at > ?)";
    $params[] = $endDt;
    $params[] = $startDt;
}

$sql .= " ORDER BY start_at ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$events = [];
foreach ($rows as $r) {
    $events[] = [
        "id" => (string)$r["event_id"],
        "title" => $r["title"],
        "start" => (new DateTime($r["start_at"]))->format(DateTime::ATOM),
        "end"   => $r["end_at"] ? (new DateTime($r["end_at"]))->format(DateTime::ATOM) : null,
        "allDay" => ((int)$r["all_day"] === 1),
        // يمكن استخدام extendedProps لاحقًا
    ];
}

echo json_encode(["success" => true, "events" => $events], JSON_UNESCAPED_UNICODE);
