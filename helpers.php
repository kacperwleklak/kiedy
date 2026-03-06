<?php
// helpers.php

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
