<?php
// -----------------------------------------------------------------------------
// File: public/pages/superadmin/payments/partials/delete_expense.php
// Description: Handles the deletion of an expense record and logs the activity.
// -----------------------------------------------------------------------------

// Set default timezone to Asia/Bangkok for consistent date/time handling.
date_default_timezone_set('Asia/Bangkok');

// Load core configurations and helper functions.
// Assumes bootstrap.php defines ROOT_PATH and initializes $baseURL
require_once realpath(__DIR__ . '/../../../../../config/bootstrap.php');
// Assumes helpers.php contains functions like requireRole() and logActivity()
require_once ROOT_PATH . '/includes/helpers.php'; 

// Start session if not already started and enforce user roles.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Ensure only users with 'superadmin' or 'admin' roles can access this script.
requireRole(['superadmin', 'admin']); 

// Include database connection.
// Assumes db.php initializes the $pdo object for database interaction.
require_once ROOT_PATH . '/config/db.php';
// Set PDO error mode to throw exceptions for better error handling.
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Set content type header for JSON response.
header('Content-Type: application/json; charset=utf-8');

// Initialize response array to be sent back as JSON.
$response = ['success' => false, 'error' => ''];

// Get the user ID from the session for logging purposes.
// Uses null coalescing operator to default to 0 if $_SESSION['user_id'] is not set.
// The @var DocBlock helps VS Code with type hinting.
/** @var int $userId */
$userId = $_SESSION['user_id'] ?? 0;

// Validate the expense ID received from the POST request.
$expenseId = isset($_POST['expense_id']) ? (int) $_POST['expense_id'] : 0;

if ($expenseId <= 0) {
    $response['error'] = 'ID ค่าใช้จ่ายไม่ถูกต้อง';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 1. Fetch expense details, contract number, and customer name BEFORE deletion.
    // This data is crucial for creating a meaningful activity log entry.
    // We JOIN with the 'contracts' table using 'contract_id' from 'expenses'.
    // The columns 'e.amount' and 'e.note' are from the 'expenses' table.
    // The columns 'c.contract_no_shop', 'c.customer_firstname', 'c.customer_lastname' are from 'contracts'.
    $stmt = $pdo->prepare("
        SELECT
            e.amount,
            e.note,
            c.contract_no_shop,
            c.customer_firstname,
            c.customer_lastname
        FROM
            expenses e
        JOIN
            contracts c ON e.contract_id = c.id
        WHERE
            e.id = ?
    ");
    $stmt->execute([$expenseId]);
    $expenseDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no expense record is found with the given ID, treat it as a successful operation
    // (nothing to delete) and log the attempt if logging is available.
    if (!$expenseDetails) {
        $response['success'] = true;
        try {
            if (function_exists('logActivity') && $userId) {
                logActivity($pdo, $userId, 'expense_deletion_attempt', 'expense', $expenseId, "พยายามลบค่าใช้จ่าย ID: {$expenseId} ซึ่งไม่พบในระบบ");
            }
        } catch (PDOException $log_e) {
            error_log("Error logging non-existent expense deletion attempt: " . $log_e->getMessage());
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Combine customer first name and last name into a single string.
    $customerName = trim($expenseDetails['customer_firstname'] . ' ' . $expenseDetails['customer_lastname']);
    // Use 'contract_no_shop' as the contract number; provide a fallback if it's empty.
    $contractNo = $expenseDetails['contract_no_shop'] ?: 'ไม่มีเลขที่สัญญา'; 

    // 2. Perform the deletion of the expense record from the 'expenses' table.
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([$expenseId]);

    // 3. Log the activity after successful deletion.
    try {
        if (function_exists('logActivity') && $userId) {
            // Construct a detailed description for the activity log.
            // It includes the expense ID, contract number, customer name, amount, and note.
            $logDescription = sprintf(
                "ลบค่าใช้จ่าย ID: %d, สัญญา: %s (%s), จำนวน: %s ฿, หมายเหตุ: \"%s\"",
                $expenseId,
                htmlspecialchars($contractNo), // Sanitize contract number for display in log.
                htmlspecialchars($customerName), // Sanitize customer name.
                number_format($expenseDetails['amount'], 2), // Format amount as currency.
                htmlspecialchars($expenseDetails['note'] ?? 'ไม่มีหมายเหตุ') // Sanitize note; provide fallback.
            );
            
            // Call the logActivity function with all required parameters.
            logActivity($pdo, $userId, 'expense_deletion', 'expense', $expenseId, $logDescription);
        }
    } catch (PDOException $log_e) {
        // Log any errors specific to the logActivity function, but don't prevent the main deletion.
        error_log("Error logging expense deletion (ID: $expenseId): " . $log_e->getMessage());
    }

    // If everything is successful, set 'success' to true and return the JSON response.
    $response['success'] = true;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    // Catch any PDO (database) exceptions during the main deletion process.
    // Set 'success' to false and provide an error message.
    $response['error'] = 'เกิดข้อผิดพลาดในการลบค่าใช้จ่าย: ' . $e->getMessage();
    // Log the full database error to the server's error log for debugging.
    error_log("Database Error deleting expense ID $expenseId: " . $e->getMessage());
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}