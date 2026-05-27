-- --------------------------------------------------------
-- سامانه مدیریت دانش فنی و کارگاهی مهندسی عمران و معماری
-- ساختار پایگاه‌داده MySQL (آفلاین)
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `civil_knowledge_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_persian_ci;
USE `civil_knowledge_db`;

-- 1. جدول کاربران (admin, expert, contributor)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'expert', 'contributor') NOT NULL DEFAULT 'contributor',
  `password_must_change` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- 2. جدول دسته‌بندی‌های دانش عمرانی
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `parent_id` INT DEFAULT NULL,
  FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- 3. جدول پست‌های دانش (تجربه، مشکل و راهکار)
CREATE TABLE IF NOT EXISTS `knowledge_entries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `problem` TEXT NOT NULL,
  `solution` TEXT NOT NULL,
  `result` TEXT DEFAULT NULL,
  `category_id` INT DEFAULT NULL,
  `keywords` VARCHAR(255) DEFAULT NULL,
  `date_occurred` DATE DEFAULT NULL,
  `status` ENUM('draft', 'approved', 'rejected') NOT NULL DEFAULT 'draft',
  `views` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- 4. جدول رسانه‌ها (عکس، فیلم، صوت) برای دانش، سوالات و گالری تجارب بصری
CREATE TABLE IF NOT EXISTS `media` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `entry_type` ENUM('knowledge', 'question', 'visual') NOT NULL,
  `entry_id` INT NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(50) NOT NULL, -- e.g., 'image/png', 'video/mp4', 'audio/mp3'
  `file_size` INT NOT NULL DEFAULT 0,
  `thumbnail_path` VARCHAR(255) DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- 5. جدول سوالات (سیستم پرسش و پاسخ کارگاهی)
CREATE TABLE IF NOT EXISTS `questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `question_text` TEXT NOT NULL,
  `priority` ENUM('normal', 'urgent', 'critical') NOT NULL DEFAULT 'normal',
  `status` ENUM('open', 'resolved') NOT NULL DEFAULT 'open',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- 6. جدول پاسخ‌ها به سوالات
CREATE TABLE IF NOT EXISTS `answers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `question_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `answer_text` TEXT NOT NULL,
  `is_accepted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- 7. جدول گالری تجارب بصری (نکات نظارتی عکس‌محور)
CREATE TABLE IF NOT EXISTS `visual_gallery` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `location_category` INT DEFAULT NULL,
  `tags` VARCHAR(255) DEFAULT NULL,
  `main_file_path` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(50) NOT NULL,
  `thumbnail_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`location_category`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- 8. جدول نظرات در گالری نکات بصری
CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `visual_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `comment_text` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`visual_id`) REFERENCES `visual_gallery` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;


-- --------------------------------------------------------
-- پیش‌فرض‌ها و داده‌های اولیه آزمایشگاهی
-- --------------------------------------------------------

-- درج دسته‌بندی‌های پیش‌فرض عمرانی
INSERT INTO `categories` (`id`, `name`, `parent_id`) VALUES
(1, 'گودبرداری و سازه نگهبان', NULL),
(2, 'سازه‌های بتن آرمه', NULL),
(3, 'سازه‌های فولادی', NULL),
(4, 'تأسیسات برقی و مکانیکی (MEP)', NULL),
(5, 'سفت‌کاری، نما و عایق‌کاری', NULL),
(6, 'مدیریت پروژه و قوانین کارگاهی', NULL),
(7, 'ایمنی و HSE عمومی', NULL),
(8, 'کنترل کیفیت و آزمایشات بتن/جوش', NULL);

-- درج زیردسته‌ها برای سازمان‌دهی دقیق‌تر
INSERT INTO `categories` (`id`, `name`, `parent_id`) VALUES
(9, 'میکروپایل و نیلینگ شمع', 1),
(10, 'میلگردگذاری و قالب‌بندی شالوده', 2),
(11, 'تست‌های غیرمخرب بتن', 8),
(12, 'اتصالات پیچی و جوشی اسکلت', 3);

-- درج کاربر ادمین، کارشناس بلندپایه و مهندس کارگاه
-- پسوردها (الگوریتم bcrypt):
-- کلمه عبور کاربر admin: admin123 -> $2y$10$iKvsWep.L0T5C/41pWhCSuSEx5Tz/iWq7pTz07iG5tT8o5HhGg7fK
-- کلمه عبور کاربر expert: expert123 -> $2y$10$wTfVn9Q/shPizDlhS2FkFuwOorL9nNn9i.nshRE3L5F6ZbeB/K40q
-- کلمه عبور کاربر contributor: field123 -> $2y$10$YByqMyoY7Nq1V2fD3f36Lul0T7j.wIqXHe7I2O0yBfSg38rG1gGf6
INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `password_must_change`) VALUES
(1, 'admin', '$2y$10$iKvsWep.L0T5C/41pWhCSuSEx5Tz/iWq7pTz07iG5tT8o5HhGg7fK', 'مدیر ارشد سامانه (دفتر فنی)', 'admin', 1),
(2, 'expert', '$2y$10$wTfVn9Q/shPizDlhS2FkFuwOorL9nNn9i.nshRE3L5F6ZbeB/K40q', 'دکتر مهدی علوی (مشاور ارشد ژئوتکنیک)', 'expert', 0),
(3, 'contributor', '$2y$10$YByqMyoY7Nq1V2fD3f36Lul0T7j.wIqXHe7I2O0yBfSg38rG1gGf6', 'مهندس سهراب رضایی (ناظر مقیم کارگاه)', 'contributor', 0);

-- درج چند مستند دانش فرضی برای شروع کار
INSERT INTO `knowledge_entries` (`id`, `user_id`, `title`, `problem`, `solution`, `result`, `category_id`, `keywords`, `date_occurred`, `status`, `views`) VALUES
(1, 3, 'ریزش شیب دیواره گود در پروژه زعفرانیه به علت نشتی لوله فاضلاب محلی', 
'طی گودبرداری دیواره شرقی پروژه زعفرانیه با عمق ۱۲ متر، با وجود اجرای نیلینگ در ردیف اول، به طور ناگهانی خاک سست پاره‌سنگی ریزش کرد. بررسی شد که لوله فرسوده فاضلاب کوچه بالادست دچار نشتی شدید شده و به پشت لایه شاتکریت نفوذ کرده بود.', 
'توقف فوری عملیات خاکی، پایدارسازی موقت با بلوک‌های سنگی و بتن‌ریزی پاشنه (ریپ‌راپ). سپس حفر چاه‌های زهکش عمیق افقی به طول ۸ متر برای خروج زه آب و اصلاح زاویه کاشت نیل‌های بعدی با مش متراکم شات‌شده.', 
'باعث جلوگیری از فاجعه فروریختن کوچه مجاور گردید. چاه‌های زهکش فشار هیدرواستاتیک پشت دیوار را در زمان ۲۴ ساعت خنثی کردند.', 
9, 'گودبرداری, ریزش دیواره, نیلینگ, نشتی لوله, زه‌کشی', '2026-02-15', 'approved', 42),

(2, 2, 'ترک‌های برشی انقباضی عمیق در بتن‌ریزی فونداسیون حجم بالا (پروژه پارک فناوری)', 
'پس از اجرای دال فونداسیون صلب به ضخامت ۱.۶ متر در هوای گرم تابستان، ترک‌های عمودی موازی در فواصل ۲ متری در لبه دال ظاهر شد که عمق برخی به ۱۵ سانتی‌متر می‌رسید.', 
'استفاده از یخ خرد شده به عنوان جایگزین آب اختلاط بتن به میزان ۴۰٪ جهت کنترل دمای خروجی بتن زیر ۲۸ درجه سانتی‌گراد، استفاده از افزودنی‌های روان‌کننده دیرگیرکننده، و پوشاندن سطح بتن با گونی مرطوب و پلاستیک بلافاصله بعد از پرداخت نهایی تا ۷ روز.', 
'مشکل ترک‌های انقباضی حرارتی در پارت‌های بتن‌ریزی بعدی به طور کامل مرتفع گردید و مقاومت مشخصه ۲۸ روزه به ۳۵ مگاپاسکال رسید.', 
10, 'بتن‌ریزی حجیم, ترک حرارتی, فونداسیون, هیدراتاسیون', '2025-08-10', 'approved', 85);
