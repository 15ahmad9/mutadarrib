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

$title = trim((string)($input["title"] ?? ""));
$description = trim((string)($input["description"] ?? ""));
$type = ($input["type"] ?? "task");
$all_day = !empty($input["all_day"]) ? 1 : 0;

$reminder_minutes = $input["reminder_minutes"];
$reminder_minutes = ($reminder_minutes === null || $reminder_minutes === "") ? null : (int)$reminder_minutes;

$start_at = $input["start_at"] ?? null; // datetime-local
$end_at   = $input["end_at"] ?? null;   // datetime-local or null

if ($title === "" || !$start_at) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Title and start date are required"]);
    exit;
}

if (!in_array($type, ["task","event"], true)) $type = "task";

function fromLocal($val) {
    if (!$val) return null;
    // datetime-local => "Y-m-d H:i:s"
    $d = DateTime::createFromFormat('Y-m-d\TH:i', $val);
    if (!$d) return null;
    return $d->format('Y-m-d H:i:s');
}

$startDb = fromLocal($start_at);
$endDb   = fromLocal($end_at);

if (!$startDb) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid start_at"]);
    exit;
}

// منطق بسيط: لو النهاية قبل البداية نرفض
if ($endDb && $endDb < $startDb) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "End must be after start"]);
    exit;
}

try {
    if ($event_id > 0) {
        // تحديث: تحقق ملكية الحدث
        $chk = $pdo->prepare("SELECT event_id FROM calendar_events WHERE event_id=? AND user_id=? LIMIT 1");
        $chk->execute([$event_id, $user_id]);
        if (!$chk->fetchColumn()) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Forbidden"]);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE calendar_events SET
              title=?,
              description=?,
              start_at=?,
              end_at=?,
              all_day=?,
              type=?,
              reminder_minutes=?
            WHERE event_id=? AND user_id=?
        ");
        $stmt->execute([
            $title,
            ($description !== "" ? $description : null),
            $startDb,
            $endDb,
            $all_day,
            $type,
            $reminder_minutes,
            $event_id,
            $user_id
        ]);

        echo json_encode(["success" => true, "message" => "Updated"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // إنشاء
    $stmt = $pdo->prepare("
        INSERT INTO calendar_events
          (user_id, title, description, start_at, end_at, all_day, type, reminder_minutes)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $title,
        ($description !== "" ? $description : null),
        $startDb,
        $endDb,
        $all_day,
        $type,
        $reminder_minutes
    ]);

    echo json_encode(["success" => true, "message" => "Created", "event_id" => (int)$pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error: ".$e->getMessage()]);
}
