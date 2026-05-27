<?php
/**
 * سامانه مدیریت دانش فنی کارگاهی - توابع کلیدی و عمومی (امنیت، تقویم جلالی، مدیریت فایل)
 */

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// ۱. ضدعفونی کردن ورودی‌ها جهت جلوگیری از حملات XSS
function h($string) {
    if ($string === null) return '';
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

// ۲. بررسی ورود کاربر و احراز هویت
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// ۳. بررسی نقش کاربر جهت سطوح دسترسی گوناگون
function check_role($allowed_roles) {
    require_login();
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], (array)$allowed_roles)) {
        die("<div style='direction: rtl; font-family: Tahoma, Arial; text-align: center; margin-top: 50px; padding: 20px; border: 1px solid #ffeeba; background-color: #fff3cd; color: #856404; border-radius: 8px;'>
                <h2>سطح دسترسی شما غیرمجاز است!</h2>
                <p>شما دسترسی لازم برای مشاهده یا ویرایش این بخش را ندارید.</p>
                <a href='index.php' style='display: inline-block; margin-top: 15px; color: #1b5e20; text-decoration: none; font-weight: bold;'>بازگشت به صفحه اصلی</a>
             </div>");
    }
}

// ۴. دریافت نام نقش کاربر به فارسی روان
function get_role_name($role) {
    switch ($role) {
        case 'admin': return 'مدیر سیستم (دفتر فنی)';
        case 'expert': return 'کارشناس ارشد (مشاور فنی)';
        case 'contributor': return 'ثبت‌کننده (مهندس کارگاه / ناظر)';
        default: return 'ناشناس';
    }
}

// ۵. دریافت برچسب اولویت سوال به فارسی به همراه کلاس رنگی مربوطه
function get_priority_html($priority) {
    switch ($priority) {
        case 'critical':
            return '<span class="status-badge priority-critical" style="background-color: #ffebee; color: #c62828; border: 1px solid #ef9a9a; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px;">بحرانی 🚨</span>';
        case 'urgent':
            return '<span class="status-badge priority-urgent" style="background-color: #fff3e0; color: #ef6c00; border: 1px solid #ffcc80; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px;">فوری ⚠️</span>';
        default:
            return '<span class="status-badge priority-normal" style="background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; padding: 2px 8px; border-radius: 4px; font-size: 11px;">عادی</span>';
    }
}

// ۶. دریافت وضعیت تاییدیه پست‌های دانش
function get_status_html($status) {
    switch ($status) {
        case 'approved':
            return '<span class="badge" style="background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; padding: 2px 10px; border-radius: 20px; font-size: 12px;">تایید شده (منتشر شده)</span>';
        case 'rejected':
            return '<span class="badge" style="background-color: #ffebee; color: #c62828; border: 1px solid #ef9a9a; padding: 2px 10px; border-radius: 20px; font-size: 12px;">رد شده</span>';
        default:
            return '<span class="badge" style="background-color: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; padding: 2px 10px; border-radius: 20px; font-size: 12px;">پیش‌نویس (در انتظار تایید)</span>';
    }
}

// ۷. تابع مبدل تاریخ میلادی به هجری شمسی (جلالی) بدون نیاز به افزونه‌های خارجی (کاملاً آفلاین)
function to_jalali($g_date_string) {
    if (empty($g_date_string)) return 'نامشخص';
    
    $timestamp = strtotime($g_date_string);
    if (!$timestamp) return 'نامشخص';

    $g_y = (int)date('Y', $timestamp);
    $g_m = (int)date('m', $timestamp);
    $g_d = (int)date('d', $timestamp);

    $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);

    // بررسی سال کبیسه میلادی
    if (($g_y % 4 === 0 && $g_y % 100 !== 0) || ($g_y % 400 === 0)) {
        $g_days_in_month[1] = 29;
    }

    $gy = $g_y - 1600;
    $gm = $g_m - 1;
    $gd = $g_d - 1;

    $g_day_no = 365 * $gy + floor(($gy + 3) / 4) - floor(($gy + 99) / 100) + floor(($gy + 399) / 400);

    for ($i = 0; $i < $gm; ++$i) {
        $g_day_no += $g_days_in_month[$i];
    }
    $g_day_no += $gd;

    $j_day_no = $g_day_no - 79;

    $j_np = floor($j_day_no / 12053);
    $j_day_no %= 12053;

    $jy = 979 + 33 * $j_np + 4 * floor($j_day_no / 1461);
    $j_day_no %= 1461;

    if ($j_day_no >= 366) {
        $jy += floor(($j_day_no - 1) / 365);
        $j_day_no = ($j_day_no - 1) % 365;
    }

    for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i) {
        $j_day_no -= $j_days_in_month[$i];
    }
    
    $jm = $i + 1;
    $jd = $j_day_no + 1;

    // افزودن صفر قبل از اعداد تک‌رقمی
    $jm_str = $jm < 10 ? '۰' . $jm : convert_en_to_fa_digits($jm);
    $jd_str = $jd < 10 ? '۰' . $jd : convert_en_to_fa_digits($jd);
    $jy_str = convert_en_to_fa_digits($jy);

    return $jy_str . '/' . $jm_str . '/' . $jd_str;
}

// مبدل اعداد انگلیسی به فارسی برای زیبایی فونت
function convert_en_to_fa_digits($string) {
    if (empty($string)) return '۰';
    $en = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    $fa = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    return str_replace($en, $fa, (string)$string);
}

// ۸. قالب‌بندی نمایش سایز فایل به کیلوبایت یا مگابایت
function format_bytes($bytes, $precision = 1) {
    if ($bytes <= 0) return '۰ بایت';
    $units = array('بایت', 'کلوبایت', 'مگابایت');
    $pow = floor(log($bytes) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return convert_en_to_fa_digits(round($bytes, $precision)) . ' ' . $units[$pow];
}

// ۹. ساخت خودکار پیش‌نمایش تصویر (Thumbnail) برای تصاویر با کتابخانه GD
function create_image_thumbnail($source_file, $target_file, $max_width = 300, $max_height = 200) {
    if (!extension_loaded('gd')) {
        // اگر افزونه GD روی وب‌سرور آفلاین فعال نباشد، همان تصویر اصلی استفاده می‌شود
        return false;
    }

    $info = getimagesize($source_file);
    if ($info === false) return false;

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg':
            $source_image = imagecreatefromjpeg($source_file);
            break;
        case 'image/png':
            $source_image = imagecreatefrompng($source_file);
            break;
        case 'image/gif':
            $source_image = imagecreatefromgif($source_file);
            break;
        default:
            return false;
    }

    if (!$source_image) return false;

    $width = $info[0];
    $height = $info[1];

    // محاسبه نسبت ابعاد تصویر جهت حفظ قرینه‌سازی
    $ratio = $width / $height;
    if ($max_width / $max_height > $ratio) {
        $new_width = $max_height * $ratio;
        $new_height = $max_height;
    } else {
        $new_width = $max_width;
        $new_height = $max_width / $ratio;
    }

    $thumb = imagecreatetruecolor($new_width, $new_height);

    // حفظ ترنسپرنسی برای تصاویر PNG و GIF
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }

    imagecopyresampled($thumb, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    $success = false;
    switch ($mime) {
        case 'image/jpeg':
            $success = imagejpeg($thumb, $target_file, 85);
            break;
        case 'image/png':
            $success = imagepng($thumb, $target_file);
            break;
        case 'image/gif':
            $success = imagegif($thumb, $target_file);
            break;
    }

    // پاکسازی منابع حافظه رم
    imagedestroy($source_image);
    imagedestroy($thumb);

    return $success;
}

// ۱۰. ساخت ساختار پوشه فرعی و ذخیره‌سازی فایل‌های آپلود شده
function handle_file_upload($file_array, $user_id) {
    if (!isset($file_array) || $file_array['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    if ($file_array['size'] > MAX_FILE_SIZE) {
        throw new Exception("حجم فایل " . $file_array['name'] . " فراتر از ظرفیت ۵۰ مگابایت است!");
    }

    $extension = strtolower(pathinfo($file_array['name'], PATHINFO_EXTENSION));
    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'm4a');
    if (!in_array($extension, $allowed_extensions)) {
        throw new Exception("فرمت فایل انتخاب شده غیرمجاز است! پسوند مجاز: JPG, PNG, MP4, MP3, M4A");
    }

    // ساخت زیرشاخه‌های سال و ماه تفکیک‌شده (مثل uploads/1405/03/) جهت جلوگیری از تراکم فایل در ۱ پوشه
    $year_folder = date('Y');
    $month_folder = date('m');
    $sub_dir = $year_folder . '/' . $month_folder . '/';
    $target_dir = UPLOAD_DIR . $sub_dir;

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // تولید نام رندوم و پیشگیری از تصادم همنام
    $unique_name = time() . '_' . $user_id . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $dest_path = $target_dir . $unique_name;

    if (move_uploaded_file($file_array['tmp_name'], $dest_path)) {
        // بررسی نوع میم جهت ساخت تصویر شاخص
        $relative_file_path = 'uploads/' . $sub_dir . $unique_name;
        $relative_thumb_path = null;

        $mime_type = mime_content_type($dest_path);
        if (str_starts_with($mime_type, 'image/')) {
            $thumb_name = 'thumb_' . $unique_name;
            $thumb_dest_path = $target_dir . $thumb_name;
            
            if (create_image_thumbnail($dest_path, $thumb_dest_path, 320, 240)) {
                $relative_thumb_path = 'uploads/' . $sub_dir . $thumb_name;
            } else {
                $relative_thumb_path = $relative_file_path; // اگر تبدیل نشد، مرجع اصلی به عنوان تامب‌نیل خواهد بود
            }
        }

        return [
            'file_path' => $relative_file_path,
            'thumbnail_path' => $relative_thumb_path,
            'file_type' => $mime_type,
            'file_size' => $file_array['size']
        ];
    }

    return false;
}
