<?php
// public/api/toggle_theme.php

// Turn off error display (so warnings don't get injected into your JSON)
ini_set('display_errors',   0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Bootstrap + session
require_once __DIR__ . '/../../config/bootstrap.php';
if (session_status()===PHP_SESSION_NONE) session_start();

// Force JSON header
header('Content-Type: application/json');

// Authentication check
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error'=>'Not logged in']);
    exit;
}

// Validate input
$new = $_POST['theme'] ?? '';
if (!in_array($new, ['light','dark'], true)) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid theme']);
    exit;
}

// Perform update
require_once ROOT_PATH . '/config/db.php';
$stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
if ($stmt->execute([$new, $_SESSION['user']['id']])) {
    // Update session copy
    $_SESSION['user']['theme'] = $new;
    echo json_encode(['theme'=>$new]);
} else {
    http_response_code(500);
    echo json_encode(['error'=>'Database error']);
}