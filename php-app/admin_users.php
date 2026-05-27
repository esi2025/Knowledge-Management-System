<?php
/**
 * سامانه مدیریت دانش فنی کارگاهی - پنل اختصاصی مدیریت کاربران (ویژه مدیر سیستم)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// ممیزی نقش: سد ورود غریبه‌ها و فقط مجاز برای نقش admin
check_role('admin');

$success_msg = '';
$error_msg = '';

$current_admin_id = $_SESSION['user_id'];

// پردازش ثبت کاربر جدید مقید به پسورد اجباری اول
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'contributor';
    $password_must_change = isset($_POST['password_must_change']) ? 1 : 0;

    if (empty($username) || empty($full_name) || empty($password) || empty($role)) {
        $error_msg = 'لطفا تمامی فیلدها را در فرم ثبت‌نام پر نمایید.';
    } elseif (!in_array($role, ['admin', 'expert', 'contributor'])) {
        $error_msg = 'نقش امنیتی کابر وارد شده غیرمجاز است.';
    } else {
        try {
            // انکریپت کردن کلمه‌عبور با تکنولوژی امن Bcrypt
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role, password_must_change) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed, $full_name, $role, $password_must_change]);
            
            $success_msg = "کاربر جدید با نام کاربری " . h($username) . " با موفقیت در بانک مستقر گردید.";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error_msg = "نام کاربری " . h($username) . " تکراری است و قبلا در سامانه به ثبت رسید.";
            } else {
                $error_msg = "خطا در فرآیند ثبت دیتابیس: " . h($e->getMessage());
            }
        }
    }
}

// پردازش بازنشانی رمز عبور یا تغییر نقش کاربر انتخاب شده
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $target_uid = (int)$_POST['user_id'];
    $role = $_POST['role'] ?? 'contributor';
    $full_name = trim($_POST['full_name'] ?? '');
    $reset_pass = $_POST['reset_password'] ?? '';

    if (empty($full_name) || empty($role)) {
        $error_msg = 'متن نام کامل و نقش نباید خالی گنجانده شود.';
    } else {
        try {
            $pdo->beginTransaction();

            $update_sql = "UPDATE users SET role = ?, full_name = ? WHERE id = ?";
            $up_stmt = $pdo->prepare($update_sql);
            $up_stmt->execute([$role, $full_name, $target_uid]);

            // اگر مدیر مایل به تنظیم مجدد کلمه عبور باشد
            if (!empty($reset_pass)) {
                $new_hashed = password_hash($reset_pass, PASSWORD_BCRYPT);
                // مدیر کلمه عبور را ریست کرده، پس در لاگین اول کاربر مجاب به تغییر است
                $pass_stmt = $pdo->prepare("UPDATE users SET password_hash = ?, password_must_change = 1 WHERE id = ?");
                $pass_stmt->execute([$new_hashed, $target_uid]);
            }

            $pdo->commit();
            $success_msg = "پروفایل امنیتی کاربر منتخب با موفقیت به‌روزرسانی نهایی شد.";
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = "خطا در تنظیمات ویرایش کاربر: " . h($e->getMessage());
        }
    }
}

// پردازش حذف کابر (بدون امکان خودکشی اکانت مدیر جاری)
if (isset($_GET['delete_user'])) {
    $delete_id = (int)$_GET['delete_user'];
    
    if ($delete_id === $current_admin_id) {
        $error_msg = "❌ خطای امنیتی: شما مجاز به حذف اکانت مدیریتی خود در حین اجرای سشن جاری نیستید!";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$delete_id]);
            $success_msg = "اکانت کاربر با کد شناسایی " . convert_en_to_fa_digits($delete_id) . " برای همیشه از هاست محلی برداشته شد.";
        } catch (PDOException $e) {
            $error_msg = "خطا در فرآیند ارتباط پایگاه حذف: " . h($e->getMessage());
        }
    }
}

// بازیبانی لیست کل کاربران در دایرکتوری جاری
try {
    $users_stmt = $pdo->query("SELECT id, username, full_name, role, password_must_change, created_at FROM users ORDER BY created_at DESC");
    $all_users = $users_stmt->fetchAll();
} catch (PDOException $e) {
    die("خطا شدید در بارگذاری مخزن کاربران: " . h($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت و ممیزی کاربران سیستم</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="wrapper">
    <!-- منوی مجاور (Sidebar) -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h1>مدیریت دانش عمران</h1>
            <p>سامانه ثبت و بازخوانی تجربیات کارگاهی</p>
        </div>
        
        <div class="user-profile-widget">
            <span class="text-muted">کاربر فعلی:</span><br>
            <span class="user-name"><?= h($_SESSION['user_fullname']) ?></span><br>
            <span style="font-size: 11px; color:#57c5b6;"><?= get_role_name($_SESSION['user_role']) ?></span>
        </div>

        <ul class="sidebar-menu">
            <li><a href="index.php">📊 صفحه نخست پورتال</a></li>
            <li><a href="dashboard.php">📈 داشبورد و آمار تجارب</a></li>
            <li><a href="add_knowledge.php">✏️ ثبت تجربه فنی جدید</a></li>
            <li><a href="knowledge_list.php">🔍 جستجو و فیلتر دانش</a></li>
            <li><a href="qna.php">❓ تریبون پرسش و پاسخ (Q&A)</a></li>
            <li><a href="gallery.php">🖼️ گالری بصری نظارت کارگاه</a></li>
            <li class="active" style="border-top: 1px solid rgba(255,255,255,0.1); margin-top:10px;"><a href="admin_users.php" style="color:#e5ba73;">⚙️ مدیریت کاربران سیستم</a></li>
            <li style="margin-top: auto;"><a href="logout.php" style="color: #ff8a80;">🚪 خروج از سامانه</a></li>
        </ul>
    </aside>

    <!-- محتوای اصلی چپ صفحه -->
    <main class="main-content">
        <header class="content-header">
            <div>
                <h2>پنل مدیریت دسترسی‌ها و حقوق حساب مهندسین</h2>
                <p style="color: var(--text-muted); font-size: 13px; margin-top:5px;">ایجاد، ویرایش، عزل، بازنشانی کلمه‌عبور حساب‌های کاربری و فیلتر نقش‌های دفاتر نظارتی</p>
            </div>
            <div class="datetime-display">
                کاربر کل سیستم: <strong><?= convert_en_to_fa_digits(count($all_users)) ?> کاربر</strong>
            </div>
        </header>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?= h($success_msg) ?></div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger"><?= h($error_msg) ?></div>
        <?php endif; ?>

        <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr;">
            
            <!-- فرم ساخت کاربر جدید -->
            <div class="card">
                <div class="card-title" style="background:#f8fafc; color: var(--primary-color);">➕ صدور دسترسی کاربری جدید برای کادر مهندسین مقیم</div>
                <div class="card-body">
                    <form action="admin_users.php" method="POST">
                        <div class="form-group">
                            <label for="username" class="required">نام کاربری اختصاصی (انگلیسی بدون فاصله)</label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="نمونه: m.rezaei" autocomplete="off" required>
                        </div>

                        <div class="form-group">
                            <label for="full_name" class="required">نام کامل شخص (به همراه ذکر سمت مهندسی)</label>
                            <input type="text" name="full_name" id="full_name" class="form-control" placeholder="مثال: مهندس مانی رضایی (سرپرست کارگاه)" required>
                        </div>

                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="password" class="required">کلمه عبور اختصاصی اولیه</label>
                                    <input type="password" name="password" id="password" class="form-control" placeholder="کلمه‌عبور موقت" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="role" class="required">نقش امنیتی و تایید تراز</label>
                                    <select name="role" id="role" class="form-control" required>
                                        <option value="contributor">ثبت‌کننده (مهندس اجرایی و ناظر کارگاه)</option>
                                        <option value="expert">کارشناس ارشد ممیز (مشاور دپارتمان مرکزی)</option>
                                        <option value="admin">مدیر فنی ارشد شبکه (Admin)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="background:#f0fdf4; border:1px solid #bbf7d0; padding:10px 15px; border-radius:6px; display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="password_must_change" id="password_must_change" value="1" style="transform:scale(1.2); cursor:pointer;" checked>
                            <label for="password_must_change" style="margin-bottom:0; font-size:12.5px; color:#166534; cursor:pointer;">
                                ☑️ اجبار به تغییر کلمه‌عبور موقت در ورود نخست کاربر به سامانه
                            </label>
                        </div>

                        <button type="submit" name="add_user" class="btn btn-primary" style="width:100%; margin-top:10px;">صدور حساب کاربری جدید 🚀</button>
                    </form>
                </div>
            </div>

            <!-- فرم مجهز بازنشانی رمز یا اصلاح نقش کاربر -->
            <div class="card">
                <div class="card-title" style="background:#fde8e8; color: var(--danger-color)">⚙️ اصلاح کلمه عبور یا تغییر سمت کاربر مستقر</div>
                <div class="card-body">
                    <form action="admin_users.php" method="POST">
                        <div class="form-group">
                            <label for="user_id" class="required">انتخاب شخص هدف</label>
                            <select name="user_id" id="user_id" class="form-control" required>
                                <option value="">-- مهندس یا کاربر متبوع را مشخص کنید --</option>
                                <?php foreach ($all_users as $usr): ?>
                                    <option value="<?= $usr['id'] ?>"><?= h($usr['full_name']) ?> (<?= h($usr['username']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_full_name" class="required">اصلاح نام کامل شخص</label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_role" class="required">بروز رسانی سطح مسئولیت</label>
                            <select name="role" id="edit_role" class="form-control" required>
                                <option value="contributor">ثبت‌کننده (مهندس اجرایی و ناظر کارگاه)</option>
                                <option value="expert">کارشناس ارشد ممیز (مشاور دپارتمان مرکزی)</option>
                                <option value="admin">مدیر فنی ارشد شبکه (Admin)</option>
                            </select>
                        </div>

                        <div class="form-group" style="background:#efecff; border:1px solid #dcd4ff; padding:12px; border-radius:6px;">
                            <label for="reset_password">تغییر فوری کلمه عبور (در صورت نیاز به بازنشانی)</label>
                            <input type="password" name="reset_password" id="reset_password" class="form-control" placeholder="برای عدم تغییر، این فیلد را خالی رها بگذارید" autocomplete="off">
                            <p style="font-size:10.5px; color:#5546ad; margin-top:5px;">💡 تذکر: بازنشانی رمز عبور، مجددا کاربر را مجبور به تغییر رمز پس از اولین لاگین خواهد کرد.</p>
                        </div>

                        <button type="submit" name="edit_user" class="btn btn-danger" style="width:100%;">ثبت نهایی تغییرات پروفایل امنیتی 🗳️</button>
                    </form>
                </div>
            </div>

        </div>

        <!-- جدول همگام سازی دایرکتوری نهایی کاربران فعال -->
        <h3 style="margin:25px 0 15px 0; color:var(--dark-slate); font-weight:700;">📂 مستندات حساب‌های کاربری فعال در پایگاه‌داده شتاب</h3>
        
        <div class="card">
            <div class="card-body" style="padding:0;">
                <table style="margin:0;">
                    <thead>
                        <tr>
                            <th>تاریخ ساخت اکانت</th>
                            <th>نام کامل مهندس</th>
                            <th>شناسه کاربری انگلیسی</th>
                            <th>سمت سازمانی / سطح دسترسی</th>
                            <th>نیاز به تغییر کلمه عبور</th>
                            <th style="width:120px; text-align:center;">جک حذف دسترسی</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($all_users as $usr): ?>
                            <tr>
                                <td><?= to_jalali($usr['created_at']) ?></td>
                                <td style="font-weight:bold;"><?= h($usr['full_name']) ?></td>
                                <td><code><?= h($usr['username']) ?></code></td>
                                <td>
                                    <span style="background:#eff6ff; color:#1d4ed8; padding:3px 8px; border-radius:20px; font-size:11px; font-weight:bold;">
                                        <?= get_role_name($usr['role']) ?>
                                    </span>
                                </td>
                                <td><?= $usr['password_must_change'] == 1 ? '<span style="color:#b45309; font-weight:bold;">⚠️ بله (باید تغییر کند)</span>' : '<span style="color:#15803d;">✓ خیر (عادی)</span>' ?></td>
                                <td style="text-align:center;">
                                    <?php if ($usr['id'] !== $current_admin_id): ?>
                                        <a href="admin_users.php?delete_user=<?= $usr['id'] ?>" class="btn btn-danger" style="padding:4px 8px; font-size:11px;" onclick="return confirm('آیا از حذف دائم و سلب دسترسی کامل این کاربر کارگاهی مطمئن هستید؟')">⛔ مسدودسازی دائم</a>
                                    <?php else: ?>
                                        <span style="font-size:11px; color:#999; font-style:italic;">-- بدون خودکشی اکانت --</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- اسکریپت جاوا اسکریپت ویرایش آسان دسته‌بندی با انتخاب در کشو -->
<script>
document.getElementById('user_id').addEventListener('change', function() {
    var uid = this.value;
    if (!uid) return;
    
    // شبیه‌ساز جاگذاری سریع مشخصات فیلدهای کاربر برای مدیر
    <?php
    $js_map = [];
    foreach ($all_users as $u) {
        $js_map[$u['id']] = [
            'name' => $u['full_name'],
            'role' => $u['role']
        ];
    }
    ?>
    var map = <?= json_encode($js_map) ?>;
    if (map[uid]) {
        document.getElementById('edit_full_name').value = map[uid]['name'];
        document.getElementById('edit_role').value = map[uid]['role'];
    }
});
</script>
</body>
</html>
