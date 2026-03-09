<?php
require_once '../config.php';
require_once '../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Invalid request'], 400);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$name = trim($input['name'] ?? '');
$captchaToken = $input['captcha_token'] ?? '';

$pdo = getDB();
$user_id = get_current_user_id($pdo);

// If user is already verified, we can skip captcha (though normally they'd only see the modal if not)
if (!is_user_verified($pdo, $user_id)) {
    if (!validate_turnstile($captchaToken)) {
        json_response(['error' => 'CAPTCHA validation failed'], 400);
    }
    set_user_verified($pdo, $user_id);
}

if (mb_strlen($name) > 50) {
    json_response(['error' => 'Name is too long'], 400);
}

if (empty($name)) {
    json_response(['error' => 'Name is required'], 400);
}

try {
    // $pdo and $user_id already initialized above
    
    $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
    $stmt->execute([$name, $user_id]);
    
    json_response(['success' => true]);
} catch (Exception $e) {
    json_response(['error' => 'An error occurred'], 500);
}
