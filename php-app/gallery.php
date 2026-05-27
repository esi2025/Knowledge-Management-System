<?php
/**
 * سامانه مدیریت دانش فنی کارگاهی - گالری تجربیات بصری ملموس کارگاهی (نکات نظارتی مصور)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

require_login();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// ۱. بازیابی دسته‌ها جهت بارگذاری در منوی کشویی فرم ثبت اثر بصری
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// ۲. پردازش درج اثر بصری جدید به همراه آپلود فایل تصاویر یا ویدئو
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_visual'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location_category = (int)($_POST['location_category'] ?? 0);
    $tags = trim($_POST['tags'] ?? '');

    if (empty($title) || empty($description) || $location_category === 0 || !isset($_FILES['visual_file'])) {
        $error = 'جداول عنوان، رسته موضوعی، شرح نکات صحیح/غلط و فایل رسانه‌ای الزامی می‌باشند.';
    } else {
        try {
            $uploaded = handle_file_upload($_FILES['visual_file'], $user_id);
            if ($uploaded) {
                $stmt = $pdo->prepare("
                    INSERT INTO visual_gallery (user_id, title, description, location_category, tags, main_file_path, file_type, thumbnail_path)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id,
                    $title,
                    $description,
                    $location_category,
                    $tags,
                    $uploaded['file_path'],
                    $uploaded['file_type'],
                    $uploaded['thumbnail_path']
                ]);
                $success = 'نکته بصری و کارگاهی با موفقیت بارگذاری گردید و در گالری اصلی منتشر گردید.';
            } else {
                $error = 'پردازش آپلود فایل مواجه با خطا شد. حتما پسوندها و ظرفیت ۵۰ مگابایت را بررسی کنید.';
            }
        } catch (Exception $file_ex) {
            $error = 'خطا در بارگذاری رسانه‌ی بصری: ' . $file_ex->getMessage();
        }
    }
}

// ۳. پردازش درج نظر جدید روی آثار بصری
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $visual_id = (int)$_POST['visual_id'];
    $comment_text = trim($_POST['comment_text'] ?? '');

    if (empty($comment_text)) {
        $error = 'متن نظر نمی‌تواند خالی باشد.';
    } else {
        try {
            $comment_stmt = $pdo->prepare("INSERT INTO comments (visual_id, user_id, comment_text) VALUES (?, ?, ?)");
            $comment_stmt->execute([$visual_id, $user_id, $comment_text]);
            $success = 'نظر باارزش شما روی نکته بصری ثبت شد.';
        } catch (PDOException $e) {
            $error = 'خطا در ثبت نظر مهندس: ' . h($e->getMessage());
        }
    }
}

// ۴. بارگذاری کل تجارب بصری تیکت خورده به ترتیب تازگی زنده
try {
    $visuals_stmt = $pdo->query("
        SELECT vg.*, u.full_name as author, c.name as cat_name,
               (SELECT COUNT(*) FROM comments c WHERE c.visual_id = vg.id) as comments_count
        FROM visual_gallery vg
        JOIN users u ON vg.user_id = u.id
        LEFT JOIN categories c ON vg.location_category = c.id
        ORDER BY vg.created_at DESC
    ");
    $visual_items = $visuals_stmt->fetchAll();
} catch (PDOException $e) {
    die("خطا در بارگذاری گالری تجربیات بصری: " . h($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>گالری مصور تجربیات نظارتی و کارگاهی</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .comment-block-item {
            background:#f1f5f9;
            padding:10px 15px;
            border-radius:6px;
            font-size:12px;
            margin-bottom:8px;
            position:relative;
        }
        .comment-block-item .author-line {
            font-weight:bold;
            color:var(--primary-color);
            margin-bottom:4px;
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
            <li><a href="knowledge_list.php">🔍 جستجو و فیلتر دانش</a></li>
            <li><a href="qna.php">❓ تریبون پرسش و پاسخ (Q&A)</a></li>
            <li class="active"><a href="gallery.php">🖼️ گالری بصری نظارت کارگاه</a></li>
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
                <h2>گالری تجارب بصری و مقایسه نکات نظارتی صحیح و نادرست</h2>
                <p style="color: var(--text-muted); font-size: 13px; margin-top:5px;">شیرینی ملموس در بازخوانی آسیب‌ها و عیوب ساختاری ناشی از اجرای غلط میلگردها یا اتصالات پیچی اسکلت</p>
            </div>
            <div class="datetime-display">
                کانال: 📸 <strong>شات‌های زنده</strong>
            </div>
        </header>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <!-- فرم ثبت تصویر نظارتی جدید -->
        <div class="card" style="border-color:var(--secondary-color);">
            <div class="card-title" style="background-color: #f0fdfa; color: #115e59; font-size:15px; border-bottom-color: var(--secondary-color);">
                📸 به اشتراک گذاشتن شات زنده یا نکته مصور از کارگاه عمرانی
            </div>
            <div class="card-body">
                <form action="gallery.php" method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-col" style="flex:2;">
                            <div class="form-group">
                                <label for="title" class="required">عنوان نکته بصری</label>
                                <input type="text" name="title" id="title" class="form-control" placeholder="مثال: اجرای نادرست اورلپ آرماتورهای ستون بدون رعایت طول وصله در فونداسیون" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="location_category" class="required">منطقه سازه‌ای / رسته موضوعی</label>
                                <select name="location_category" id="location_category" class="form-control" required>
                                    <option value="">-- موضوع را مشخص کنید --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= h($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col" style="flex:2;">
                            <div class="form-group">
                                <label for="description" class="required">شرح تفصیلی تفاوت اجرای درست با غلط (چگونگی مهار عیب یا جزییات آیین‌نامه‌ای)</label>
                                <textarea name="description" id="description" rows="2" class="form-control" placeholder="جزییات تصویر را بنویسید؛ مثلاً عدم گونیا بودن قالب‌بندی لبه ستون موجب نشتی شیرابه سیمان شده است..." required></textarea>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="tags">برچسب‌ها (با کامای انگلیسی تفکیک شود)</label>
                                <input type="text" name="tags" id="tags" class="form-control" placeholder="مثال: آرماتوربندی, وصله, ستون_بتنی">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="required">انتخاب تصویر اصلی یا فیلم نکته (JPG, PNG, MP4 تا سقف ۵۰ مگابایت)</label>
                        <!-- محدوده درگ اند دراپ تعبیه شده -->
                        <div class="upload-zone" id="visual-drop-zone">
                            <p style="font-size:13px; color:#555;">📎 فایل تصویری یا فیلم خود را بکشید و اینجا رها کنید، یا <strong>کلیک کنید</strong> تا فایل را دستی از روی رایانه محلی بردارید.</p>
                            <input type="file" name="visual_file" id="visual_file" style="display:none;" required>
                        </div>
                    </div>

                    <div style="text-align: left;">
                        <button type="submit" name="submit_visual" class="btn btn-success" style="padding:10px 30px;">بارگذاری و قرار دادن در گالری 📸</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- گرید بصری گالری -->
        <h3 style="margin: 30px 0 15px 0; color:var(--dark-slate); font-weight:700;">📂 آلبوم شات‌های نظارتی و عیوب اجرایی</h3>

        <?php if(empty($visual_items)): ?>
            <div class="card">
                <div class="card-body" style="text-align:center; color: var(--text-muted); padding:40px;">
                    آلبومی یافت نشد. می‌توانید با سابمیت کردن اولین تصویر، گالری را استارت بزنید!
                </div>
            </div>
        <?php else: ?>
            <div class="gallery-grid">
                <?php foreach ($visual_items AS $item): 
                    $file_ext = strtolower(pathinfo($item['main_file_path'], PATHINFO_EXTENSION));
                    $is_v = str_starts_with($item['file_type'], 'video/');
                ?>
                    <div class="gallery-card">
                        <div class="gallery-media-wrapper">
                            <!-- لایت‌باکس پویا با فراخوانی Trigger -->
                            <a href="<?= h($item['main_file_path']) ?>" class="lightbox-trigger" data-type="<?= $is_v ? 'video' : 'image' ?>" data-caption="<?= h($item['title']) ?>">
                                <?php if($is_v): ?>
                                    <span style="font-size: 55px; text-decoration:none;">🎥</span>
                                <?php else: ?>
                                    <img src="<?= h($item['thumbnail_path'] ?: $item['main_file_path']) ?>" alt="تصویر گالری بصری">
                                <?php endif; ?>
                            </a>
                            <div class="gallery-tag-badge"><?= h($item['cat_name'] ?: 'رویداد زنده') ?></div>
                        </div>

                        <div style="padding: 15px;">
                            <h4 style="font-size:13.5px; color:var(--primary-color); font-weight:bold; margin-bottom:8px; line-height:1.5;"><?= h($item['title']) ?></h4>
                            <p style="font-size:12px; color:#475569; text-align:justify; margin-bottom:12px; height: 50px; overflow:hidden;">
                                <?= h($item['description']) ?>
                            </p>

                            <div style="font-size:10.5px; color:#888; border-bottom:1px solid #f1f5f9; padding-bottom:10px; display:flex; justify-content:space-between; margin-bottom:12px;">
                                <span>عکاس: <strong><?= h($item['author']) ?></strong></span>
                                <span>ثبت: <?= to_jalali($item['created_at']) ?></span>
                            </div>

                            <!-- بخش نظرات کلاینت ها روی این اثر بصری -->
                            <div style="background:#fafafa; padding:10px; border-radius:6px; margin-bottom:12px;">
                                <h5 style="font-size:11px; font-weight:bold; margin-bottom:8px; color:#555;">💬 ثبت گفتگوها مهندسین مقیم:</h5>
                                
                                <div style="max-height:150px; overflow-y:auto; margin-bottom:10px;">
                                    <?php
                                    try {
                                        $cmt_stmt = $pdo->prepare("
                                            SELECT c.*, u.full_name as author 
                                            FROM comments c 
                                            JOIN users u ON c.user_id = u.id 
                                            WHERE c.visual_id = ? 
                                            ORDER BY c.created_at ASC
                                        ");
                                        $cmt_stmt->execute([$item['id']]);
                                        $commentsList = $cmt_stmt->fetchAll();
                                    } catch (PDOException $e) {
                                        $commentsList = [];
                                    }
                                    
                                    if(empty($commentsList)): ?>
                                        <p style="font-size:10px; color:#999;">تاکنون بحث یا تفسیری برای این مورد فیدنشده.</p>
                                    <?php else:
                                        foreach ($commentsList as $cl): ?>
                                            <div class="comment-block-item">
                                                <div class="author-line"><?= h($cl['author']) ?>: <span style="font-size:9.5px; font-weight:normal; color:#777; float:left;"><?= to_jalali($cl['created_at']) ?></span></div>
                                                <div style="color:#555;"><?= h($cl['comment_text']) ?></div>
                                            </div>
                                        <?php endforeach;
                                    endif; ?>
                                </div>

                                <!-- فیلد ارسال کامنت برخط روی این شات کادر -->
                                <form action="gallery.php" method="POST" style="margin:0;">
                                    <input type="hidden" name="visual_id" value="<?= $item['id'] ?>">
                                    <div style="display:flex; gap:5px;">
                                        <input type="text" name="comment_text" class="form-control" style="font-size:11px; padding:6px 10px; flex-grow:1;" placeholder="فیدبک تفسیری شما..." required>
                                        <button type="submit" name="submit_comment" class="btn btn-primary" style="padding:4px 10px; font-size:11px;">ثبت</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- بارگذاری اسکریپت لایت باکس کلاینتی -->
<script src="script.js"></script>
</body>
</html>
