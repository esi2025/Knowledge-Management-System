<?php
/**
 * سامانه مدیریت دانش فنی کارگاهی - داشبورد آمارها و نمودارهای محلی و تأییدیه‌ها
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

require_login();

$user_role = $_SESSION['user_role'];

// ۱. مدیریت تایید یا رد سوابق دانش توسط کارشناس / ادمین جهت جریان چرخه‌کار
if (in_array($user_role, ['admin', 'expert']) && isset($_POST['action']) && isset($_POST['entry_id'])) {
    $entry_id = (int)$_POST['entry_id'];
    $action = $_POST['action']; // 'approve' یا 'reject'
    $new_status = $action === 'approve' ? 'approved' : 'rejected';

    try {
        $update_stmt = $pdo->prepare("UPDATE knowledge_entries SET status = ? WHERE id = ?");
        $update_stmt->execute([$new_status, $entry_id]);
        $success_msg = "وضعیت سند دانش شماره " . convert_en_to_fa_digits($entry_id) . " با موفقیت به " . ($action === 'approve' ? 'تایید شده' : 'رد شده') . " تغییر یافت.";
    } catch (PDOException $e) {
        $error_msg = "خطا در به‌روزرسانی وضعیت سند: " . h($e->getMessage());
    }
}

// ۲. محاسبات آماری داشبورد برای سنجش تعالی سازمانی
try {
    // آمار کل فایلهای تایید شده
    $total_approved = $pdo->query("SELECT COUNT(*) FROM knowledge_entries WHERE status = 'approved'")->fetchColumn();
    // تعداد دفعات کل بازدیدها
    $total_views = $pdo->query("SELECT SUM(views) FROM knowledge_entries WHERE status = 'approved'")->fetchColumn() ?: 0;
    // تعداد سوالات فنی بی پاسخ
    $pending_questions = $pdo->query("SELECT COUNT(*) FROM questions WHERE status = 'open'")->fetchColumn();
    // تعداد نکات بصری کارگاه
    $total_visuals = $pdo->query("SELECT COUNT(*) FROM visual_gallery")->fetchColumn();

    // برترین مشارکت کنندگان فنی (تا ۳ نفر اول)
    $stmt_top_users = $pdo->query("
        SELECT u.full_name, COUNT(k.id) as cnt 
        FROM users u 
        JOIN knowledge_entries k ON u.id = k.user_id 
        WHERE k.status = 'approved' 
        GROUP BY u.id 
        ORDER BY cnt DESC 
        LIMIT 3
    ");
    $top_contributors = $stmt_top_users->fetchAll();

    // پربازدیدترین تجارب مهندسی
    $stmt_top_views = $pdo->query("
        SELECT k.id, k.title, k.views, c.name as cat_name 
        FROM knowledge_entries k
        LEFT JOIN categories c ON k.category_id = c.id
        WHERE k.status = 'approved' 
        ORDER BY k.views DESC 
        LIMIT 4
    ");
    $popular_entries = $stmt_top_views->fetchAll();

    // توزیع فراوانی سوابق در دسته‌بندی‌ها جهت رسم نمودار میله‌ای فارسی آفلاین
    $stmt_chart = $pdo->query("
        SELECT c.name as cat_name, COUNT(k.id) as cnt
        FROM categories c
        LEFT JOIN knowledge_entries k ON c.id = k.category_id AND k.status = 'approved'
        GROUP BY c.id
        ORDER BY cnt DESC
    ");
    $chart_data = $stmt_chart->fetchAll();

    // تعیین ماکزیمم مقدار جهت نسبت‌دهی طول میله‌ها
    $max_chart_val = 1;
    foreach ($chart_data as $data) {
        if ($data['cnt'] > $max_chart_val) {
            $max_chart_val = $data['cnt'];
        }
    }

    // ۳. بازیابی پیش‌نویس‌های در انتظار تایید برای ادمین/کارشناس
    $pending_approvals = [];
    if (in_array($user_role, ['admin', 'expert'])) {
        $stmt_pending = $pdo->query("
            SELECT k.*, u.full_name as author, c.name as category_name 
            FROM knowledge_entries k
            JOIN users u ON k.user_id = u.id
            LEFT JOIN categories c ON k.category_id = c.id
            WHERE k.status = 'draft'
            ORDER BY k.created_at ASC
        ");
        $pending_approvals = $stmt_pending->fetchAll();
    }
} catch (PDOException $e) {
    die("خطای بحرانی در کوئری‌های پیشخوان سیستم: " . h($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد آماری و تایید تجارب</title>
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
            <li class="active"><a href="dashboard.php">📈 داشبورد و آمار تجارب</a></li>
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
                <h2>میز کار و داشبورد تحلیلی مدیریت دانش</h2>
                <p style="color: var(--text-muted); font-size: 13px; margin-top:5px;">گزارش شاخص‌های کلیدی عملکرد و ارزیابی مشارکت دفاتر ناظرین مقیم</p>
            </div>
            <div class="datetime-display">
                📅 تقویم: <strong><?= to_jalali(date('Y-m-d')) ?></strong>
            </div>
        </header>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?= h($success_msg) ?></div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger"><?= h($error_msg) ?></div>
        <?php endif; ?>

        <!-- بخش کارت‌های نشانگر عملکرد کل سیستم -->
        <div class="stats-container">
            <div class="stat-card">
                <div>
                    <div class="stat-value"><?= convert_en_to_fa_digits($total_approved) ?></div>
                    <div class="stat-label">تجربه مصوب کارگاهی</div>
                </div>
                <div class="stat-icon">🎓</div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-value"><?= convert_en_to_fa_digits($total_views) ?></div>
                    <div class="stat-label">کل بازدید همکاران</div>
                </div>
                <div class="stat-icon">👁️</div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-value"><?= convert_en_to_fa_digits($pending_questions) ?></div>
                    <div class="stat-label">پرسش‌های فنی باز</div>
                </div>
                <div class="stat-icon">❓</div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-value"><?= convert_en_to_fa_digits($total_visuals) ?></div>
                    <div class="stat-label">نکات گالری بصری</div>
                </div>
                <div class="stat-icon">📸</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- ستون اول: نمودار و جداول پربازدید -->
            <div>
                <!-- نمودار میله‌ای شیک آفلاین بدون نیازمندی به لود فایل خارجی CDN -->
                <div class="card">
                    <div class="card-title">📊 فراوانی مستندات ثبت‌شده در دسته‌بندی‌های ۸ گانه</div>
                    <div class="card-body">
                        <div class="bar-chart-container">
                            <?php foreach ($chart_data as $data): 
                                $percentage = ($data['cnt'] / $max_chart_val) * 100;
                            ?>
                                <div class="chart-row">
                                    <div class="chart-label" title="<?= h($data['cat_name']) ?>"><?= h($data['cat_name']) ?></div>
                                    <div class="chart-bar-container">
                                        <div class="chart-bar-fill" style="width: <?= $percentage ?>%;"></div>
                                    </div>
                                    <div class="chart-value"><?= convert_en_to_fa_digits($data['cnt']) ?> تجربه</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- سوابق پربازدید و کلیدی -->
                <div class="card">
                    <div class="card-title">🔥 تجارب با بالاترین ارجاع و مطالعه همکاران</div>
                    <div class="card-body" style="padding:0;">
                        <table style="margin:0;">
                            <thead>
                                <tr>
                                    <th>شماره</th>
                                    <th>عنوان کلیدی تجربه</th>
                                    <th>رسته سازه</th>
                                    <th>نرخ ارجاع</th>
                                    <th>مشاهده</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $idx = 1; foreach ($popular_entries as $pop): ?>
                                    <tr>
                                        <td><?= convert_en_to_fa_digits($idx++) ?></td>
                                        <td style="font-weight:bold;"><?= h($pop['title']) ?></td>
                                        <td><span style="background: #eff6ff; padding:2px 6px; border-radius:4px; font-size:11px;"><?= h($pop['cat_name']) ?></span></td>
                                        <td><strong><?= convert_en_to_fa_digits($pop['views']) ?></strong> بار</td>
                                        <td><a href="view_knowledge.php?id=<?= $pop['id'] ?>" style="color:var(--primary-color);">مطالعه ↩️</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ستون دوم: پنل مشارکت کنندگان برتر و اطلاعات کاربران -->
            <div>
                <div class="card">
                    <div class="card-title">🏆 فعال‌ترین مهندسان ثبت‌کننده (Top Contributors)</div>
                    <div class="card-body">
                        <ul style="list-style:none; display:flex; flex-direction:column; gap:15px;">
                            <?php $medal = ['🥇', '🥈', '🥉']; $i=0; foreach ($top_contributors as $tc): ?>
                                <li style="display:flex; justify-content:space-between; align-items:center; padding-bottom:10px; border-bottom:1px dashed var(--border-color);">
                                    <span><?= isset($medal[$i]) ? $medal[$i++] : '•' ?> <strong><?= h($tc['full_name']) ?></strong></span>
                                    <span style="background:var(--secondary-color); color:#fff; padding:2px 8px; border-radius:15px; font-size:12px; font-weight:bold;">
                                        <?= convert_en_to_fa_digits($tc['cnt']) ?> تجربه مصوب
                                    </span>
                                </li>
                            <?php endforeach; if(empty($top_contributors)): ?>
                                <li style="text-align:center; color:var(--text-muted); font-size:13px;">هنوز مشارکتی گزارش نشده است.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="card" style="background: #fffdf5; border: 1px solid #fcf3c6;">
                    <div class="card-title" style="background:#fffcf0; border-bottom-color:#fcf3c6; color:#9a6b07;">⚖️ حاکمیت داده و تضمین کیفیت تجارب</div>
                    <div class="card-body" style="font-size:12.5px; color:#7d5912; line-height:1.7;">
                        سوابق ارسالی توسط مهندسان کارگاهی بلافاصله به عنوان <strong>پیش‌نویس</strong> ذخیره شده و پس از ممیزی کامل توسط <strong>کارشناسان ارشد دپارتمان فنی</strong> در چرخه تایید قرار خواهند گرفت تا از صحت محاسبات علمی و نکات آیین‌نامه‌ای ابرازشده اطمینان بدست آید.
                    </div>
                </div>
            </div>
        </div>

        <!-- بخش ویژه ممیزی سوابق دانش کتبی (فقط مخصوص ادمین و اکسپرت سامانه) -->
        <?php if (in_array($user_role, ['admin', 'expert'])): ?>
            <div class="card" style="margin-top:20px; border:2px solid var(--secondary-color);">
                <div class="card-title" style="background-color: #f0fdfa; color: #115e59; font-size:16px;">
                    <span>📥 کارتابل ممیزی و تایید نهایی سوابق دانش معلق (در انتظار بررسی)</span>
                    <span style="background:var(--secondary-color); color:white; padding:3px 10px; border-radius:20px; font-size:12px;"><?= convert_en_to_fa_digits(count($pending_approvals)) ?> پیش‌نویس جدید</span>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($pending_approvals)): ?>
                        <div style="padding:30px; text-align:center; color: var(--text-muted); font-size:14px;">
                            🎉 عالی! هیچ تجربه یا سند فنی معلقی در انتظار ممیزی وجود ندارد.
                        </div>
                    <?php else: ?>
                        <table style="margin:0;">
                            <thead>
                                <tr>
                                    <th>جزییات ثبت</th>
                                    <th>عنوان گزارش</th>
                                    <th>رسته فنی</th>
                                    <th>طراح چالش و نویسنده</th>
                                    <th style="width: 250px; text-align:center;">ممیزی و تصمیم‌گیری نهایی</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_approvals as $pa): ?>
                                    <tr>
                                        <td><?= to_jalali($pa['created_at']) ?></td>
                                        <td style="font-weight:bold;"><?= h($pa['title']) ?></td>
                                        <td><?= h($pa['category_name']) ?></td>
                                        <td><?= h($pa['author']) ?></td>
                                        <td>
                                            <div style="display:flex; gap:8px; justify-content:center;">
                                                <a href="view_knowledge.php?id=<?= $pa['id'] ?>" class="btn btn-secondary" style="padding:5px 8px; font-size:12px;">بررسی فنی 🔍</a>
                                                <form action="dashboard.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="entry_id" value="<?= $pa['id'] ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-success" style="padding:5px 8px; font-size:12px;">تایید نهایی ✅</button>
                                                </form>
                                                <form action="dashboard.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="entry_id" value="<?= $pa['id'] ?>">
                                                    <button type="submit" name="action" value="reject" class="btn btn-danger" style="padding:5px 8px; font-size:12px;">رد ممیزی ❌</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>
