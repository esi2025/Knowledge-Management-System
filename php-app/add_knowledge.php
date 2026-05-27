<?php
/**
 * سامانه مدیریت دانش فنی کارگاهی - ثبت چالش و تجربه فنی مهندسی جدید به همراه فایل‌های ضمیمه
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

require_login();

$error = '';
$success = '';

// بازیابی تمامی دسته‌بندی‌ها جهت پر کردن لیست کشویی فرم
try {
    $stmt_cats = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
    $categories = $stmt_cats->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// پردازش فرم پس از سابمیت به همراه اعتبارسنجی همه‌جانبه
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $problem = trim($_POST['problem'] ?? '');
    $solution = trim($_POST['solution'] ?? '');
    $result = trim($_POST['result'] ?? '');
    $keywords = trim($_POST['keywords'] ?? '');
    $date_occurred = $_POST['date_occurred'] ?: null;
    
    // وضعیت اولیه برای ثبت کننده معمولی پیش‌نویس است، کارشناس/ادمین توانایی انتشار آنی دارند
    $status = 'draft';
    if (in_array($_SESSION['user_role'], ['admin', 'expert']) && isset($_POST['instant_approve']) && $_POST['instant_approve'] == '1') {
        $status = 'approved';
    }

    if (empty($title) || empty($problem) || empty($solution) || $category_id === 0) {
        $error = 'جداول ستاره‌دار (عنوان، دسته‌بندی موضوعی، شرح چالش و شرح راهکار اجراشده) الزامی هستند.';
    } else {
        try {
            // آغاز تراکنش در دیتابیس (Transaction) جهت اطمینان از صحت کامل هردو عملیات ثبت محتوا و آپلود فایل‌ها
            $pdo->beginTransaction();

            $insert_query = "
                INSERT INTO knowledge_entries (user_id, title, problem, solution, result, category_id, keywords, date_occurred, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $ins_stmt = $pdo->prepare($insert_query);
            $ins_stmt->execute([
                $_SESSION['user_id'],
                $title,
                $problem,
                $solution,
                $result,
                $category_id,
                $keywords,
                $date_occurred,
                $status
            ]);
            
            $entry_id = $pdo->lastInsertId();

            // پردازش فایل‌های الحاقی گوناگون (حداکثر ۵ فایل همزمان مجاز)
            if (isset($_FILES['attachments'])) {
                $files = $_FILES['attachments'];
                $file_count = count($files['name']);
                
                for ($i = 0; $i < $file_count; $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }

                    // آماده‌سازی آرایه نمونه به تفکیک برای استفاده مجدد از تابع handle_file_upload
                    $single_file = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i],
                    ];

                    try {
                        $upload_res = handle_file_upload($single_file, $_SESSION['user_id']);
                        if ($upload_res) {
                            // درج در جدول رسانه‌ها
                            $media_stmt = $pdo->prepare("
                                INSERT INTO media (entry_type, entry_id, file_path, file_type, file_size, thumbnail_path, description)
                                VALUES ('knowledge', ?, ?, ?, ?, ?, ?)
                            ");
                            
                            $file_desc = $_POST['attachment_descriptions'][$i] ?? 'ضمیمه مستند دانش شماره ' . $entry_id;
                            
                            $media_stmt->execute([
                                $entry_id,
                                $upload_res['file_path'],
                                $upload_res['file_type'],
                                $upload_res['file_size'],
                                $upload_res['thumbnail_path'],
                                $file_desc
                            ]);
                        }
                    } catch (Exception $file_ex) {
                        // در صورت بروز خطا در یک فایل، تراکنش باطل می‌شود و هشدار داده خواهد شد
                        $pdo->rollBack();
                        $error = "خطا در پردازش فایل " . h($files['name'][$i]) . ": " . $file_ex->getMessage();
                        break;
                    }
                }
            }

            // اگر خطایی در حین ثبت فایل ایجاد نشده بود، تغییرات در دیتابیس اعمال نهایی می‌شود
            if (empty($error)) {
                $pdo->commit();
                if ($status === 'approved') {
                    $success = 'سند دانش آزموده شده شما با موفقیت به صورت منتشر شده ثبت گردید! در حال انتقال به لیست تجارب...';
                } else {
                    $success = 'پیش‌نویس تجربه شما با موفقیت به ثبت رسید و در صندوق ممیزی کارشناسان دفاتر فنی قرار گرفت. وضعیت کنونی: پیش‌نویس ممیزی نشده';
                }
                header("refresh:3;url=knowledge_list.php");
            }

        } catch (PDOException $db_ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'خطا در ثبت نهایی اطلاعات در دیتابیس: ' . h($db_ex->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت تجربه فنی کارگاهی جدید</title>
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
            <li class="active"><a href="add_knowledge.php">✏️ ثبت تجربه فنی جدید</a></li>
            <li><a href="knowledge_list.php">🔍 جستجو و فیلتر دانش</a></li>
            <li><a href="qna.php">❓ تریبون پرسش و پاسخ (Q&A)</a></li>
            <li><a href="gallery.php">🖼️ گالری بصری نظارت کارگاه</a></li>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <li style="border-top: 1px solid rgba(255,255,255,0.1); margin-top:10px;"><a href="admin_users.php" style="color:#e5ba73;">⚙️ مدیریت کاربران سیستم</a></li>
            <?php endif; ?>
            <li style="margin-top: auto;"><a href="logout.php" style="color: #ff8a80;">🚪 خروج از سامانه</a></li>
        </ul>
    </aside>

    <!-- محتوای اصلی چپ صفحه -->
    <main class="main-content">
        <header class="content-header">
            <div>
                <h2>برگه‌ورود اطلاعات و مستند کردن تجربه کارگاهی</h2>
                <p style="color: var(--text-muted); font-size: 13px; margin-top:5px;">فرآیند هم‌افزایی و تبلور دانش مفقود در مغز مهندسان اجرایی در قالب‌های کتبی و مالتی‌مدیا</p>
            </div>
            <div class="datetime-display">
                📅 امروز:  <strong><?= to_jalali(date('Y-m-d')) ?></strong>
            </div>
        </header>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-title">📝 فرم ثبت سند تجربیات مواجه‌شده در عملیات ساختمانی</div>
            <div class="card-body">
                <form action="add_knowledge.php" method="POST" enctype="multipart/form-data">
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="title" class="required">عنوان گزارش / رویداد فنی</label>
                                <input type="text" name="title" id="title" class="form-control" placeholder="مثال: نفوذ شتابنده آب پشت شاتکریت دیوار نیلینگ پروژه زعفرانیه" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="category_id" class="required">رسته پروژه / دسته‌بندی موضوعی</label>
                                <select name="category_id" id="category_id" class="form-control" required>
                                    <option value="">-- لطفاً رسته‌ای را مشخص کنید --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= h($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="problem" class="required">شرح تفصیلی چالش یا مشکل مواجه شده (شامل نشانه‌های خرابی، عواقب و ریسک ایجاد شده)</label>
                        <textarea name="problem" id="problem" rows="4" class="form-control" placeholder="جزئیات ریز اتفاق، شرایط محیطی، رطوبت خاک یا کیفیت میلگردها را بنویسید..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="solution" class="required">راهکار مهندسی یا نظارتی اعمال شده جهت مهار چالش (شرح دقیق گام‌های کارگاهی و مواد کاربردی)</label>
                        <textarea name="solution" id="solution" rows="4" class="form-control" placeholder="توقف عملیات، حفر چاه و لوله‌گذاری صلب، تغییر قطر نیل‌ها یا تمهیدات کورتینگ..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="result">نتیجه فنی نهایی / مزایای اقتصادی بدست آمده پس از مهار چالش (اختیاری)</label>
                        <textarea name="result" id="result" rows="2" class="form-control" placeholder="مثال: پایداری خاک تامین شد و بعد از سه ماه هیچ نشت یا ترکی گزارش نگردید..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="keywords">کلمات کلیدی جستجو (با کامای انگلیسی تفکیک کنید)</label>
                                <input type="text" name="keywords" id="keywords" class="form-control" placeholder="مثال: نیلینگ, زهکشی, گودبرداری, نشت_آب">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="date_occurred">تاریخ دقیق وقوع رویداد فنی</label>
                                <input type="date" name="date_occurred" id="date_occurred" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- بخش آپلود چندگانه فایل‌های مالتی‌مدیا به همراه توضیح برای هریک -->
                    <div class="card" style="margin-top:20px; border-color:#e5ba73; background:#fffdfc;">
                        <div class="card-title" style="background:#fffaf0; border-bottom-color:#e5ba73; color:#a27113; font-size:14px;">
                            🎥 ضمیمه‌های مالتی‌مدیای کمکی (عکس‌های محل، فیلم توضیحی و ثبت صوتی جلسه کارگاه)
                        </div>
                        <div class="card-body">
                            <p style="font-size:12px; color:var(--text-muted); margin-bottom:15px; background:white; padding:10px; border:1px solid #ddd; border-radius:4px;">
                                ⚠️ پسوند فایل‌های مجاز: <strong>JPG, PNG, MP4, MP3, M4A</strong> | حداکثر ظرفیت مجاز برای هر فایل: <strong>۵۰ مگابایت</strong>
                            </p>
                            
                            <!-- تولید ۳ ردیف آپلود پویا جهت سهولت مهندسان -->
                            <?php for ($i = 0; $i < 3; $i++): ?>
                                <div style="display:flex; gap:15px; margin-bottom:12px; align-items:flex-end; border-bottom:1px solid #eee; padding-bottom:10px;">
                                    <div style="flex:1;">
                                        <label style="font-size:12px; margin-bottom:5px; font-weight:bold;">انتخاب فایل ضمیمه شماره <?= convert_en_to_fa_digits($i+1) ?></label>
                                        <input type="file" name="attachments[]" class="form-control" style="padding:6px 12px; font-size:12px;">
                                    </div>
                                    <div style="flex:2;">
                                        <label style="font-size:12px; margin-bottom:5px; font-weight:bold;">توضیحات کوتاه برای فایل (چه چیزی در این تصویر یا فیلم نمایش داده شده؟)</label>
                                        <input type="text" name="attachment_descriptions[]" class="form-control" style="padding:7px 12px; font-size:12px;" placeholder="به عنوان نمونه: نمای شاتکریت ترک خورده ردیف دوم گود شرقی">
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- تایید آنی توسط ادمین یا استاد کارگاه -->
                    <?php if (in_array($_SESSION['user_role'], ['admin', 'expert'])): ?>
                        <div style="margin-top:20px; background:#f0fdf4; border:1px solid #bbf7d0; padding:15px; border-radius:6px; display:flex; align-items:center; gap:10px;">
                            <input type="checkbox" name="instant_approve" id="instant_approve" value="1" style="transform: scale(1.3); cursor:pointer;">
                            <label for="instant_approve" style="margin-bottom:0; font-weight:bold; color:#166534; cursor:pointer;">
                                ☑️ تأیید ممیزی و انتشار آنی در سامانه (با تیک خوردن این گزینه، سند بلافاصله تایید عمومی شده و برای همه همکاران قابل مشاهده خواهد شد).
                            </label>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 30px; display:flex; gap:10px; justify-content:flex-end;">
                        <a href="index.php" class="btn btn-secondary">انصراف و بازگشت ↩️</a>
                        <button type="submit" class="btn btn-primary" style="padding: 12px 35px;">ثبت اطلاعات و بارگذاری فایل‌ها 🗳️</button>
                    </div>

                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>
