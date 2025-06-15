<?php
// File: app/Mail/Mailer.php

namespace App\Mail;

// โหลด Composer autoload (จะดึง PHPMailer, PSR/Log มาให้โดยอัตโนมัติ)
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    /**
     * ส่งอีเมลรีเซ็ตรหัสผ่าน
     *
     * @param string $toEmail  อีเมลผู้รับ
     * @param string $toName   ชื่อผู้รับ
     * @param string $token    โทเค็นสำหรับรีเซ็ต
     * @return bool            true ถ้าส่งสำเร็จ, false ถ้าเกิดข้อผิดพลาด
     */
    public static function sendResetPassword(string $toEmail, string $toName, string $token): bool
    {
        // 1) สร้าง URL สำหรับ reset (ปรับให้ตรงกับโครงสร้างโปรเจกต์ของคุณ)
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = '/nano-friend/public';               // ถ้าใช้ virtual host ให้ปรับเป็น '';
        $tokenUrl = "https://{$host}{$basePath}/reset_password.php?token=" . urlencode($token);

        // 2) กำหนด path ไปยัง template และ CSS
        $baseAssets = dirname(__DIR__, 2) . '/public/assets/mail';
        $cssPath    = "{$baseAssets}/css/email.css";
        $tplPath    = "{$baseAssets}/templates/reset_password.php";

        // 3) เตรียม CSS ฝังใน <style>
        $cssContent = is_file($cssPath)
            ? file_get_contents($cssPath)
            : '';

        // 4) สร้างตัวแปรให้ template ใช้งาน
        ob_start();
        // ในไฟล์ reset_password.php ให้ใช้ตัวแปร $toName, $tokenUrl, $cssContent
        include $tplPath;
        $htmlBody = ob_get_clean();

        // 5) ตั้งค่า PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'romihave@gmail.com';      // แก้เป็นอีเมลจริง
            $mail->Password   = 'sasm khqa sakd zqfs';   // แอปพาสเวิร์ดจาก Google
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            // Sender & Recipient
            $mail->setFrom('notify_noreply@nano-friend.com', 'Nano Friend Support');
            $mail->addAddress($toEmail, $toName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'รีเซ็ตรหัสผ่าน Nano Friend';
            $mail->Body    = $htmlBody;

            return $mail->send();
        } catch (Exception $e) {
            error_log('Mailer Exception: ' . $e->getMessage());
            error_log('Mailer ErrorInfo: ' . $mail->ErrorInfo);
            return false;
        }
    }
}