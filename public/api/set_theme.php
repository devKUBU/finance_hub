<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error'=>'not_logged_in']);
    exit;
}

$theme = $_POST['theme'] ?? '';
if (!in_array($theme, ['dark','light'], true)) {
    http_response_code(400);
    echo json_encode(['error'=>'bad_theme']);
    exit;
}

require __DIR__.'/../../config/db.php';
$upd = $pdo->prepare("UPDATE users SET theme=? WHERE id=?");
$upd->execute([$theme, $_SESSION['user']['id']]);

// อัปเดตใน session ด้วย
$_SESSION['user']['theme'] = $theme;

echo json_encode(['ok'=>true]);