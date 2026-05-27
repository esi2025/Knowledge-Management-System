import { Department, Category, KnowledgeEntry, Question, VisualTip, User } from './types';

// تعریف دپارتمان‌های اصلی سازمان - دپارتمان پیش‌فرض متمایل به امور رفاهی و ورزشی است
export const DEPARTMENTS: Department[] = [
  { id: 'welfare', name: 'واحد ورزشی، فرهنگی و خدمات رفاهی سازمان', iconName: 'Activity' },
  { id: 'operations', name: 'واحد بهره‌برداری و فنی نیروگاه برق', iconName: 'Zap' },
  { id: 'safety', name: 'واحد بهداشت، ایمنی و محیط زیست (HSE)', iconName: 'ShieldAlert' }
];

// دسته‌بندی موضوعات به تفکیک دپارتمان‌ها
export const CATEGORIES: Category[] = [
  // واحد رفاهی
  { id: 'sports', name: 'خدمات ورزشی و سالن‌های تربیت‌بدنی', departmentId: 'welfare' },
  { id: 'cultural', name: 'خدمات فرهنگی، مسابقات، مسجد و کتابخانه‌ها', departmentId: 'welfare' },
  { id: 'medical', name: 'درمانگاه رفاهی، بیمه پرسنل و پایش سلامت صنعتی', departmentId: 'welfare' },
  { id: 'housing', name: 'خدمات اسکان، ویلاهای سازمانی و امور مهمانان دفتری', departmentId: 'welfare' },
  { id: 'catering', name: 'خدمات رستورانی، آشپزخانه صنعتی و تشریفات عمومی', departmentId: 'welfare' },

  // واحد فنی نیروگاه
  { id: 'steam', name: 'توربین‌های بخار مرطوب و بخش بویلرها', departmentId: 'operations' },
  { id: 'generator', name: 'ژنراتورها و ترانسفورماتورهای قدرت فشار قوی', departmentId: 'operations' },
  { id: 'instrument', name: 'ابزار دقیق، اتوماسیون صنعتی و فرامینی PLC', departmentId: 'operations' },

  // واحد ایمنی HSE
  { id: 'fire', name: 'سیستم‌های اعلام خودکار و اطفاء حریق صنعتی', departmentId: 'safety' },
  { id: 'pollution', name: 'پایش پساب خروجی نیروگاه و آلاینده‌های حرارتی', departmentId: 'safety' }
];

// کاربران سامانه جهت لاگین دفتری
export const INITIAL_USERS: User[] = [
  { id: 'u1', username: 'admin', password: '123', name: 'مهندس مهدی اسماعیلی', role: 'admin' },
  { id: 'u2', username: 'expert', password: '123', name: 'دکتر علوی (کارشناس ارشد رفاهی)', role: 'expert' },
  { id: 'u3', username: 'user', password: '123', name: 'مهندس رضایی (مربی و ناظر سالن ورزشی)', role: 'contributor' },
  { id: 'u4', username: 'expert_tech', password: '123', name: 'مهندس حسینی (رئیس نوبت کاری بویلر)', role: 'expert' },
  { id: 'u5', username: 'user_tech', password: '123', name: 'مهندس عباسی (ناظر میدانی ابزار دقیق)', role: 'contributor' }
];

// آرشیو ۱۰ ساله تجارب ممیزی‌شده به تفکیک دپارتمان
export const INITIAL_ENTRIES: KnowledgeEntry[] = [
  // تجربیات واحد رفاهی و ورزشی نیروگاه
  {
    id: 101,
    title: 'کاهش رطوبت و مهار قارچ در تاتامی‌های رزمی سالن تربیت بدنی مجتمع مسکونی',
    categoryId: 'sports',
    categoryName: 'خدمات ورزشی و سالن‌های تربیت‌بدنی',
    problem: 'به علت رطوبت بالای ساحلی نیروگاه در تابستان (تا ۹۲٪)، تاتامی‌های ورزشی رزمی بوی کهنگی و قارچی جدی گرفته و باعث بروز امراض پوستی نونهالانِ فرزند پرسنل شیفت نیروگاه شد.',
    solution: 'نصب تله‌های رطوبت‌گیر گرانولی سیلیکاژل در زیر شاسی سالن و اجرای سیستم هواساز برگشتی با سنسور هوشمند رطوبت روی رنج ۶۰٪ به همراه گندزدایی مکرر با گاز اوزون پرتابل.',
    result: 'کاهش بو به میزان صدرصد و عاری‌سازی سالن از هاگ‌های زنده محیطی طی تست‌های کشت آزمایشگاهی کلینیک نیروگاه.',
    keywords: ['رطوبت', 'تاتامی_ورزشی', 'اوزون‌ساز', 'خدمات_ورزشی', 'نیروگاه'],
    author: 'مهندس رضایی (مربی و ناظر سالن ورزشی)',
    authorRole: 'contributor',
    dateOccurred: '۱۴۰۳/۰۴/۱۲',
    views: 48,
    status: 'approved',
    departmentId: 'welfare'
  },
  {
    id: 102,
    title: 'سیستم سهمیه‌بندی الکترونیکی بهینه سانس‌های ورزشی براساس سختی شیفت کاری پرسنل',
    categoryId: 'sports',
    categoryName: 'خدمات ورزشی و سالن‌های تربیت‌بدنی',
    problem: 'بی‌عدالتی در دسترسی به سانس‌های فوتبال و تنیس سالن چندمنظوره؛ به طوری که نیروهای ستادی سانس‌های مرغوب عصر را رزرو کرده و پرسنل زحمت‌کش شیفت کاری تعمیرات و بویلر محروم می‌شدند.',
    solution: 'بازنویسی آیین‌نامه رفاهی و ایجاد جدول ضریب سختی شیفت کاری (پرسنل شیفت بویلر و بخش تعمیرات داغ ضریب اولویت ۳ دارند) در اولویت‌دهی رزرو بومی سانس‌های طلایی استخر و سالن ورزشی.',
    result: 'افزایش ۶۵ درصدی مشارکت پرسنل شیفت شب توربین‌ها در فعالیت‌های بهینه ورزشی ارتقای سلامتی.',
    keywords: ['عدالت_رفاهی', 'استخر', 'رزرو_سانس', 'نوبت‌دهی'],
    author: 'دکتر علوی (کارشناس ارشد رفاهی)',
    authorRole: 'expert',
    dateOccurred: '۱۴۰۳/۰۶/۲۰',
    views: 74,
    status: 'approved',
    departmentId: 'welfare'
  },
  {
    id: 103,
    title: 'هوشمندسازی خنک‌سازی سردخانه طبخ سالن غذاخوری مرکزی سازمان در برابر بارهای تنش دمایی تابستانی',
    categoryId: 'catering',
    categoryName: 'خدمات رستورانی، آشپزخانه صنعتی و تشریفات عمومی',
    problem: 'در تابستان‌های گرم جنوب، نفوذ جریان گرمای هوا به سردخانه زیرصفر نگهداری تغذیه روزانه پرسنل بوم لرزانی بوجود می‌آورد و موجب افت کیفیت اقلام پروتئینی پرسنل شیفت نیروگاه می‌شد.',
    solution: 'طراحی پرده عایق‌بندی دولایه پلی‌اورتان متحرک و ایجاد سیستم هم‌پوشانی خودکار ترموستات کمکی برای کار در پیک بار تبخیری تابستان.',
    result: 'به صفر رسیدن نرخ فساد‌پذیری گوشت و مرغ ارسالی به رستوران‌های شیفت شب نیروگاه برق.',
    keywords: ['سردخانه_صنعتی', 'پرده_حرارتی', 'پروتئین', 'کیفیت_غذا', 'تابستان'],
    author: 'مهندس رضایی (مربی و ناظر سالن ورزشی)',
    authorRole: 'contributor',
    dateOccurred: '۱۴۰۳/۰۸/۰۲',
    views: 35,
    status: 'approved',
    departmentId: 'welfare'
  },
  {
    id: 104,
    title: 'تخصیص ویلاهای رفاهی محمودآباد پرسنل بر اساس مکانیزم خودکار قرعه‌کشی عادلانه با در نظر گرفتن نوبت کاری',
    categoryId: 'housing',
    categoryName: 'خدمات اسکان، ویلاهای سازمانی و امور مهمانان دفتری',
    problem: 'رزرو مداوم ویلاهای شمال توسط عده‌ای معین از کارکنان ستاد نیروگاه و گله‌مندی مستمر تکنسین‌های خسته بهره‌برداری بخار و آب‌شیرین‌کن‌ها.',
    solution: 'اعمال سیستم امتیاز دهی و فیلتر محرومیت ۳ ساله برای کسانی که از سهمیه ویلاهای شمال نیروگاه استفاده کرده‌اند. پرسنل بویلر سال قبل امتیاز ویژه ۴ برابری در سیستم قرعه‌کشی دارند.',
    result: 'تعادل کامل مراجعات رفاهی خانواده‌ها و رضایت عمیق تکنسین‌های خط اول جبهه برق کشور.',
    keywords: ['قرعه_کشی', 'ویلاهای_سازمانی', 'سفر_رفاهی', 'عدالت'],
    author: 'دکتر علوی (کارشناس ارشد رفاهی)',
    authorRole: 'expert',
    dateOccurred: '۱۴۰۳/۱۰/۱۱',
    views: 92,
    status: 'approved',
    departmentId: 'welfare'
  },

  // تجربیات دپارتمان فنی و نیروگاهی
  {
    id: 201,
    title: 'مهار خوردگی شدید دیواره لوله‌های سوپرهیتر واحد یک بخار نیروگاه برق دلیجان',
    categoryId: 'steam',
    categoryName: 'توربین‌های بخار مرطوب و بخش بویلرها',
    problem: 'پدیده خوردگی دما بالا به دلیل ناخالصی سوخت مازوت پشت بویلر که موجب سوراخ‌شدگی لوله‌ها و خروج اضطراری واحد از تولید برق سراسری با خاموشی‌های شهری می‌شد.',
    solution: 'استفاده از پوشش محافظ اسپری ترمواسپری فلز روی با ترکیب آلیاژ نیکل کروم مقاوم در برابر خوردگی گوگردی گازهای خروجی.',
    result: 'افزایش کارکرد ایمن لوله‌های سوپرهیتر از ۶ ماه به ۳۶ ماه کار مداوم بدون نشتی.',
    keywords: ['سوپرهیتر', 'بویلر', 'خوردگی', 'گوگردی', 'سوخت_مازوت', 'نیروگاه'],
    author: 'مهندس حسینی (رئیس نوبت کاری بویلر)',
    authorRole: 'expert',
    dateOccurred: '۱۴۰۲/۰۲/۱۵',
    views: 124,
    status: 'approved',
    departmentId: 'operations'
  },
  {
    id: 202,
    title: 'تعادل‌بخشی ارتعاشات یاطاقان شماره ۴ توربوژنراتور برق خروجی پائین دست',
    categoryId: 'generator',
    categoryName: 'ژنراتورها و ترانسفورماتورهای قدرت فشار قوی',
    problem: 'افزایش لرزش ژنراتور به رنج خطرناک ۱۱۰ میکرومتر در دورهای بالای ۳۰۰۰ دور در دقیقه که زنگ خطر تخریب بلبرینگ را به صدا در می‌آورد.',
    solution: 'بالانس دینامیکی روتور اصلی توربین در محل نیروگاه به وسیله دستگاه فرکانس متمرکز بدون نیاز به ارسال شفت ارتعاشی به خارج از کشور.',
    result: 'پایین آوردن لرزش به زیر ۲۵ میکرومتر (رنج کاملاً عالی و استاندارد سازمانی نیروگاهی).',
    keywords: ['یاطاقان', 'توربوژنراتور', 'ارتعاش', 'بالانس_دینامیکی', 'تولید_پایدار'],
    author: 'مهندس عباسی (ناظر میدانی ابزار دقیق)',
    authorRole: 'contributor',
    dateOccurred: '۱۴۰۲/۰۵/۲۲',
    views: 89,
    status: 'approved',
    departmentId: 'operations'
  },

  // تجربیات دپارتمان ایمنی HSE
  {
    id: 301,
    title: 'خنک‌سازی اتوماتیک مخازن آمونیاک به وسیله رینگ‌های مه‌پاش آب صنعتی در طوفان‌های تابستانی',
    categoryId: 'fire',
    categoryName: 'سیستم‌های اعلام خودکار و اطفاء حریق صنعتی',
    problem: 'افزایش فشار ناگهانی داخل مخازن کپسول آمونیاک به خاطر دمای محیطی بالای ۵۲ درجه کارگاهی و عدم کارایی عایق پشم‌شیشه سقف.',
    solution: 'پیاده‌سازی ترانسمیسترهای فشار متصل به شیرهای برقی آب که به محض عبور فشار از هشت بار به صورت اتوماتیک رینگ مه‌پاش خنک‌کننده مخازن را فعال می‌کند.',
    result: 'کنترل کامل ریسک انفجار گازهای سمی آمونیاک در محوطه تولید مواد خنک‌ساز چرخه بویلر.',
    keywords: ['آمونیاک', 'مه‌پاش', 'ایمنی_صنعتی', 'HSE', 'گرما', 'انفجار'],
    author: 'مهندس حسینی (رئیس نوبت کاری بویلر)',
    authorRole: 'expert',
    dateOccurred: '۱۴۰۲/۰۷/۰۳',
    views: 105,
    status: 'approved',
    departmentId: 'safety'
  }
];

// سوالات مشورتی جبهه پرسش و پاسخ
export const INITIAL_QUESTIONS: Question[] = [
  {
    id: 1,
    title: 'پیشگیری از جلبک‌زدگی آب آب‌نمای بزرگ ورودی هتل آپارتمان اسکان پرسنل',
    questionText: 'با آغار تابستان آب‌نمای حوض بزرگ روباز هتل آپارتمان استقرار تکنسین‌های نیروگاه دچار جلبک سبز غلیظ و شیوع حشرات شده است. از کات‌کبود استفاده شد اما روی بتن‌های بدنه شوره تیره می‌دهد. چه دوز یا راهکار جایگزینی برای تهویه پرسنل پیشنهاد می‌دهید؟',
    priority: 'normal',
    status: 'resolved',
    author: 'مهندس رضایی (مربی و ناظر سالن ورزشی)',
    createdAt: '۱۴۰۳/۰۴/۱۵',
    userId: 'u3',
    departmentId: 'welfare',
    answers: [
      {
        id: 10,
        answerText: 'پیشنهاد می‌شود به جای کات‌کبود خام از قرص‌های کلر تثبیت‌شده حاوی ضدجلبک به دوز فشرده بهره بگیرید و یا سیستم چرخش آب مجاور با یک فیلتر شنی کوچک غرقابی ارزان‌قیمت تعبیه کنید تا پیترن رشد زیستی قطع گردد.',
        replierName: 'دکتر علوی (کارشناس ارشد رفاهی)',
        replierRole: 'expert',
        createdAt: '۱۴۰۳/۰۴/۲۰',
        isAccepted: true
      }
    ]
  },
  {
    id: 2,
    title: 'انتخاب بهترین نوع کف‌پوش ضربه‌گیر برای حاشیه پیست پیاده‌روی مجموعه رفاهی دپارتمان',
    questionText: 'جهت توسعه سلامت عمومی خانواده‌های مستقر در کوی منازل نیروگاهی، نیاز به خرید کف‌پوش مناسب پیست دوومیدانی است. کاتالوگ شرکت‌های ایرانی کفپوش لاستیکی رولی و تایل‌های پلاستیکی را پیشنهاد میکند. کدام برای آفتاب تند و بارندگی طولانی منطقه عمر بهتری دارد؟',
    priority: 'normal',
    status: 'open',
    author: 'مهندس رضایی (مربی و ناظر سالن ورزشی)',
    createdAt: '۱۴۰۳/۰۵/۱۰',
    userId: 'u3',
    departmentId: 'welfare',
    answers: []
  },
  {
    id: 3,
    title: 'پدیده خوردگی کاویتاسیون در پروانه پمپ‌های تامین آب تغذیه مخازن رفاهی سالن غذاخوری',
    questionText: 'پمپ شماره ۲ آبرسانی آشپزخانه رستوران نیروگاه صدای قلوه‌سنگ چرخنده می‌دهد و فشار دبی آن مکرراً افت می‌کند. احتمال کاویتاسیون جدی است. چه تدبیری جهت کاهش استهلاک بکار بندیم؟',
    priority: 'urgent',
    status: 'open',
    author: 'مهندس رضایی (مربی و ناظر سالن ورزشی)',
    createdAt: '۱۴۰۳/۰۶/۰۲',
    userId: 'u3',
    departmentId: 'welfare',
    answers: []
  },

  // بخش فنی نیروگاهی
  {
    id: 4,
    title: 'نحوه تمیزکاری رسوبات گچی سینی‌های گرمایشی غشاهای دی‌سَلینیتور آب‌شیرین‌کن نیروگاهی',
    questionText: 'سختی آب ورودی حوضچه‌ها بالا رفته و اسیدشویی هیدروکلریک معمولی روی سینی خنک‌کننده‌ رسوبات را مهار نمی‌کند. احتمال سوراخ شدن فویل تیتانیومی المنت زیاد است.',
    priority: 'critical',
    status: 'open',
    author: 'مهندس عباسی (ناظر میدانی ابزار دقیق)',
    createdAt: '۱۴۰۲/۰۶/۲۸',
    userId: 'u5',
    departmentId: 'operations',
    answers: []
  }
];

// نکات تصویری نظارتی و هشدارهای ایمنی و رفاهی روز کارگاهی
export const INITIAL_VISUAL_TIPS: VisualTip[] = [
  {
    id: 1,
    title: 'بهداشت آب اسخر و سالن‌های مرطوب رفاهی',
    description: 'همواره چک‌لیست سطح کلر آب استخر نیروگاهی را در بازه ۱.۵ تا ۳ppm و مقدار pH را بین ۷.۲ تا ۷.۶ نگه‌دارید تا ریسک بروز بیماری‌های قارچی ناشی از پاهای خیس به صفر متمایل شود.',
    imageUrl: 'https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7?auto=format&fit=crop&q=80&w=700',
    departmentId: 'welfare'
  },
  {
    id: 2,
    title: 'بهداشت انبارداری مواد غذایی سرد رستوران نیروگاه',
    description: 'ثبت خودکار دمای یخچال‌های نگهداری سبزیجات روی ۵ درجه بالای صفر و مخازن انجماد گوشت روی منفی ۱۸ درجه برای پایداری سلامت تغذیه خط نخست کارگران تولید برق الزامی است.',
    imageUrl: 'https://images.unsplash.com/photo-1588964895597-cfccd6e2dbf9?auto=format&fit=crop&q=80&w=700',
    departmentId: 'welfare'
  },
  {
    id: 3,
    title: 'زاویه صحیح چک حرارتی یاطاقان‌های موتورهای فشار مستقیم',
    description: 'هنگام خواندن درجه روغن یاطاقان توربین کمکی بوسیله پیرومتر نوری، فاصله استاندارد ۵۰ سانتی‌متری و زاویه ۶۰ درجه را حفظ کنید تا تشعشعات گسیلشی سنسور گيج نکنند.',
    imageUrl: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&q=80&w=700',
    departmentId: 'operations'
  }
];

// شبیه‌سازی کدهای PHP آفلاین جهت کار در سیستم‌های XAMPP/WAMP محلی نیروگاه
export const PHP_FILES_DICT: Record<string, { filename: string; description: string; code: string }> = {
  db_connection: {
    filename: 'db_connection.php',
    description: 'فایل اتصال لوکال همگام با PHP PDO و پایگاه‌داده MySQL آفلاین روی سیستم‌های قدیمی و دفتری سازمان',
    code: `<?php
// db_connection.php - اتصال امن آفلاین لوکال و همخوان با انواع سرویس‌های WAMP/XAMPP
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'powerplant_welfare_km');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("خطای بحرانی در اتصال لوکال به دیتابیس نیروگاه: " . $e->getMessage());
}
?>`
  },
  login: {
    filename: 'login.php',
    description: 'سیستم ثبت نشست‌ها و مدیریت نشست کاربران همنوا با سه نقش بومی (مدیر، ممیز ارشد، ناظر میدانی)',
    code: `<?php
// login.php - سیستم لوکال لاگین و نشست‌ها برای نیروگاه برق مهدی اسماعیلی
require_once 'db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // تطبیق با رمزهای لوکال هش‌شده (توضیح: برای همخوانی در اینترانت لوکال، از رمز ساده یا هش استفاده شود)
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "کلمه عبور یا نام کاربری در سیستم لوکال اشتباه است.";
        }
    }
}
?>`
  },
  save_entry: {
    filename: 'save_knowledge.php',
    description: 'کد ثبت سند دانش فنی به همراه آپلود آفلاین مستقیم مدارک تصویری، فیلم و نوارهای صوتی رکوردشده ناظران',
    code: `<?php
// save_knowledge.php - ذخیره مستندات تصویری و صوتی خدمات رفاهی و کارگاهی
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("عدم دسترسی مجاز! لطفاً ابتدا به سامانه لاگین کنید.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title']));
    $category_id = trim($_POST['category_id']);
    $problem = htmlspecialchars(trim($_POST['problem']));
    $solution = htmlspecialchars(trim($_POST['solution']));
    $result = htmlspecialchars(trim($_POST['result']));
    $keywords = htmlspecialchars(trim($_POST['keywords'])); // رشته کامای انگلیسی تفکیک‌شده
    $department_id = trim($_POST['department_id'] ?? 'welfare');
    
    // پردازش آپلود مدیا (تصویر، ویدئو، صدای ضبط شده ناظر کارگاه)
    $media_url = '';
    $media_type = '';
    
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['attachment']['tmp_name'];
        $file_name = $_FILES['attachment']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_exts = ['jpg', 'jpeg', 'png', 'mp4', 'mkv', 'mp3', 'wav', 'ogg'];
        if (in_array($file_ext, $allowed_exts)) {
            $new_name = uniqid('km_', true) . '.' . $file_ext;
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            if (move_uploaded_file($file_tmp, $upload_dir . $new_name)) {
                $media_url = $upload_dir . $new_name;
                
                // تشخیص نوع پیوست رسانه‌ای
                if (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
                    $media_type = 'image';
                } elseif (in_array($file_ext, ['mp4', 'mkv'])) {
                    $media_type = 'video';
                } else {
                    $media_type = 'audio';
                }
            }
        }
    }

    // پیش‌نویس موقت به عنوان وضعیت اولیه (جهت طی روند ممیزی و تایید ناظر عالی)
    $status = 'draft'; 
    $author = $_SESSION['user_name'];
    $author_role = $_SESSION['user_role'];
    
    $sql = "INSERT INTO knowledge_entries (title, category_id, problem, solution, result, keywords, media_url, media_type, author, author_role, status, department_id, date_created) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$title, $category_id, $problem, $solution, $result, $keywords, $media_url, $media_type, $author, $author_role, $status, $department_id])) {
        echo json_encode(['status' => 'success', 'message' => 'دانش با موفقیت ذخیره شده و در صف ممیزی و تایید کارشناس قرار گرفت.']);
    } else {
        echo json_encode(['status' => 'failed', 'message' => 'بروز خطا در پایگاه‌داده لوکال.']);
    }
}
?>`
  },
  schema: {
    filename: 'database_schema.sql',
    description: 'ساختار پایگاه‌داده غنی و جامع همنوا با روابط و نمایه دپارتمان‌ها و ممیزی‌های چندنقشاه',
    code: `-- database_schema.sql - دایرکتوری نونمایی جداولMySQL آفلاین برای دایرکتوری پورتال
CREATE DATABASE IF NOT EXISTS powerplant_welfare_km CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE powerplant_welfare_km;

-- ۱. جدول کاربران با نقش ممیز و ناظر
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'expert', 'contributor') NOT NULL DEFAULT 'contributor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ۲. جدول دپارتمان‌های تفکیکی
CREATE TABLE IF NOT EXISTS departments (
    id VARCHAR(30) PRIMARY KEY,
    name VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ۳. جدول زیردسته رسته‌های مهندسی و اداری
CREATE TABLE IF NOT EXISTS categories (
    id VARCHAR(30) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department_id VARCHAR(30) NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ۴. جدول اسناد و پرونده‌های ممیزی تجربیات (رانت ۱۰ ساله)
CREATE TABLE IF NOT EXISTS knowledge_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category_id VARCHAR(30) NOT NULL,
    problem TEXT NOT NULL,
    solution TEXT NOT NULL,
    result TEXT,
    keywords VARCHAR(255),
    media_url VARCHAR(255),
    media_type VARCHAR(20),
    author VARCHAR(100) NOT NULL,
    author_role VARCHAR(50) NOT NULL,
    status ENUM('approved', 'draft', 'rejected') DEFAULT 'draft',
    rejection_reason TEXT,
    department_id VARCHAR(30) NOT NULL,
    views INT DEFAULT 0,
    date_created DATETIME NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- درج داده‌های نمونه و ساختار اولیه
INSERT INTO departments (id, name) VALUES 
('welfare', 'واحد ورزشی، فرهنگی و خدمات رفاهی سازمان'),
('operations', 'واحد بهره‌برداری و فنی نیروگاه برق'),
('safety', 'واحد بهداشت، ایمنی و محیط زیست (HSE)');

INSERT INTO users (username, password_hash, name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مهندس مهدی اسماعیلی', 'admin'); -- پسورد نمونه لوکال: 123
`
  }
};
