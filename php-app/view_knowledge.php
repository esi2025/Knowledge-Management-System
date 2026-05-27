<?php
/**
 * سامانه مدیریت دانش فنی کارگاهی - صفحه نمایش کامل سند دانش به همراه پیوست‌های چندرسانه‌ای
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

require_login();

$entry_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($entry_id <= 0) {
    die("شناسه سند مورد نظر نامعتبر است.");
}

// افزایش شمارنده دفعات بازدید از سند به صورت یکپارچه و بهینه
if (!isset($_SESSION['viewed_entries'][$entry_id])) {
    try {
        $update_view_stmt = $pdo->prepare("UPDATE knowledge_entries SET views = views + 1 WHERE id = ?");
        $update_view_stmt->execute([$entry_id]);
        $_SESSION['viewed_entries'][$entry_id] = true;
    } catch (PDOException $e) {
        // نادیده گرفتن خطای افزایش بازدید جهت استمرار عملکرد اصلی سیستم
    }
}

// ۱. بازیابی عمیق اطلاعات سند دانش به همراه نویسنده و نام رسته
try {
    $stmt = $pdo->prepare("
        SELECT k.*, u.full_name as author, c.name as category_name, u.role as author_role
        FROM knowledge_entries k
        JOIN users u ON k.user_id = u.id
        LEFT JOIN categories c ON k.category_id = c.id
        WHERE k.id = ?
    ");
    $stmt->execute([$entry_id]);
    $entry = $stmt->fetch();

    if (!$entry) {
        die("<div style='direction: rtl; font-family: Tahoma; text-align: center; margin-top: 50px;'><h2>سند مورد نظر یافت نشد یا حذف گردیده است.</h2><a href='index.php'>بازگشت</a></div>");
    }

    // بررسی سیاست امنیتی حریم داده: اگر سند در وضعیت پیش‌نویس باشد و کاربر جاری، نویسنده یا ادمین/کارشناس نباشد، مجاز به دیدن نیست!
    if ($entry['status'] !== 'approved' && $_SESSION['user_role'] === 'contributor' && $entry['user_id'] !== $_SESSION['user_id']) {
        die("<div style='direction: rtl; font-family: Tahoma; text-align: center; margin-top: 50px; background:#fff3cd; padding:30px; border-radius:8px;'><h2>سند انتخابی هنوز در وضعیت پیش‌نویس است و مورد تایید علمی کارشناسان دپارتمان فنی قرار نگرفته است.</h2><p>فقط ثبت‌کننده اثر و تیم نظارتی کلیدی به محتوای این پیش‌نویس کماکان دسترسی خواهند داشت.</p><a href='index.php'>بازگشت به پورتال</a></div>");
    }

    // ۲. استخراج لیست پیوست‌های مالتی‌مدیای مرتبط با این تجربه
    $media_stmt = $pdo->prepare("SELECT * FROM media WHERE entry_type = 'knowledge' AND entry_id = ? ORDER BY id ASC");
    $media_stmt->execute([$entry_id]);
    $attachments = $media_stmt->fetchAll();

} catch (PDOException $e) {
    die("خطا در استخراج اطلاعات سند دانش: " . h($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سند دانش شماره <?= convert_en_to_fa_digits($entry['id']) ?> - <?= h($entry['title']) ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .knowledge-meta-table {
            background-color: #f8fafc;
            border-bottom: 2px solid var(--primary-color);
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 25px;
            font-size: 13.5px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .meta-label {
            font-weight: bold;
            color: var(--dark-slate);
        }
        .meta-value {
            color: #475569;
        }
        .section-header-custom {
            border-right: 4px solid var(--primary-color);
            padding-right: 12px;
            margin-bottom: 12px;
            font-size: 15px;
            font-weight: 700;
        }
        .p-block {
            background-color: #fafbfd;
            border: 1px solid #eef2f6;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            text-align: justify;
            font-size: 14.5px;
            line-height: 1.8;
            color: #334155;
            white-space: pre-line; /* حفظ خطوط جدید تایپ شده به زیبایی */
        }
    </style>
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
                <h2>سند فنی شماره <?= convert_en_to_fa_digits($entry['id']) ?></h2>
                <p style="color: var(--text-muted); font-size: 13px; margin-top:5px;">مشاهده، ارزیابی، بازخوانی راهکارهای کارگاهی و دانلود فایلهای پیوست منضم به سند</p>
            </div>
            <div style="display:flex; gap:8px;">
                <a href="knowledge_list.php" class="btn btn-secondary">بازگشت به آرشیو تجربه عمران ↩️</a>
            </div>
        </header>

        <div class="card">
            <div class="card-title" style="font-size:18px; color: var(--primary-color); display:flex; justify-content:space-between; align-items:center;">
                <span>🎯 <?= h($entry['title']) ?></span>
                <span><?= get_status_html($entry['status']) ?></span>
            </div>
            <div class="card-body">
                
                <!-- جدول یا باکس متا دیتای سند -->
                <div class="knowledge-meta-table">
                    <div class="meta-item">
                        <span class="meta-label">📂 رسته تخصصی:</span>
                        <span class="meta-value"><?= h($entry['category_name'] ?: 'سایر') ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">✍️ نویسنده و ناظر:</span>
                        <span class="meta-value"><?= h($entry['author']) ?> (<?= get_role_name($entry['author_role']) ?>)</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">📅 تاریخ وقوع چالش:</span>
                        <span class="meta-value"><?= to_jalali($entry['date_occurred']) ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">👁️ نرخ مطالعه:</span>
                        <span class="meta-value"><?= convert_en_to_fa_digits($entry['views']) ?> بار مراجعه</span>
                    </div>
                </div>

                <!-- شرح مسئله یا چالش -->
                <div class="section-header-custom" style="color: var(--danger-color);">🚨 شرح دقیق چالش مواجه‌شده در عملیات ساختمانی:</div>
                <div class="p-block" style="border-right: 3px solid var(--danger-color);">
                    <?= h($entry['problem']) ?>
                </div>

                <!-- شرح راهکار اجرایی اعمال شده -->
                <div class="section-header-custom" style="color: var(--success-color);">✅ راهکارهای نظارتی آیین‌نامه‌ای اعمال‌شده جهت مهار خطر:</div>
                <div class="p-block" style="border-right: 3px solid var(--success-color); background-color: #fcfdfd;">
                    <?= h($entry['solution']) ?>
                </div>

                <!-- نتیجه‌گیری و مزیت برآورد شده -->
                <?php if (!empty($entry['result'])): ?>
                    <div class="section-header-custom" style="color: var(--primary-color);">📈 نتیجه نهایی و دستاوردهای فنی - اقتصادی حاصله:</div>
                    <div class="p-block" style="border-right: 3px solid var(--primary-color);">
                        <?= h($entry['result']) ?>
                    </div>
                <?php endif; ?>

                <!-- کلمات کلیدی برای جستارهای بعدی -->
                <?php if (!empty($entry['keywords'])): ?>
                    <div style="margin-top: 20px; border-top:1px solid #eee; padding-top:15px; display:flex; gap:8px; align-items:center;">
                        <span style="font-weight:bold; font-size:13px; color:#555;">🏷️ هشتگ‌ها و برچسب‌های متناظر:</span>
                        <?php foreach (explode(',', $entry['keywords']) as $kw): ?>
                            <span style="font-size:11px; background:#e2e8f0; color:#334155; padding:3px 9px; border-radius:4px;">#<?= h(trim($kw)) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- بخش شگفت‌انگیز نمایش و دانلود فایل‌های مالتی‌مدیای ضمیمه آفلاین -->
                <div class="card" style="margin-top: 35px; border-color: var(--secondary-color);">
                    <div class="card-title" style="background-color: #f0fdfa; color: #115e59; font-size:14px;">
                        📎 گالری جامع فایل‌ها و ضمیمه‌های چندرسانه‌ای معتبر منضم به سند (تصویر، ویدئو، وویس کارگاه)
                    </div>
                    <div class="card-body">
                        <?php if (empty($attachments)): ?>
                            <p style="text-align:center; color: var(--text-muted); font-size:13px; padding:20px;">هیچ تصویر، ویدئو یا ترک صوتی کمکی برای این سند ضمیمه نشده است.</p>
                        <?php else: ?>
                            <div class="media-preview-container">
                                <?php foreach ($attachments as $att): 
                                    $file_ext = strtolower(pathinfo($att['file_path'], PATHINFO_EXTENSION));
                                    $is_image = str_starts_with($att['file_type'], 'image/');
                                    $is_video = str_starts_with($att['file_type'], 'video/');
                                    $is_audio = str_starts_with($att['file_type'], 'audio/');
                                ?>
                                    <div class="media-item">
                                        <div style="height: 140px; background:#e2e8f0; border-radius:4px; display:flex; align-items:center; justify-content:center; overflow:hidden; margin-bottom:10px;">
                                            <?php if ($is_image): ?>
                                                <!-- لایت باکس هماهنگ شده با کلیک -->
                                                <a href="<?= h($att['file_path']) ?>" class="lightbox-trigger" data-type="image" data-caption="<?= h($att['description']) ?>">
                                                    <img src="<?= h($att['thumbnail_path'] ?: $att['file_path']) ?>" alt="تصویر پیوست" style="width: 100%; height: 140px; object-fit: cover;">
                                                </a>
                                            <?php elseif ($is_video): ?>
                                                <a href="<?= h($att['file_path']) ?>" class="lightbox-trigger" data-type="video" data-caption="<?= h($att['description']) ?>" style="font-size: 50px; text-decoration:none;">🎥</a>
                                            <?php elseif ($is_audio): ?>
                                                <span style="font-size: 50px;">🎵</span>
                                            <?php else: ?>
                                                <span style="font-size: 50px;">📄</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div style="font-size: 12px; margin-bottom:8px; height: 36px; overflow:hidden; text-align:justify; color: #475569;" title="<?= h($att['description']) ?>">
                                            <strong>توضیح:</strong> <?= h($att['description']) ?>
                                        </div>
                                        
                                        <!-- خروجی پلیرها صوتی بومی محلی و اطلاعات دانلود فایل -->
                                        <?php if ($is_audio): ?>
                                            <audio src="<?= h($att['file_path']) ?>" controls style="width:100%; margin-bottom:10px;"></audio>
                                        <?php endif; ?>
                                        
                                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:11px; color:#777; border-top: 1px solid #eee; padding-top:8px;">
                                            <span>سایز فایل: <?= format_bytes($att['file_size']) ?></span>
                                            <a href="<?= h($att['file_path']) ?>" download class="btn btn-secondary" style="padding: 2px 6px; font-size:10px;">📥 دانلود مستقیم</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- دکمه‌های سریع ممیزی مستقیم برای مدیر/کارشناس که از آیدی سند عبور کردند -->
                <?php if (in_array($_SESSION['user_role'], ['admin', 'expert']) && $entry['status'] === 'draft'): ?>
                    <div style="margin-top:25px; background: #fff8e1; border: 1px solid #ffe082; padding:20px; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong style="color:#b78103; font-size:14px;">🛂 بخش بازنگری و ممیزی دپارتمان مهندسی:</strong>
                            <p style="font-size:12px; color: #7d5912; margin-top:5px;">شما دسترسی ممیزی این مدرک را دارید. لطفاً آن را به دقت مطالعه کرده و جهت تایید تصمیم بگیرید.</p>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <form action="dashboard.php" method="POST" style="margin:0;">
                                <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-success" style="padding: 8px 20px; font-size:13px;">تأیید علمی و انتشار سند ✅</button>
                            </form>
                            <form action="dashboard.php" method="POST" style="margin:0;">
                                <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                                <button type="submit" name="action" value="reject" class="btn btn-danger" style="padding: 8px 20px; font-size:13px;">رد ممیزی و ابطال تجربه ❌</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </main>
</div>

<!-- اسکریپت لایت باکس سراسری -->
<script src="script.js"></script>
</body>
</html>
