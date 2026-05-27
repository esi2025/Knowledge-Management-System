<?php
/**
 * سامانه مدیریت دانش فنی کارگاهی - فایل تنظیمات
 * پایگاه داده محلی (XAMPP / WAMP)
 */

// ۱. تنظیم هدر برای پشتیبانی کامل از UTF-8 فارسی و یونیکد
header('Content-Type: text/html; charset=utf-8');

// ۲. فرآیند همگام‌سازی خطاهای احتمالی در سرورهای محلی
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ۳. تعریف ثوابت اتصال به دیتابیس
define('DB_HOST', 'localhost');
define('DB_NAME', 'civil_knowledge_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // در سیستم‌های محلی معمولاً خالی است

// ۴. مسیرهای آپلود فایل‌ها مقید به دایرکتوری لوکال
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // ۵۰ مگابایت حداکثر مجاز طبق صورت‌مسئله

// ۵. ساخت فولدر آپلود در صورت عدم وجود به همراه مجوز دسترسی مناسب
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// ۶. اتصال امن به پایگاه‌داده با PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_persian_ci"
        )
    );
} catch (PDOException $e) {
    // نمایش خطای شیک و فارسی در صورت عدم اتصال دیتابیس در محیط آفلاین
    die("<div style='direction: rtl; font-family: Tahoma, Arial; text-align: center; margin-top: 50px; padding: 20px; border: 1px solid #f5c6cb; background-color: #f8d7da; color: #721c24; border-radius: 8px;'>
            <h2>خطا در اتصال به پایگاه‌داده محلی!</h2>
            <p>لطفاً مطمئن شوید که سرور MySQL در کنترل‌پنل XAMPP یا WAMP بر روی رایانه شما در حالت اجرا (Start) قرار دارد.</p>
            <p style='font-size: 13px; color: #555;'>جزئیات اتصال: " . htmlspecialchars($e->getMessage()) . "</p>
         </div>");
}

// ۷. راه‌اندازی سشن امن با طول عمر معین (۳۰ دقیقه بیکاری)
if (session_status() === PHP_SESSION_NONE) {
    // تنظیم عمر سشن روی ۱۸۰۰ ثانیه (۳۰ دقیقه)
    ini_set('session.gc_maxlifetime', 1800);
    session_set_cookie_params(1800);
    session_start();
}

// مدیریت انقضای جلسه پس از ۳۰ دقیقه فعالیت نکردن کاربر
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time(); // به‌روزرسانی زمان فعالیت
