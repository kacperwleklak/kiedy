<?php
// helpers.php

// Helper function to generate a V4 UUID
function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
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
    
    // Set cookie for 10 years
    setcookie($cookie_name, $cookie_hash, time() + (10 * 365 * 24 * 60 * 60), '/');
    
    return $user_id;
}
