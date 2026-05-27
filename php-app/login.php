<?php
/**
 * سامانه مدیریت دانش فنی کارگاهی - صفحه ورود امن و منحصربفرد کاربران (آفلاین)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// اگر کاربر وارد شده، ارجاع به صفحه پورتال
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// نمایش اخطار انقضای جلسه پس از ۳۰ دقیقه بیکاری
if (isset($_GET['timeout'])) {
    $error = 'جلسه کاری شما به علت ۳۰ دقیقه بی‌تحرکی به پایان رسید. جهت امنیت، مجددا وارد شوید.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'لطفا نام کاربری و کلمه عبور را تکمیل نمایید.';
    } else {
        try {
            // استفاده از عبارات آماده جهت مصونیت علیه SQL Injection
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // پاکسازی و ایجاد سشن ایمن و جدید جهت پیشگیری از Session Fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_fullname'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['last_activity'] = time();

                // بررسی ضرورت تغییر کلمه عبور پیشفرض در ورود اول (مانند ادمین با پسورد admin123)
                if ($user['password_must_change'] == 1) {
                    $_SESSION['must_reset'] = true;
                    header("Location: login.php?reset=1");
                    exit();
                }

                header("Location: index.php");
                exit();
            } else {
                $error = 'نام کاربری یا کلمه عبور وارد شده نادرست است.';
            }
        } catch (PDOException $e) {
            $error = 'خطایی در مفسر دیتابیس رخ داده است: ' . h($e->getMessage());
        }
    }
}

// فرآیند کلمه عبور اجباری دور اول
if (isset($_GET['reset']) && isset($_SESSION['must_reset'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
        $new_pass = $_POST['new_password'] ?? '';
        $repeat_pass = $_POST['repeat_password'] ?? '';

        if (strlen($new_pass) < 6) {
            $error = 'رمز عبور جدید باید حداقل حاوی ۶ کاراکتر باشد.';
        } elseif ($new_pass !== $repeat_pass) {
            $error = 'تکرار کلمه عبور مطابقت ندارد!';
        } else {
            try {
                $hashed_pass = password_hash($new_pass, PASSWORD_BCRYPT);
                $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ?, password_must_change = 0 WHERE id = ?");
                $update_stmt->execute([$hashed_pass, $_SESSION['user_id']]);
                
                unset($_SESSION['must_reset']);
                $success = 'کلمه عبور شما با موفقیت به روز رسانی شد. در حال انتقال به پورتال...';
                header("refresh:2;url=index.php");
            } catch (PDOException $e) {
                $error = 'خطا در به روز رسانی رمز عبور: ' . h($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سامانه مدیریت دانش کارگاهی</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .login-logo h2 {
            color: var(--primary-color);
            font-size: 20px;
            font-weight: 800;
        }
        .login-logo .subtitle {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 5px;
        }
        .infobox {
            margin-top: 20px;
            background: #f1f5f9;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 12px;
            color: #475569;
            border-right: 3px solid var(--accent-color);
        }
    </style>
</head>
<body class="login-page">

<div class="login-card">
    <div class="login-logo">
        <h2>🛠️ پورتال دانش دپارتمان فنی</h2>
        <div class="subtitle">شرکت مهندسی عمران و معماری (نسخه تحت شبکه داخلی آفلاین)</div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="padding: 10px 15px; font-size:12px;"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success" style="padding: 10px 15px; font-size:12px;"><?= h($success) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['reset']) && isset($_SESSION['must_reset'])): ?>
        <!-- فرم تغییر اجباری پسورد در اولین ورود -->
        <div class="alert alert-warning" style="background:#fff8e1; color:#b78103; border: 1px solid #ffe082; padding: 10px 15px; font-size: 12px; line-height: 1.6; margin-bottom: 20px;">
            ⚠️ <strong>تغییر کلمه عبور اجباری:</strong> با توجه به اینکه اولین بار است وارد سیستم می‌شوید، یا ادمین اقدام به بازنشانی رمز شما کرده است، ملزم به انتخاب کلمه عبور جدید و اختصاصی هستید.
        </div>

        <form action="login.php?reset=1" method="POST" id="password-reset-form">
            <div class="form-group">
                <label for="new_password" class="required">کلمه عبور جدید اختصاصی</label>
                <input type="password" name="new_password" id="new_password" class="form-control" autocomplete="off" required>
            </div>
            
            <div class="form-group">
                <label for="repeat_password" class="required">تکرار کلمه عبور جدید</label>
                <input type="password" name="repeat_password" id="repeat_password" class="form-control" autocomplete="off" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">ثبت نهایی و ورود به سامانه 🗳️</button>
        </form>
    <?php else: ?>
        <!-- فرم ورود معمولی مجهز به تگهای امنیتی -->
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username" class="required">نام کاربری (حروف انگلیسی)</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="مثال: admin" autocomplete="off" required>
            </div>

            <div class="form-group">
                <label for="password" class="required">کلمه عبور تخصیص یافته</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="********" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">احراز هویت و ورود 🔐</button>
        </form>

        <div class="infobox">
            ℹ️ <strong>حساب‌های پیش‌فرض کارخانه‌ای شبکه:</strong><br>
            • مدیر سیستم: نام کاربری <strong>admin</strong> پسورد <strong>admin123</strong><br>
            • کارشناس ارشد: نام کاربری <strong>expert</strong> پسورد <strong>expert123</strong><br>
            • مهندس کارگاه: نام کاربری <strong>contributor</strong> پسورد <strong>field123</strong>
        </div>
    <?php endif; ?>
</div>

<script src="script.js"></script>
</body>
</html>
