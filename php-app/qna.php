<?php
/**
 * سامانه مدیریت دانش فنی کارگاهی - سیستم پرسش و پاسخ کارگاهی ناظران و کارشناسان ارشد (Q&A)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

require_login();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// ۱. مدیریت درج پرسش جدید توسط کاربران
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_question'])) {
    $title = trim($_POST['title'] ?? '');
    $question_text = trim($_POST['question_text'] ?? '');
    $priority = $_POST['priority'] ?? 'normal';

    if (empty($title) || empty($question_text)) {
        $error = 'عنوان سوال و شرح چالش با بن‌بست فنی را پر نمایید.';
    } elseif (!in_array($priority, ['normal', 'urgent', 'critical'])) {
        $error = 'درجه اولویت وارد شده نامعتبر است.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO questions (user_id, title, question_text, priority, status) VALUES (?, ?, ?, ?, 'open')");
            $stmt->execute([$user_id, $title, $question_text, $priority]);
            $success = 'پرسش فنی شما با موفقیت ثبت شد و در معرض دید استادان و کارشناسان ارشد دپارتمان قرار گرفت.';
        } catch (PDOException $e) {
            $error = 'خطا در ثبت پرسش: ' . h($e->getMessage());
        }
    }
}

// ۲. مدیریت درج پاسخ جدید برای سوالات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answer'])) {
    $question_id = (int)$_POST['question_id'];
    $answer_text = trim($_POST['answer_text'] ?? '');

    if (empty($answer_text)) {
        $error = 'متن پاسخ نمی‌تواند خالی باشد.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO answers (question_id, user_id, answer_text, is_accepted) VALUES (?, ?, ?, 0)");
            $stmt->execute([$question_id, $user_id, $answer_text]);
            $success = 'پاسخ و مشورت تخصصی شما با موفقیت به ثبت رسید.';
        } catch (PDOException $e) {
            $error = 'خطا در ثبت پاسخ: ' . h($e->getMessage());
        }
    }
}

// ۳. تعیین یک پاسخ به عنوان "راهکار پذیرفته شده و نهایی" توسط طراح سوال
if (isset($_GET['accept_answer']) && isset($_GET['q_id'])) {
    $answer_id = (int)$_GET['accept_answer'];
    $q_id = (int)$_GET['q_id'];

    try {
        // ابتدا تایید مالکیت سوال (فقط طراح سوال اجازه تایید پذیرش پاسخ را دارد)
        $q_check = $pdo->prepare("SELECT user_id FROM questions WHERE id = ?");
        $q_check->execute([$q_id]);
        $owner_id = $q_check->fetchColumn();

        if ($owner_id == $user_id || $_SESSION['user_role'] === 'admin') {
            // آغاز تراکنش دیتابیس
            $pdo->beginTransaction();
            
            // صفر کردن تگ پذیرش سایر پاسخهای سوال مربوطه
            $reset_stmt = $pdo->prepare("UPDATE answers SET is_accepted = 0 WHERE question_id = ?");
            $reset_stmt->execute([$q_id]);

            // یک کردن پاسخ انتخاب شده فعلی
            $accept_stmt = $pdo->prepare("UPDATE answers SET is_accepted = 1 WHERE id = ?");
            $accept_stmt->execute([$answer_id]);

            // تغییر وضعیت سوال به حل شده (resolved)
            $status_stmt = $pdo->prepare("UPDATE questions SET status = 'resolved' WHERE id = ?");
            $status_stmt->execute([$q_id]);

            $pdo->commit();
            $success = 'پاسخ انحصاری با موفقیت به عنوان راهکار نهایی تایید و ثبت کارنامه شد.';
        } else {
            $error = 'شما مجوز انتخاب پاسخ پذیرفته شده بابت این سوال را ندارید.';
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'خطا در به روز رسانی پاسخ پذیرفته شده: ' . h($e->getMessage());
    }
}

// ۴. بازیابی تمامی سوالات فنی به همراه نویسنده و تعداد کل پاسخ‌های ثبت‌شده برای هریک
try {
    $questions_stmt = $pdo->query("
        SELECT q.*, u.full_name as author, u.role as author_role,
               (SELECT COUNT(*) FROM answers a WHERE a.question_id = q.id) as answers_count
        FROM questions q
        JOIN users u ON q.user_id = u.id
        ORDER BY q.priority = 'critical' DESC, q.priority = 'urgent' DESC, q.created_at DESC
    ");
    $questions = $questions_stmt->fetchAll();
} catch (PDOException $e) {
    die("خطا در بارگذاری سوالات فروم: " . h($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تریبون پرسش و پاسخ کارگاهی (Q&A)</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .reply-item-custom {
            background:#fcfdfe;
            border:1px solid #e1e8ed;
            padding:15px;
            border-radius:6px;
            margin-bottom:10px;
            position:relative;
        }
        .reply-item-custom.is-accepted-border {
            border: 2px solid var(--success-color);
            background:#f4fdf7;
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
            <li class="active"><a href="qna.php">❓ تریبون پرسش و پاسخ (Q&A)</a></li>
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
                <h2>تریبون پرسش و پاسخ و مشاوره با متخصصین ژئوتکنیک و سازه</h2>
                <p style="color: var(--text-muted); font-size: 13px; margin-top:5px;">رفع سریع بن‌بست‌های اجرایی کارگاه‌ها با کسب نظرات ناظران و کارشناسان ارشد دفاتر مرکزی</p>
            </div>
            <div class="datetime-display">
                مشاوران آنلاین: <strong>آفلاین بومی شبکه</strong>
            </div>
        </header>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <!-- بخش اول: شبیه‌ساز طرح سوال جدید -->
        <div class="card" style="border-color: var(--accent-color);">
            <div class="card-title" style="background-color: #fffdf5; color: #856404; font-size:15px; border-bottom-color: var(--accent-color);">
                🙋‍♂️ طرح سوال فنی یا چالش مفرط مهندسی جدید در کارگاه
            </div>
            <div class="card-body">
                <form action="qna.php" method="POST">
                    <div class="form-row">
                        <div class="form-col" style="flex:2;">
                            <div class="form-group">
                                <label for="title" class="required">عنوان مختصر سوال فنی</label>
                                <input type="text" name="title" id="title" class="form-control" placeholder="مثال: لرزش کلاف سقف سوله‌های صنعتی در زمان وزش باد شدید چه‌طور برطرف می‌شود؟" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="priority">درجه اهمیت اضطرار کارگاهی</label>
                                <select name="priority" id="priority" class="form-control">
                                    <option value="normal">عادی (پروژه‌های روال عادی)</option>
                                    <option value="urgent">فوری ⚠️ (آسیب موضعی شالوده)</option>
                                    <option value="critical">بحرانی 🚨 (خطر ریزش دیواره در ۲۴ ساعت)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="question_text" class="required">شرح کامل سوال و وضع زنده اعضای سازه‌ای</label>
                        <textarea name="question_text" id="question_text" rows="3" class="form-control" placeholder="توضیحات مفصل در خصوص نوع بتن، نتایج جک فشاری، ترک‌های دیده‌شده و غیره بنویسید..." required></textarea>
                    </div>

                    <div style="text-align: left;">
                        <button type="submit" name="submit_question" class="btn btn-warning" style="color:#111; font-weight:bold; text-shadow:none;">طرح سوال عمومی در پانل 🗳️</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- تیکت ها و جریان گفتگوها -->
        <h3 style="margin: 25px 0 15px 0; color: var(--dark-slate); font-weight:700;">📌 جریان سوالات فنی همکاران در شبکه داخلی</h3>

        <?php if(empty($questions)): ?>
            <div class="card">
                <div class="card-body" style="text-align:center; color: var(--text-muted); padding:40px;">
                    تاکنون هیچ پرسش فنی جدیدی روی سامانه طرح نگردیده است.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($questions as $q): 
                // بازیابی پاسخ‌های ثبت‌شده برای هر سوال به فلو
                try {
                    $ans_stmt = $pdo->prepare("
                        SELECT a.*, u.full_name as replier, u.role as replier_role 
                        FROM answers a 
                        JOIN users u ON a.user_id = u.id 
                        WHERE a.question_id = ? 
                        ORDER BY a.is_accepted DESC, a.created_at ASC
                    ");
                    $ans_stmt->execute([$q['id']]);
                    $answers = $ans_stmt->fetchAll();
                } catch (PDOException $e) {
                    $answers = [];
                }
            ?>
                <!-- تیکت فروم سوال -->
                <div class="qna-ticket <?= $q['status'] === 'resolved' ? 'accepted-indicator' : '' ?>">
                    <?php if ($q['status'] === 'resolved'): ?>
                        <span class="accepted-mark" style="font-size:12px; background:var(--success-color); color:white; padding:4px 10px; border-radius:15px;">✓ چالش مهارشده و حل‌شده</span>
                    <?php endif; ?>

                    <div style="display: flex; gap:10px; align-items: center; margin-bottom:10px;">
                        <?= get_priority_html($q['priority']) ?>
                        <h4 style="font-size: 15px; color: var(--primary-color); font-weight:700;"><?= h($q['title']) ?></h4>
                    </div>

                    <p style="font-size:13.5px; color:#475569; text-align:justify; margin-bottom:15px; white-space:pre-line;">
                        <?= h($q['question_text']) ?>
                    </p>

                    <div style="font-size:11px; color:#888; border-bottom:1px solid #eee; padding-bottom:10px; display:flex; justify-content:space-between; margin-bottom:15px;">
                        <span>طراح چالش: <strong><?= h($q['author']) ?></strong> (<?= get_role_name($q['author_role']) ?>)</span>
                        <span>تاریخ درج: <?= to_jalali($q['created_at']) ?></span>
                    </div>

                    <!-- بخش پاسخ های ثبت شده برای پرسش مورد نظر -->
                    <div style="margin-right:25px; margin-bottom:15px;">
                        <h5 style="font-size:12.5px; font-weight:bold; color:var(--dark-slate); margin-bottom:10px;">📢 نظریه‌های مشورتی ابراز شده مهندسان ارشد و ناظران دیگر:</h5>
                        
                        <?php if(empty($answers)): ?>
                            <p style="font-size:12px; color:var(--text-muted); margin-bottom:15px; background: #fafafa; padding:10px;">هنوز پاسخی ارسال نشده است. اولین نفری باشید که راهکار را پیشنهاد می‌دهد!</p>
                        <?php else: ?>
                            <?php foreach ($answers as $ans): ?>
                                <div class="reply-item-custom <?= $ans['is_accepted'] == 1 ? 'is-accepted-border' : '' ?>">
                                    <?php if($ans['is_accepted'] == 1): ?>
                                        <div style="position:absolute; top:10px; left:10px; color:var(--success-color); font-size:11px; font-weight:bold; background:#e8f5e9; padding:2px 8px; border-radius:4px; border:1px solid var(--success-color)">
                                            🥇 راهکار برگزیده و مصوب و تایید شده ناظر عالی
                                        </div>
                                    <?php endif; ?>
                                    
                                    <p style="font-size:13px; color:#333; text-align:justify; padding-bottom:10px; border-bottom:1px dashed #eee; line-height:1.7;">
                                        <?= h($ans['answer_text']) ?>
                                    </p>
                                    
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px; font-size:11px;">
                                        <span style="color:#555;">پاسخ‌دهنده: <strong><?= h($ans['replier']) ?></strong> (<span style="color:var(--primary-color)"><?= get_role_name($ans['replier_role']) ?></span>)</span>
                                        <div style="display:flex; gap:10px; align-items:center;">
                                            <span>ثبت شده در: <?= to_jalali($ans['created_at']) ?></span>
                                            
                                            <!-- دکمه تایید پاسخ اگر کاربر جاری، نویسنده سوال باشد و تا قبل این تایید نکرده باشد -->
                                            <?php if ($q['user_id'] == $user_id && $ans['is_accepted'] == 0 && $q['status'] !== 'resolved'): ?>
                                                <a href="qna.php?accept_answer=<?= $ans['id'] ?>&q_id=<?= $q['id'] ?>" class="btn btn-success" style="padding: 2px 8px; font-size:10px;">✓ انتخاب به عنوان راهکار برگزیده کارگاهی</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- فرم درج نظر تفصیلی جدید برای سوال -->
                    <div style="background:#f8fafc; padding:15px; border-radius:6px; border:1px solid var(--border-color); margin-right:25px;">
                        <form action="qna.php" method="POST" style="margin:0;">
                            <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                            <div style="display:flex; gap:10px;">
                                <div style="flex-grow:1;">
                                    <input type="text" name="answer_text" class="form-control" style="font-size:12.5px; padding:8px 12px;" placeholder="مشاوره کارگاهی یا جواب تخصصی مکتوب بر اساس تجربه را اینجا به اشتراک بگذارید..." required>
                                </div>
                                <button type="submit" name="submit_answer" class="btn btn-primary" style="padding: 8px 15px; font-size:12px;">ثبت پاسخ ↩️</button>
                            </div>
                        </form>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </main>
</div>

</body>
</html>
