<?php
// File: public/pages/superadmin/payments/api/get_activity_log.php (Corrected Timezone Logic)

require_once realpath(__DIR__ . '/../../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
require_once ROOT_PATH . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);

header('Content-Type: application/json; charset=utf-8');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Pagination & Filter Logic ---
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;
$searchQuery = $_GET['search'] ?? '';

// --- Sorting Logic ---
$sortColumnMap = [
    'id'         => 'al.id',
    'created_at' => 'al.created_at',
    'username'   => 'u.username',
    'action'     => 'al.action'
];
$sortColumn = isset($_GET['sort']) && isset($sortColumnMap[$_GET['sort']]) ? $sortColumnMap[$_GET['sort']] : 'al.created_at';
$sortDirection = isset($_GET['dir']) && in_array(strtoupper($_GET['dir']), ['ASC', 'DESC']) ? strtoupper($_GET['dir']) : 'DESC';

// --- Build WHERE clause ---
$whereClauses = [];
$params = [];
if (!empty($searchQuery)) {
    $whereClauses[] = "(u.username LIKE :search OR al.action LIKE :search OR al.description LIKE :search OR al.ip_address LIKE :search)";
    $params[':search'] = "%{$searchQuery}%";
}
$whereSql = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// --- Total Items Query ---
$totalSql = "SELECT COUNT(al.id) FROM activity_log al LEFT JOIN users u ON al.user_id = u.id {$whereSql}";
$totalStmt = $pdo->prepare($totalSql);
$totalStmt->execute($params);
$totalItems = (int)$totalStmt->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// --- Main Data Query ---
$sql = "
    SELECT al.*, u.username
    FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.id
    {$whereSql}
    ORDER BY {$sortColumn} {$sortDirection}, al.id DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => &$val) { $stmt->bindParam($key, $val); }
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- ไม่ต้องแปลงเวลาแล้ว เพราะใน DB เป็นเวลาไทยอยู่แล้ว ---

// Return JSON response
echo json_encode([
    'logs' => $logs,
    'pagination' => [
        'page' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'limit' => $itemsPerPage
    ]
], JSON_UNESCAPED_UNICODE);