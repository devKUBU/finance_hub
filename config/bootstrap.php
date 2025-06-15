<?php
// config/bootstrap.php

// 1) ตรวจสอบ protocol และ host
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST']; // localhost หรือ nano-friend.com

// 2) หาตำแหน่ง “/public” ใน URL path
$scriptName = $_SERVER['SCRIPT_NAME']; 
$publicSeg  = '/public';
$pos        = strpos($scriptName, $publicSeg);

if ($pos !== false) {
    // ถ้ารันผ่าน http://localhost/nano-friend/public/...
    $basePath = substr($scriptName, 0, $pos + strlen($publicSeg));
} else {
    // ถ้ารันบน production ที่ DocumentRoot ชี้ไปที่ public
    $basePath = '';
}

// 3) รวมกันเป็น BASE_URL
$baseURL = rtrim($protocol . '://' . $host . $basePath, '/');

// 4) กำหนดคอนสแตนต์สำหรับ file include
define('ROOT_PATH', realpath(__DIR__ . '/..')); // ชี้ไปที่ nano-friend/