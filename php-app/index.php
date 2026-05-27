<?php
/**
 * سامانه مدیریت دانش فنی کارگاهی - صفحه اصلی و پورتال ورود اطلاعات
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// گیت ورودی احراز هویت
require_login();

// پارامترهای موتور جستجوی سراسری
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// ۱. بازیابی دسته‌بندی‌های والدی برای فیلتر باکس
try {
    $cat_stmt = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// ۲. ساخت کوئری جستجو بر اساس فیدبک کاربران (فقط آیتم‌های با وضعیت approved به استثناء دسترسی ادمین/کارشناس به پیش‌نویس‌ها در صورت دلخواه)
$query_parts = ["k.status = 'approved'"];
$params = [];

if (!empty($search)) {
    $query_parts[] = "(k.title LIKE ? OR k.problem LIKE ? OR k.solution LIKE ? OR k.keywords LIKE ?)";
    $like_val = "%$search%";
    $params[] = $like_val;
    $params[] = $like_val;
    $params[] = $like_val;
    $params[] = $like_val;
}

if ($category_filter > 0) {
    $query_parts[] = "(k.category_id = ? OR c.parent_id = ?)";
    $params[] = $category_filter;
    $params[] = $category_filter;
}

$where_clause = implode(" AND ", $query_parts);

try {
    // بازیابی به همراه تعداد رسانه‌ها و سوابق نویسنده
    $stmt = $pdo->prepare("
        SELECT k.*, u.full_name as author, c.name as category_name,
               (SELECT COUNT(*) FROM media m WHERE m.entry_type = 'knowledge' AND m.entry_id = k.id) as media_count
        FROM knowledge_entries k
        JOIN users u ON k.user_id = u.id
        LEFT JOIN categories c ON k.category_id = c.id
        WHERE $where_clause
        ORDER BY k.created_at DESC
        LIMIT 6
    ");
    $stmt->execute($params);
    $recent_entries = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_entries = [];
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سامانه مدیریت دانش و تجارب کارگاهی</title>
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
            <li class="active"><a href="index.php">📊 صفحه نخست پورتال</a></li>
            <li><a href="dashboard.php">📈 داشبورد و آمار تجارب</a></li>
            <li><a href="add_knowledge.php">✏️ ثبت تجربه فنی جدید</a></li>
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
                <h2>خوش‌آمدید به بانک دانش دپارتمان فنی</h2>
                <p style="color: var(--text-muted); font-size: 13px; margin-top:5px;">بایگانی تجارب متبلور مهندسان ارشد در ده سال گذشته کارگاه‌های ساختمانی</p>
            </div>
            <div class="datetime-display">
                📅 امروز:  <strong><?= to_jalali(date('Y-m-d')) ?></strong>
            </div>
        </header>

        <!-- بخش بنرهای تبلیغاتی دسترسی سریع کارگاهی -->
        <div class="stats-container" style="grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 35px;">
            <div class="stat-card" style="border-right: 5px solid var(--primary-color);">
                <div>
                    <div style="font-weight: bold; font-size:16px;">ثبت آسان تجربه مکتوب</div>
                    <p style="font-size: 11px; color: var(--text-muted); margin-top:5px;">شیرآبه نشتی، لرزش خاک، رانش دیوار نگهبان یا قالب شالوده را مستند کنید.</p>
                    <a href="add_knowledge.php" class="btn btn-primary" style="padding: 5px 10px; font-size: 11px; margin-top: 10px;">وارد کردن مستندات 📝</a>
                </div>
            </div>
            <div class="stat-card" style="border-right: 5px solid var(--warning-color);">
                <div>
                    <div style="font-weight: bold; font-size:16px;">طرح سوال اضطراری</div>
                    <p style="font-size: 11px; color: var(--text-muted); margin-top:5px;">در صورت بروز بن‌بست‌های اجرایی یا ترک فونداسیون، سریع از متخصصان بپرسید.</p>
                    <a href="qna.php" class="btn btn-warning" style="padding: 5px 10px; font-size: 11px; text-shadow:none; color:#333; margin-top: 10px;">ثبت درخواست مشاوره 🙋‍♂️</a>
                </div>
            </div>
            <div class="stat-card" style="border-right: 5px solid var(--success-color);">
                <div>
                    <div style="font-weight: bold; font-size:16px;">نکات بصری نظارت</div>
                    <p style="font-size: 11px; color: var(--text-muted); margin-top:5px;">تصاویر قبل و بعد، مقایسه جوش غلط و درست در اسکلت را به اشتراک بگذارید.</p>
                    <a href="gallery.php" class="btn btn-success" style="padding: 5px 10px; font-size: 11px; margin-top: 10px;">ورود به گالری تصاویر 📸</a>
                </div>
            </div>
        </div>

        <!-- فرم جستجوی سریع -->
        <div class="filter-bar" style="margin-bottom: 35px;">
            <form action="index.php" method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
                <div class="form-group" style="flex: 2; margin-bottom: 0;">
                    <label for="search">جستجوی آزاد کلمات کلیدی، عناوین یا عیوب سازه‌ای</label>
                    <input type="text" name="search" id="search" value="<?= h($search) ?>" placeholder="مثال: ترک فونداسیون، نیلینگ، عایق، شاتکریت..." class="form-control">
                </div>
                
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label for="category">رسته یا دسته‌بندی موضوعی</label>
                    <select name="category" id="category" class="form-control">
                        <option value="0">--- تمامی رسته‌ها ---</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <div style="margin-bottom: 0;">
                    <button type="submit" class="btn btn-primary" style="padding: 11px 25px;">جستجوی هوشمند 🔍</button>
                </div>
                <?php if (!empty($search) || $category_filter > 0): ?>
                    <div style="margin-bottom: 0;">
                        <a href="index.php" class="btn btn-secondary" style="padding: 11px 20px;">حذف فیلترها</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- لیست آخرین تجارب کارگاهی ثبت شده -->
        <h3 style="margin-bottom: 20px; color: var(--dark-slate); display: flex; align-items: center; gap: 10px; font-weight:700;">
            <span>📌</span> آخرین مستندات ثبت شده تایید شده
        </h3>

        <?php if (empty($recent_entries)): ?>
            <div class="alert alert-danger" style="text-align: center; border-style: dashed; padding: 40px 20px;">
                نتیجه‌ای برای جستجوی شما یافت نشد یا هنوز سندی با این مشخصات تایید عمومی نشده است.
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px;">
                <?php foreach ($recent_entries as $entry): ?>
                    <div class="card" style="display:flex; flex-direction:column; justify-content:space-between; margin-bottom: 0;">
                        <div class="card-title" style="font-size: 15px;">
                            <span><?= h($entry['title']) ?></span>
                            <span style="font-size:11px; background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:4px; font-weight:normal;"><?= h($entry['category_name']) ?></span>
                        </div>
                        <div class="card-body" style="padding: 15px 20px; flex-grow:1;">
                            <div style="font-size:12px; color:var(--text-muted); margin-bottom:10px; display:flex; justify-content:space-between;">
                                <span>نویسنده: <strong><?= h($entry['author']) ?></strong></span>
                                <span>زمان رویداد: 📝 <?= to_jalali($entry['date_occurred']) ?></span>
                            </div>
                            
                            <h4 style="font-size:13px; color:var(--danger-color); font-weight:bold; margin-bottom:5px;">شرح چالش/مسئله:</h4>
                            <p style="font-size:13px; color:#555; text-align:justify; line-height:1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom:15px;">
                                <?= h($entry['problem']) ?>
                            </p>

                            <h4 style="font-size:13px; color:var(--success-color); font-weight:bold; margin-bottom:5px;">راهکار نظارتی اعمال شده:</h4>
                            <p style="font-size:13px; color:#555; text-align:justify; line-height:1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom:15px;">
                                <?= h($entry['solution']) ?>
                            </p>
                            
                            <?php if (!empty($entry['keywords'])): ?>
                                <div style="margin-top: 10px; display:flex; flex-wrap:wrap; gap:5px;">
                                    <?php foreach (explode(',', $entry['keywords']) as $kw): ?>
                                        <span style="font-size:10px; background:#f1f5f9; color:#475569; padding:2px 6px; border-radius:3px;">#<?= h(trim($kw)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="background: #fafafa; border-top:1px solid var(--border-color); padding: 10px 20px; display:flex; justify-content:space-between; align-items:center; font-size:12px; border-bottom-left-radius:10px; border-bottom-right-radius:10px;">
                            <span>👀 تعداد بازدید: <strong><?= convert_en_to_fa_digits($entry['views']) ?></strong> مرتبه</span>
                            <span>📎 فایل‌های ضمیمه: <strong><?= convert_en_to_fa_digits($entry['media_count']) ?></strong> عدد</span>
                            <a href="view_knowledge.php?id=<?= $entry['id'] ?>" class="btn btn-primary" style="padding: 4px 10px; font-size: 11px;">مشاهده کامل راهکار ↩️</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="knowledge_list.php" class="btn btn-secondary" style="padding: 10px 30px;">مشاهده و جستجو در کل گنجینه دانش کارگاه‌ها 🔍</a>
            </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>
