<?php
// helpers.php
require_once 'config.php';

// Helper function to generate a V4 UUID
function generate_uuid() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant RFC 4122
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Function to handle JSON response
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Get or create user based on cookie
function get_current_user_id($pdo) {
    $cookie_name = 'kiedy_user_id';
    
    if (isset($_COOKIE[$cookie_name])) {
        $cookie_hash = $_COOKIE[$cookie_name];
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE cookie_hash = ?");
        $stmt->execute([$cookie_hash]);
        $user = $stmt->fetch();
        
        if ($user) {
            return $user['id'];
        }
    }
    
    // Create new user
    $user_id = generate_uuid();
    $cookie_hash = bin2hex(random_bytes(32)); // Secure random hash for cookie
    
    $stmt = $pdo->prepare("INSERT INTO users (id, cookie_hash) VALUES (?, ?)");
    $stmt->execute([$user_id, $cookie_hash]);
    
    // Set cookie for 10 years (HttpOnly + SameSite to protect from XSS/CSRF)
    setcookie($cookie_name, $cookie_hash, [
        'expires'  => time() + (10 * 365 * 24 * 60 * 60),
        'secure'   => true,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    
    return $user_id;
}

// Check if user is verified
function is_user_verified($pdo, $user_id) {
    if (!$user_id) return false;
    $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return (bool)$stmt->fetchColumn();
}

// Set user as verified
function set_user_verified($pdo, $user_id) {
    $stmt = $pdo->prepare("UPDATE users SET is_verified = TRUE WHERE id = ?");
    return $stmt->execute([$user_id]);
}

// Validate Cloudflare Turnstile token
function validate_turnstile($token) {
    if (empty($token)) return false;
    if (empty(TURNSTILE_SECRET_KEY)) return true; // Skip if no key (dev mode)

    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = [
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $token
    ];

    $remoteip = $_SERVER['HTTP_CF_CONNECTING_IP'] ??
                $_SERVER['HTTP_X_FORWARDED_FOR'] ??
                $_SERVER['REMOTE_ADDR'];

    if ($remoteip) {
        $data['remoteip'] = $remoteip;
    }

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) return false;

    $response = json_decode($result, true);
    return !empty($response['success']);
}
