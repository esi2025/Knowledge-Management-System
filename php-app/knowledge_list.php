<?php
/**
 * سامانه مدیریت دانش فنی کارگاهی - صندوق جستجو و آرشیو جامع تجارب
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

require_login();

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// پارامترهای فیلتر دریافت شده از کلاینت
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$author_id = isset($_GET['author']) ? (int)$_GET['author'] : 0;
$media_filter = isset($_GET['media_type']) ? trim($_GET['media_type']) : ''; // 'image', 'video', 'audio'
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'approved'; // پیش فرض فقط تایید شده ها

// سفت‌کاری حقوق دسترسی: کاربر contributor فقط اجازه دیدن اسناد 'approved' و کارهای پیش‌نویس خودش را دارد!
if ($user_role === 'contributor') {
    $status_filter = 'approved'; 
}

// محاسبات صفحه‌بندی (۱۰ تجربه در هر صفحه بر اساس بند ۵ صورت‌مسئله)
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// ساخت قطعه بندهای پویای کوئری جستجو
$query_parts = [];
$params = [];

// فیلتر عمومی نقش‌ها
if ($user_role === 'contributor') {
    $query_parts[] = "(k.status = 'approved' OR k.user_id = ?)";
    $params[] = $user_id;
} else {
    // ادمین و اکسپرت هر وضعیتی را که انتخاب کنند می‌بینند
    if (!empty($status_filter)) {
        $query_parts[] = "k.status = ?";
        $params[] = $status_filter;
    }
}

// فیلتر جستجوی متنی
if (!empty($search)) {
    $query_parts[] = "(k.title LIKE ? OR k.problem LIKE ? OR k.solution LIKE ? OR k.keywords LIKE ?)";
    $like_val = "%$search%";
    $params[] = $like_val;
    $params[] = $like_val;
    $params[] = $like_val;
    $params[] = $like_val;
}

// فیلتر دسته بندی
if ($category_id > 0) {
    $query_parts[] = "k.category_id = ?";
    $params[] = $category_id;
}

// فیلتر نویسنده اثر
if ($author_id > 0) {
    $query_parts[] = "k.user_id = ?";
    $params[] = $author_id;
}

// فیلتر تخصصی نوع پیوست رسانه‌ای (عکس، فیلم یا وویس)
if (!empty($media_filter)) {
    if ($media_filter === 'image') {
        $query_parts[] = "EXISTS (SELECT 1 FROM media m WHERE m.entry_type = 'knowledge' AND m.entry_id = k.id AND m.file_type LIKE 'image/%')";
    } elseif ($media_filter === 'video') {
        $query_parts[] = "EXISTS (SELECT 1 FROM media m WHERE m.entry_type = 'knowledge' AND m.entry_id = k.id AND m.file_type LIKE 'video/%')";
    } elseif ($media_filter === 'audio') {
        $query_parts[] = "EXISTS (SELECT 1 FROM media m WHERE m.entry_type = 'knowledge' AND m.entry_id = k.id AND m.file_type LIKE 'audio/%')";
    }
}

$where_clause = "";
if (count($query_parts) > 0) {
    $where_clause = "WHERE " . implode(" AND ", $query_parts);
}

try {
    // ۱. استخراج کل سوابق مشابه جهت محاسبه تعداد صفحات در بخش پیجینیشن
    $count_sql = "
        SELECT COUNT(DISTINCT k.id) 
        FROM knowledge_entries k
        LEFT JOIN categories c ON k.category_id = c.id
        $where_clause
    ";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_rows = $count_stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);

    // ۲. استخراج آیتم‌های نهایی متناظر صفحه مورد نظر
    $data_sql = "
        SELECT k.*, u.full_name as author, c.name as category_name,
               (SELECT COUNT(*) FROM media m WHERE m.entry_type = 'knowledge' AND m.entry_id = k.id) as media_count
        FROM knowledge_entries k
        JOIN users u ON k.user_id = u.id
        LEFT JOIN categories c ON k.category_id = c.id
        $where_clause
        ORDER BY k.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt_data = $pdo->prepare($data_sql);
    $stmt_data->execute($params);
    $entries = $stmt_data->fetchAll();

    // ۳. بارگذاری لیست دسته‌بندی‌ها و نویسندگان برای منوهای دراپ‌داون فرم فیلتر
    $categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
    $authors = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetchAll();

} catch (PDOException $e) {
    die("خطا در محاسبات موتور آرشیو دانش: " . h($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>آرشیو و جستجو در تجارب کارگاهی</title>
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
            <li class="active"><a href="knowledge_list.php">🔍 جستجو و فیلتر دانش</a></li>
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
                <h2>فیلتر و جستجوی پیشرفته اسناد دانش فنی</h2>
                <p style="color: var(--text-muted); font-size: 13px; margin-top:5px;">امکان دسترسی سریع به جزئیات اجرایی پروژه‌ها بر اساس نوع عیب، رسته و نوع اسناد رسانه‌ای</p>
            </div>
            <div class="datetime-display">
                تعداد کل نتایج:  <strong><?= convert_en_to_fa_digits($total_rows) ?> تجربه</strong>
            </div>
        </header>

        <!-- فرم جستجوی تفکیک یافته همه‌جانبه -->
        <div class="filter-bar">
            <form action="knowledge_list.php" method="GET">
                <div class="form-row" style="margin-bottom: 15px;">
                    <div class="form-col" style="flex:2;">
                        <input type="text" name="search" class="form-control" value="<?= h($search) ?>" placeholder="جستجو در عنوان، مسئله، راهکار مهار چالش یا برچسب‌ها...">
                    </div>
                    <div class="form-col">
                        <select name="category" class="form-control">
                            <option value="0">-- تمامی رسته‌های سازه‌ای --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="margin-bottom: 15px; align-items: center;">
                    <div class="form-col">
                        <select name="author" class="form-control">
                            <option value="0">-- تفکیک بر اساس نویسنده/ناظر --</option>
                            <?php foreach ($authors as $auth): ?>
                                <option value="<?= $auth['id'] ?>" <?= $author_id == $auth['id'] ? 'selected' : '' ?>><?= h($auth['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-col">
                        <select name="media_type" class="form-control">
                            <option value="">-- نوع داکیومنت رسانه‌ای --</option>
                            <option value="image" <?= $media_filter === 'image' ? 'selected' : '' ?>>دارای تصویر شات زنده کارگاه</option>
                            <option value="video" <?= $media_filter === 'video' ? 'selected' : '' ?>>دارای ویدئوی آموزشی و کلوپ</option>
                            <option value="audio" <?= $media_filter === 'audio' ? 'selected' : '' ?>>دارای فایل ضبط صدای مهندس</option>
                        </select>
                    </div>

                    <?php if (in_array($user_role, ['admin', 'expert'])): ?>
                        <div class="form-col">
                            <select name="status" class="form-control">
                                <option value="">-- وضعیت چرخه تایید (همه) --</option>
                                <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>تایید شده و مصوب عمومی</option>
                                <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>پیش‌نویس بررسی نشده</option>
                                <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>مرجوع‌شده و فاقد تایید علمی</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn btn-primary">اعمال فیلترها ⚙️</button>
                        <?php if(!empty($search) || $category_id > 0 || $author_id > 0 || !empty($media_filter) || $status_filter !== 'approved'): ?>
                            <a href="knowledge_list.php" class="btn btn-secondary">حذف فیلتر</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- جدول نتایج استخراج شده -->
        <div class="card">
            <div class="card-title">📂 دفترچه ثبت تجارب فنی مهندسین عمران شرکتی</div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($entries)): ?>
                    <div style="padding: 40px; text-align:center; color: var(--text-muted); font-size:14px;">
                        هیچ تجربه تایید شده یا ثبت‌شده‌ای منطبق با انتخاب شما پیدا نشد.
                    </div>
                <?php else: ?>
                    <table style="margin: 0;">
                        <thead>
                            <tr>
                                <th style="width: 60px;">کد</th>
                                <th>عنوان گرانبهای تجربه</th>
                                <th>دپارتمان/رسته</th>
                                <th>نویسنده (نقش)</th>
                                <th>تاریخ رویداد</th>
                                <th>سنگینی رسانه</th>
                                <?php if (in_array($user_role, ['admin', 'expert'])): ?>
                                    <th>وضعیت</th>
                                <?php endif; ?>
                                <th style="width:120px; text-align:center;">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td><?= convert_en_to_fa_digits($entry['id']) ?></td>
                                    <td>
                                        <div style="font-weight: bold; font-size:13px;"><?= h($entry['title']) ?></div>
                                        <?php if(!empty($entry['keywords'])): ?>
                                            <div style="margin-top:4px; font-size:10px; color:var(--text-muted);">
                                                برچسب‌ها: <?= h($entry['keywords']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="background-color: #f1f5f9; padding:3px 7px; border-radius:4px; font-size:11px; color:#475569;">
                                            <?= h($entry['category_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><strong><?= h($entry['author']) ?></strong></div>
                                    </td>
                                    <td><?= to_jalali($entry['date_occurred']) ?></td>
                                    <td>
                                        <span style="font-size:12px;" title="فایل ضمیمه">
                                            📎 <?= convert_en_to_fa_digits($entry['media_count']) ?> ضمیمه
                                        </span>
                                    </td>
                                    <?php if (in_array($user_role, ['admin', 'expert'])): ?>
                                        <td><?= get_status_html($entry['status']) ?></td>
                                    <?php endif; ?>
                                    <td style="text-align:center;">
                                        <a href="view_knowledge.php?id=<?= $entry['id'] ?>" class="btn btn-secondary" style="padding: 4px 8px; font-size:11px; white-space:nowrap;">مطالعه کامل ↩️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- بخش صفحه‌بندی (پیجینیشن فارسی) -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="knowledge_list.php?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&category=<?= $category_id ?>&author=<?= $author_id ?>&media_type=<?= urlencode($media_filter) ?>&status=<?= urlencode($status_filter) ?>" class="pagination-link">صفحه قبل قبلی</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="knowledge_list.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $category_id ?>&author=<?= $author_id ?>&media_type=<?= urlencode($media_filter) ?>&status=<?= urlencode($status_filter) ?>" class="pagination-link <?= $page == $i ? 'active' : '' ?>"><?= convert_en_to_fa_digits($i) ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="knowledge_list.php?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&category=<?= $category_id ?>&author=<?= $author_id ?>&media_type=<?= urlencode($media_filter) ?>&status=<?= urlencode($status_filter) ?>" class="pagination-link">صفحه بعد بعدی</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>
