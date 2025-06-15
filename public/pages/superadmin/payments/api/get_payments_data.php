<?php
// File: public/pages/superadmin/payments/api/get_payments_data.php (Added Sorting)

require_once realpath(__DIR__ . '/../../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
require_once ROOT_PATH . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);

header('Content-Type: application/json; charset=utf-8');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Pagination & Filter Logic ---
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 25;
$offset = ($page - 1) * $itemsPerPage;
$searchQuery = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';

// --- Sorting Logic ---
$sortColumnMap = [
    'contract_no'   => 'c.contract_no_shop',
    'customer_name' => 'customer_name',
    'due_date'      => 'next_due_date'
];
$defaultSort = "ORDER BY CASE WHEN pn.status = 'pending' AND DATE(pn.due_date) < CURDATE() THEN 1 WHEN pn.status = 'pending' AND DATE(pn.due_date) = CURDATE() THEN 2 WHEN pn.status = 'pending' THEN 3 ELSE 4 END, COALESCE(pn.due_date, pp.due_date) ASC";
$sortColumn = isset($_GET['sort']) && isset($sortColumnMap[$_GET['sort']]) ? $sortColumnMap[$_GET['sort']] : null;
$sortDirection = isset($_GET['dir']) && in_array(strtoupper($_GET['dir']), ['ASC', 'DESC']) ? strtoupper($_GET['dir']) : 'ASC';
$orderBySql = $sortColumn ? "ORDER BY {$sortColumn} {$sortDirection}, c.id DESC" : $defaultSort;

// --- Build WHERE clause ---
$whereClauses = ["c.approval_status = 'approved'"];
$params = [];
if (!empty($searchQuery)) {
    $whereClauses[] = "(c.contract_no_shop LIKE :search OR CONCAT(c.customer_firstname, ' ', c.customer_lastname) LIKE :search)";
    $params[':search'] = "%{$searchQuery}%";
}
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$next7Days = date('Y-m-d', strtotime('+7 days'));
switch ($statusFilter) {
    case 'overdue': $whereClauses[] = "EXISTS (SELECT 1 FROM payments p WHERE p.contract_id = c.id AND p.status = 'pending' AND DATE(p.due_date) < :today)"; $params[':today'] = $today; break;
    case 'today': $whereClauses[] = "EXISTS (SELECT 1 FROM payments p WHERE p.contract_id = c.id AND p.status = 'pending' AND DATE(p.due_date) = :today)"; $params[':today'] = $today; break;
    case 'tomorrow': $whereClauses[] = "EXISTS (SELECT 1 FROM payments p WHERE p.contract_id = c.id AND p.status = 'pending' AND DATE(p.due_date) = :tomorrow)"; $params[':tomorrow'] = $tomorrow; break;
    case 'next7': $whereClauses[] = "EXISTS (SELECT 1 FROM payments p WHERE p.contract_id = c.id AND p.status = 'pending' AND DATE(p.due_date) BETWEEN :tomorrow AND :next7days)"; $params[':tomorrow'] = $tomorrow; $params[':next7days'] = $next7Days; break;
    case 'closed': $whereClauses[] = "(SELECT COALESCE(SUM(p.amount_due), 0) FROM payments p WHERE p.contract_id = c.id) <= (SELECT COALESCE(SUM(p2.amount_paid), 0) FROM payments p2 WHERE p2.contract_id = c.id)"; break;
}
$whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

// --- Total Items Query ---
$totalSql = "SELECT COUNT(DISTINCT c.id) FROM contracts c LEFT JOIN payments pn ON pn.id = (SELECT p.id FROM payments p WHERE p.contract_id = c.id AND p.status = 'pending' LIMIT 1) {$whereSql}";
$totalStmt = $pdo->prepare($totalSql);
$totalStmt->execute($params);
$totalItems = (int)$totalStmt->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// --- Main Data Query ---
$sql = "
SELECT
  c.id AS contract_id, c.contract_no_shop AS contract_no,
  CONCAT(c.customer_firstname,' ',c.customer_lastname) AS customer_name,
  pn.id as next_payment_id, pn.amount_paid as next_amount_paid_for_this_installment,
  COALESCE(pn.pay_no, pp.pay_no) AS next_pay_no,
  DATE(COALESCE(pn.due_date, pp.due_date)) AS next_due_date,
  COALESCE(pn.amount_due, pp.amount_due) AS next_amount_due,
  (SELECT SUM(p3.amount_paid) FROM payments p3 WHERE p3.contract_id = c.id) as total_paid_amount,
  (SELECT SUM(p4.amount_due) FROM payments p4 WHERE p4.contract_id = c.id) as total_due_amount,
  (SELECT COUNT(*) FROM payments p5 WHERE p5.contract_id = c.id) as total_installments,
  CASE WHEN pn.id IS NOT NULL THEN pn.status ELSE pp.status END AS next_status
FROM contracts c
LEFT JOIN payments pn ON pn.id = (SELECT p.id FROM payments p WHERE p.contract_id = c.id AND p.status = 'pending' ORDER BY (DATE(p.due_date) < CURDATE()) DESC, p.due_date ASC LIMIT 1)
LEFT JOIN payments pp ON pp.id = (SELECT p2.id FROM payments p2 WHERE p2.contract_id = c.id AND p2.status = 'paid' ORDER BY p2.pay_no DESC LIMIT 1)
{$whereSql}
{$orderBySql}
LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => &$val) { $stmt->bindParam($key, $val); }
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['contracts' => $contracts, 'pagination' => ['page' => $page, 'totalPages' => $totalPages, 'totalItems' => $totalItems]]);