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

if (!$calendar_day_id || !$time_slot) {
    json_response(['error' => 'Missing day or time slot.'], 400);
}

try {
    $pdo = getDB();
    $user_id = get_current_user_id($pdo);
    
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
    json_response(['error' => 'An error occurred: ' . $e->getMessage()], 500);
}
