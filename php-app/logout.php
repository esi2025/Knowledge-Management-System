<?php
/**
 * سامانه مدیریت دانش فنی کارگاهی - خروج امن از سیستم
 */
require_once __DIR__ . '/config.php';

session_unset();
session_destroy();

header("Location: login.php");
exit();
