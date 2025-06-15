<?php
// File: includes/helpers.php
// ฟังก์ชันใหม่สำหรับแสดงหน้า Access Denied
if (! function_exists('displayAccessDenied')) {
    function displayAccessDenied(string $message = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้'): void {
        global $baseURL; // เรียกใช้ตัวแปร $baseURL ที่ถูกกำหนดใน bootstrap.php
        
        // กำหนด HTTP Status Code เป็น 403 Forbidden
        header('HTTP/1.1 403 Forbidden');

        // ใช้ Heredoc Syntax ในการสร้างหน้า HTML ทั้งหน้า
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="th" data-bs-theme="light">
        <head>
            <meta charset="UTF-8">
            <title>Access Denied</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
            <style>
                body {
                    background-color: #f4f6f9;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    font-family: 'Prompt', sans-serif;
                }
                .card {
                    max-width: 500px;
                    border: none;
                    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
                }
                .card-header {
                    background-color: #dc3545;
                    color: white;
                    text-align: center;
                    border-bottom: none;
                    padding: 1.5rem;
                }
                .card-header .fa-solid {
                    font-size: 3rem;
                }
                .card-body {
                    padding: 2rem;
                }
                .card-footer {
                    background-color: transparent;
                    border-top: none;
                    padding: 1.5rem;
                }
            </style>
        </head>
        <body>
            <div class="card text-center">
                <div class="card-header">
                    <i class="fa-solid fa-lock mb-3"></i>
                    <h4 class="modal-title">Access Denied</h4>
                </div>
                <div class="card-body">
                    <h5 class="card-title">ไม่สามารถเข้าถึงได้</h5>
                    <p class="card-text text-muted">{$message}</p>
                </div>
                <div class="card-footer">
                    <a href="{$baseURL}/login.php" class="btn btn-primary">
                        <i class="fa-solid fa-arrow-left me-1"></i> กลับหน้าหลัก
                    </a>
                </div>
            </div>
        </body>
        </html>
HTML;
        exit; // หยุดการทำงานของสคริปต์หลังจากแสดงหน้า
    }
}

// 1) ตรวจสอบสิทธิ์การเข้าถึงตาม role
if (! function_exists('requireRole')) {
    function requireRole(array $allowedRoles): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['user'])
            || ! in_array($_SESSION['user']['role'], $allowedRoles, true)
        ) {
            // เรียกใช้ฟังก์ชันใหม่ที่เราสร้างขึ้น
            displayAccessDenied(); 
        }
    }
}

// 2) บันทึกกิจกรรมลงตาราง activity_log
if (! function_exists('logActivity')) {
    function logActivity(
        PDO $pdo,
        int $userId,
        string $action,
        ?string $targetType,
        ?int $targetId,
        string $description
    ): void {
        $targetType = $targetType ?? 'system';
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $pdo->prepare("
            INSERT INTO activity_log
              (user_id, action, target_type, target_id, description, ip_address)
            VALUES
              (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $action,
            $targetType,
            $targetId,
            $description,
            $ip
        ]);
    }
}

// 3) Flash message (one-time)
if (! function_exists('setFlash')) {
    /**
     * เก็บข้อความ flash แบบ one-time ลงใน session
     * @param string $key   ชื่อคีย์ เช่น 'success','error','info','warning'
     * @param string $msg   ข้อความ
     */
    function setFlash(string $key, string $msg): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['flash'][$key] = $msg;
    }
}

if (! function_exists('getFlash')) {
    /**
     * ดึงข้อความ flash แล้วลบออกจาก session ทันที
     * @param string $key
     * @return string|null
     */
    function getFlash(string $key): ?string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (isset($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }
        return null;
    }
}

if (! function_exists('displayFlash')) {
    /**
     * แสดง flash message ชนิดต่างๆ (success, error, info, warning)
     */
    function displayFlash(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        foreach (['success','error','info','warning'] as $type) {
            if ($msg = getFlash($type)) {
                // แปลง error → danger เพื่อ Bootstrap
                $bsType = $type === 'error' ? 'danger' : $type;
                echo "<div class='alert alert-{$bsType} alert-dismissible fade show' role='alert'>
                        {$msg}
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                      </div>";
            }
        }
    }
}

// 4) ฟังก์ชั่นแปลงวันที่เป็นภาษาไทย (เช่น 20 พ.ค. 2568)
if (! function_exists('dateThai')) {
    function dateThai(string $date, bool $showTime = false): string {
        $dt = new DateTime($date, new DateTimeZone('Asia/Bangkok'));
        $thaiM = [
            '', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
            'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'
        ];
        $d = $dt->format('j').' '.$thaiM[(int)$dt->format('n')].
             ' '.($dt->format('Y') + 543);
        if ($showTime) {
            $d .= ' '.$dt->format('H:i');
        }
        return $d;
    }
}

// 5) ฟังก์ชั่นแปลงวันที่หน้า Admin (dd/mm/YYYY HH:MM:SS)
if (! function_exists('formatThaiDateTime')) {
    function formatThaiDateTime(string $dt): string {
        $ts = strtotime($dt);
        $day   = date('d', $ts);
        $mon   = date('m', $ts);
        $year  = date('Y', $ts) + 543;
        $time  = date('H:i:s', $ts);
        return "{$day}/{$mon}/{$year} {$time}";
    }
}

// 6) สร้าง badge พร้อมคลาส
if (! function_exists('renderBadge')) {
    function renderBadge(string $text, string $cls): string {
        return "<span class='{$cls} px-2 py-1 rounded'>{$text}</span>";
    }
}

// 7) สร้างปุ่ม Action สำหรับหน้า contracts
if (! function_exists('renderContractActions')) {
    function renderContractActions(array $c): string {
        $id = htmlspecialchars($c['id'], ENT_QUOTES, 'UTF-8');
        if ($c['approval_status'] === 'pending') {
            return "
                <button class='btn btn-sm btn-success me-1'
                    data-bs-toggle='modal'
                    data-bs-target='#approveModal-{$id}'>อนุมัติ</button>
                <button class='btn btn-sm btn-danger'
                    data-bs-toggle='modal'
                    data-bs-target='#rejectModal-{$id}'>ปฏิเสธ</button>
            ";
        }

        if ($c['approval_status'] === 'approved'
            && $c['commission_status'] === 'commission_pending'
        ) {
            return "
                <button class='btn btn-sm btn-primary'
                    data-bs-toggle='modal'
                    data-bs-target='#commissionModal-{$id}'>ตั้งคอมมิชชั่น</button>
            ";
        }

        return "<span class='text-muted'>—</span>";
    }
}

// Permission สำหรับแอดมินในการดูหน้าต่างๆ

if (!function_exists('hasPermission')) {
    function hasPermission(PDO $pdo, int $userId, string $key): bool {
        // ดึง role และ permissions
        $stmt = $pdo->prepare("SELECT role, permissions FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return false;

        // ✅ ถ้าเป็น superadmin ให้ผ่านเสมอ
        if ($row['role'] === 'superadmin') {
            return true;
        }

        // ตรวจสอบ permission จาก JSON
        $json = $row['permissions'];
        if (!$json) return false;

        $data = json_decode($json, true);
        if (!is_array($data)) return false;

        return in_array($key, $data);
    }
}