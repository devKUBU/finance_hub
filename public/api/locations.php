<?php
header('Content-Type: application/json; charset=utf-8');
require_once realpath(__DIR__ . '/../../../config/bootstrap.php');
require_once ROOT_PATH . '/config/db.php';

$level = $_GET['level'] ?? '';
$id    = (int)($_GET[$level==='amphur'? 'province' : ($level==='district'?'amphur':'')] ?? 0);

switch($level) {
  case 'province':
    $stmt = $pdo->query("SELECT id,name FROM provinces ORDER BY name");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    break;
  case 'amphur':
    $stmt = $pdo->prepare("SELECT id,name FROM amphures WHERE province_id=? ORDER BY name");
    $stmt->execute([$id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    break;
  case 'district':
    $stmt = $pdo->prepare("SELECT id,name FROM districts WHERE amphure_id=? ORDER BY name");
    $stmt->execute([$id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    break;
  default:
    $data = [];
}
echo json_encode($data);