<?php
require_once '../config.php';
require_once '../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request.');
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$start_time = $_POST['start_time'] ?? '08:00';
$end_time = $_POST['end_time'] ?? '20:00';
$datesJSON = $_POST['dates'] ?? '[]';

$dates = json_decode($datesJSON, true);

if (empty($title) || empty($dates) || !is_array($dates)) {
    die('Title and at least one date are required.');
}

// Basic time validation
if (strlen($start_time) === 5) $start_time .= ':00';
if (strlen($end_time) === 5) $end_time .= ':00';

if ($start_time >= $end_time) {
    die('Start time must be before end time.');
}

$pdo = getDB();
$calendar_id = generate_uuid();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO calendars (id, title, description, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$calendar_id, $title, $description, $start_time, $end_time]);

    $stmtDays = $pdo->prepare("INSERT INTO calendar_days (calendar_id, date_value) VALUES (?, ?)");
    foreach ($dates as $date) {
        $stmtDays->execute([$calendar_id, $date]);
    }

    $pdo->commit();
    
    // Redirect to the newly created calendar
    header("Location: ../calendar.php?id=" . urlencode($calendar_id));
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("create_calendar error: " . $e->getMessage());
    die("An unexpected error occurred. Please try again.");
}
