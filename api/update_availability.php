<?php
require_once '../config.php';
require_once '../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Invalid request'], 400);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$calendar_day_id = $input['calendar_day_id'] ?? null;
$time_slot = $input['time_slot'] ?? null;
// Status can be 'available', 'maybe', or empty for delete
$status = $input['status'] ?? '';

$allowed_statuses = ['available', 'maybe', ''];

if (!$calendar_day_id || !$time_slot) {
    json_response(['error' => 'Missing day or time slot.'], 400);
}

if (!in_array($status, $allowed_statuses, true)) {
    json_response(['error' => 'Invalid status value.'], 400);
}

try {
    $pdo = getDB();
    $user_id = get_current_user_id($pdo);

    // Verify the calendar_day_id actually exists (prevents blind writes to arbitrary IDs)
    $stmtCheck = $pdo->prepare("SELECT id FROM calendar_days WHERE id = ?");
    $stmtCheck->execute([$calendar_day_id]);
    if (!$stmtCheck->fetch()) {
        json_response(['error' => 'Invalid calendar day.'], 404);
    }
    
    if (empty($status)) {
        // Delete if no status
        $stmt = $pdo->prepare("DELETE FROM availabilities WHERE calendar_day_id = ? AND time_slot = ? AND user_id = ?");
        $stmt->execute([$calendar_day_id, $time_slot, $user_id]);
    } else {
        // Upsert
        $stmt = $pdo->prepare("INSERT INTO availabilities (calendar_day_id, user_id, time_slot, status) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE status = ?");
        $stmt->execute([$calendar_day_id, $user_id, $time_slot, $status, $status]);
    }
    
    json_response(['success' => true]);
} catch (Exception $e) {
    error_log("update_availability error: " . $e->getMessage());
    json_response(['error' => 'An unexpected error occurred.'], 500);
}
