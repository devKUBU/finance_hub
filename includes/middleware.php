<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/constants.php';


function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

function requireRole($role) {
    if ($_SESSION['user']['role'] !== $role) {
        http_response_code(403);
        exit('Permission Denied');
    }
}
