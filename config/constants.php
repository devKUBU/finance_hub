<?php
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$projectBase = str_replace('/login.php', '', $scriptName); // login.php คือไฟล์ที่รันจริง

define('BASE_URL', $projectBase);