import React, { useState, useEffect, useMemo } from 'react';
import { 
  Building2, 
  Zap, 
  Award, 
  Search, 
  PlusCircle, 
  Printer, 
  Download, 
  UserCheck, 
  Users, 
  FileText, 
  CheckCircle2, 
  XCircle, 
  AlertCircle, 
  MessageSquare, 
  Clock, 
  Compass, 
  HelpCircle, 
  LogOut, 
  LogIn, 
  Filter, 
  Database, 
  Copy, 
  FileCode,
  Image as ImageIcon,
  Video as VideoIcon,
  Mic as AudioIcon,
  Check,
  ChevronLeft,
  ChevronsLeft,
  X,
  UploadCloud,
  RefreshCw,
  FolderOpen,
  FolderLock
} from 'lucide-react';

import { User, Department, Category, KnowledgeEntry, Question, Answer, VisualTip } from './types';
import { 
  DEPARTMENTS, 
  CATEGORIES, 
  INITIAL_USERS, 
  INITIAL_ENTRIES, 
  INITIAL_QUESTIONS, 
  INITIAL_VISUAL_TIPS, 
  PHP_FILES_DICT 
} from './mockData';

// دریافت دیتای لوکال برای مانا بودن اطلاعات در لایو پریویو
const STORAGE_PREFIX = 'powerplant_km_';

export default function App() {
  // ۱. حالت‌های ورود و احراز هویت دفتری
  const [users, setUsers] = useState<User[]>(() => {
    const saved = localStorage.getItem(STORAGE_PREFIX + 'users');
    return saved ? JSON.parse(saved) : INITIAL_USERS;
  });
  
  const [currentUser, setCurrentUser] = useState<User | null>(() => {
    const saved = localStorage.getItem(STORAGE_PREFIX + 'currentUser');
    return saved ? JSON.parse(saved) : INITIAL_USERS[0]; // ورود پیش‌فرض کابر ادمین برای راحتی ممیزی در AI Studio
  });

  const [loginUsername, setLoginUsername] = useState('');
  const [loginPassword, setLoginPassword] = useState('');
  const [loginError, setLoginError] = useState('');

  // ۲. دپارتمان فعال (پیش‌فرض روی خدمات رفاهی و ورزشی متمرکز است)
  const [activeDepartmentId, setActiveDepartmentId] = useState<string>('welfare');

  // ۳. لایه‌های ناوبری عمومی پورتال
  const [activeTab, setActiveTab] = useState<'archive' | 'create' | 'audit' | 'qa' | 'tips' | 'php_src' | 'users_mgmt'>('archive');
  const [selectedEntry, setSelectedEntry] = useState<KnowledgeEntry | null>(null);

  // ۴. دیتابیس لوکال اسناد دانش، فروم و نکات
  const [entries, setEntries] = useState<KnowledgeEntry[]>(() => {
    const saved = localStorage.getItem(STORAGE_PREFIX + 'entries');
    return saved ? JSON.parse(saved) : INITIAL_ENTRIES;
  });

  const [questions, setQuestions] = useState<Question[]>(() => {
    const saved = localStorage.getItem(STORAGE_PREFIX + 'questions');
    return saved ? JSON.parse(saved) : INITIAL_QUESTIONS;
  });

  const [visualTips, setVisualTips] = useState<VisualTip[]>(() => {
    const saved = localStorage.getItem(STORAGE_PREFIX + 'tips');
    return saved ? JSON.parse(saved) : INITIAL_VISUAL_TIPS;
  });

  // ۵. لوگوی آپلود شونده رسمی شرکت (ماندگار در حافظه بومی)
  const [logoUrl, setLogoUrl] = useState<string>(() => {
    const saved = localStorage.getItem(STORAGE_PREFIX + 'logoUrl');
    return saved || '';
  });

  // ۶. حالت‌های ورودی فیلترهای آرشیو
  const [searchTerm, setSearchTerm] = useState('');
  const [filterCategory, setFilterCategory] = useState('all');
  const [filterMedia, setFilterMedia] = useState<'all' | 'image' | 'video' | 'audio'>('all');
  const [filterStatus, setFilterStatus] = useState<string>('all'); // مخصوص مراجع مدیریتی

  // ۷. فرم ورودی سند دانش جدید
  const [formTitle, setFormTitle] = useState('');
  const [formCategory, setFormCategory] = useState('');
  const [formProblem, setFormProblem] = useState('');
  const [formSolution, setFormSolution] = useState('');
  const [formResult, setFormResult] = useState('');
  const [formKeywords, setFormKeywords] = useState('');
  const [formMediaFile, setFormMediaFile] = useState<File | null>(null);
  const [formMediaType, setFormMediaType] = useState<'image' | 'video' | 'audio' | ''>('');
  const [formMediaBase64, setFormMediaBase64] = useState<string>('');
  const [uploadProgress, setUploadProgress] = useState<number | null>(null);
  const [formSuccess, setFormSuccess] = useState('');
  const [isDragOver, setIsDragOver] = useState(false);

  // ۸. ورودی‌های فروم گفتگو
  const [qTitle, setQTitle] = useState('');
  const [qText, setQText] = useState('');
  const [qPriority, setQPriority] = useState<'normal' | 'urgent' | 'critical'>('normal');
  const [ansInputMap, setAnsInputMap] = useState<Record<number, string>>({});

  // ۹. فرم مدیریت کاربران توسط ادمین
  const [userFormName, setUserFormName] = useState('');
  const [userFormUsername, setUserFormUsername] = useState('');
  const [userFormPassword, setUserFormPassword] = useState('');
  const [userFormRole, setUserFormRole] = useState<'admin' | 'expert' | 'contributor'>('contributor');
  const [userSuccessMsg, setUserSuccessMsg] = useState('');

  // ۱۰. ممیزی و کامنت‌های دبار برگشت اسناد
  const [rejectionComments, setRejectionComments] = useState<Record<number, string>>({});
  const [activeRejectionInputId, setActiveRejectionInputId] = useState<number | null>(null);

  // ۱۱. کپی کدهای آفلاین PHP
  const [copiedFileKey, setCopiedFileKey] = useState<string | null>(null);
  const [activePhpKey, setActivePhpKey] = useState<string>('db_connection');

  // ذخیره‌سازی مانا با بروزرسانی وضعیت‌ها
  useEffect(() => {
    localStorage.setItem(STORAGE_PREFIX + 'users', JSON.stringify(users));
  }, [users]);

  useEffect(() => {
    localStorage.setItem(STORAGE_PREFIX + 'currentUser', JSON.stringify(currentUser));
  }, [currentUser]);

  useEffect(() => {
    localStorage.setItem(STORAGE_PREFIX + 'entries', JSON.stringify(entries));
  }, [entries]);

  useEffect(() => {
    localStorage.setItem(STORAGE_PREFIX + 'questions', JSON.stringify(questions));
  }, [questions]);

  useEffect(() => {
    localStorage.setItem(STORAGE_PREFIX + 'tips', JSON.stringify(visualTips));
  }, [visualTips]);

  useEffect(() => {
    localStorage.setItem(STORAGE_PREFIX + 'logoUrl', logoUrl);
  }, [logoUrl]);

  // ریست فیلترها در زمان جابجایی دپارتمان‌ها
  useEffect(() => {
    setFilterCategory('all');
    setSearchTerm('');
    // مقداردهی اتوماتیک اولین دسته‌بندی دپارتمان در فرم ثبت
    const deptCategories = CATEGORIES.filter(c => c.departmentId === activeDepartmentId);
    if (deptCategories.length > 0) {
      setFormCategory(deptCategories[0].id);
    } else {
      setFormCategory('');
    }
  }, [activeDepartmentId]);

  // عملیات خروج از سیستم لوکال
  const handleLogout = () => {
    setCurrentUser(null);
    setActiveTab('archive');
    setSelectedEntry(null);
  };

  // عملیات ورود لوکال
  const handleLogin = (e: React.FormEvent) => {
    e.preventDefault();
    const found = users.find(u => u.username === loginUsername && u.password === loginPassword);
    if (found) {
      setCurrentUser(found);
      setLoginUsername('');
      setLoginPassword('');
      setLoginError('');
    } else {
      setLoginError('❌ نام کاربری یا کلمه عبور در سرور داخلی منطبق نیست.');
    }
  };

  // ورود آسان برای بررسی ممیزی (سوئیچ‌کننده تست چابک)
  const handleQuickLogin = (role: 'admin' | 'expert' | 'contributor') => {
    const matched = users.find(u => u.role === role);
    if (matched) {
      setCurrentUser(matched);
      setLoginError('');
    }
  };

  // آپلود لوگوی شرکت
  const handleLogoUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      const reader = new FileReader();
      reader.onloadend = () => {
        setLogoUrl(reader.result as string);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleRemoveLogo = () => {
    setLogoUrl('');
  };

  // مدیریت درگ اند دراپ پیوست رسانه‌ای
  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragOver(true);
  };

  const handleDragLeave = () => {
    setIsDragOver(false);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragOver(false);
    const file = e.dataTransfer.files?.[0];
    if (file) {
      processAttachedFile(file);
    }
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      processAttachedFile(file);
    }
  };

  const processAttachedFile = (file: File) => {
    setFormMediaFile(file);
    // تشخیص فرمت رسانه
    const nameLower = file.name.toLowerCase();
    const typeLower = file.type.toLowerCase();
    
    let detectedType: 'image' | 'video' | 'audio' | '' = '';
    
    if (typeLower.startsWith('image/') || /\.(jpg|jpeg|png|gif|webp)$/.test(nameLower)) {
      detectedType = 'image';
    } else if (typeLower.startsWith('video/') || /\.(mp4|mkv|avi|mov)$/.test(nameLower)) {
      detectedType = 'video';
    } else if (typeLower.startsWith('audio/') || /\.(mp3|wav|ogg|aac|m4a)$/.test(nameLower)) {
      detectedType = 'audio';
    }
    
    setFormMediaType(detectedType);

    // شبیه‌سازی لودر آپلود به صورت درصد پیشرفت
    setUploadProgress(10);
    const interval = setInterval(() => {
      setUploadProgress(prev => {
        if (prev === null) return null;
        if (prev >= 100) {
          clearInterval(interval);
          // ذخیره بیس۶۴ فایل جهت نمایش لوکال
          const reader = new FileReader();
          reader.onloadend = () => {
            setFormMediaBase64(reader.result as string);
          };
          reader.readAsDataURL(file);
          return 100;
        }
        return prev + 30;
      });
    }, 150);
  };

  // ثبت دانش سازمانی جدید (وضعیت اولیه همیشه پیش‌نویس است)
  const handleCreateKnowledge = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formTitle.trim() || !formProblem.trim() || !formSolution.trim()) {
      alert('لطفاً فیلدهای اجباری دارای ستاره را با دقت پر کنید.');
      return;
    }

    const catObj = CATEGORIES.find(c => c.id === formCategory);
    
    const newEntry: KnowledgeEntry = {
      id: Date.now(),
      title: formTitle,
      categoryId: formCategory,
      categoryName: catObj ? catObj.name : 'رسته عمومی',
      problem: formProblem,
      solution: formSolution,
      result: formResult,
      keywords: formKeywords ? formKeywords.split(/[,،]/).map(k => k.trim()).filter(Boolean) : [],
      mediaUrl: formMediaBase64 || undefined,
      mediaType: formMediaType || undefined,
      author: currentUser?.name || 'ناظر مهمان',
      authorRole: currentUser?.role || 'contributor',
      dateOccurred: new Date().toLocaleDateString('fa-IR'),
      views: 1,
      status: 'draft', // پیش‌نویس موقت تا تایید ممیز
      departmentId: activeDepartmentId
    };

    setEntries(prev => [newEntry, ...prev]);
    setFormSuccess('✔️ سند تجربه فنی با موفقیت در اینترانت لوکال ذخیره و به کارتابل ممیزی ارجاع شد.');
    
    // پاکسازی فرم
    setFormTitle('');
    setFormProblem('');
    setFormSolution('');
    setFormResult('');
    setFormKeywords('');
    setFormMediaFile(null);
    setFormMediaType('');
    setFormMediaBase64('');
    setUploadProgress(null);

    // انتقال اتوماتیک ناظر پس از ۳ ثانیه به آرشیو
    setTimeout(() => {
      setFormSuccess('');
      setActiveTab('archive');
    }, 2500);
  };

  // فرآیندهای ممیزی و تایید کیفیت (مخصوص ادمین و ممیز ارشد)
  const handleAuditApprove = (id: number) => {
    setEntries(prev => prev.map(item => {
      if (item.id === id) {
        return { ...item, status: 'approved', rejectionReason: undefined };
      }
      return item;
    }));
  };

  const handleAuditReject = (id: number) => {
    const reason = rejectionComments[id];
    if (!reason?.trim()) {
      alert('لطفاً دلیل ممیزی علمی یا تذکر اصلاح سند را وارد کنید.');
      return;
    }
    setEntries(prev => prev.map(item => {
      if (item.id === id) {
        return { ...item, status: 'rejected', rejectionReason: reason };
      }
      return item;
    }));
    // پاکسازی
    setActiveRejectionInputId(null);
  };

  // ثبت چالش جدید در بخش Q&A دپارتمان فعال
  const handlePostQuestion = (e: React.FormEvent) => {
    e.preventDefault();
    if (!qTitle.trim() || !qText.trim()) {
      alert('عنوان و متن سوال فنی نمیتواند خالی باشد.');
      return;
    }

    const newQ: Question = {
      id: Date.now(),
      title: qTitle,
      questionText: qText,
      priority: qPriority,
      status: 'open',
      author: currentUser?.name || 'ناظر ناآشنا',
      createdAt: new Date().toLocaleDateString('fa-IR'),
      userId: currentUser?.id || 'temp',
      departmentId: activeDepartmentId,
      answers: []
    };

    setQuestions(prev => [newQ, ...prev]);
    setQTitle('');
    setQText('');
    setQPriority('normal');
  };

  // ثبت نظریه یا راهکار مشورتی برای چالش مطرح شده
  const handlePostAnswer = (qId: number) => {
    const text = ansInputMap[qId];
    if (!text?.trim()) {
      alert('لطفاً ابتدا نظریه فنی خود را در کادر وارد کنید.');
      return;
    }

    setQuestions(prev => prev.map(q => {
      if (q.id === qId) {
        const newAns: Answer = {
          id: Date.now(),
          answerText: text,
          replierName: currentUser?.name || 'کارشناس مدعو',
          replierRole: currentUser?.role || 'contributor',
          createdAt: new Date().toLocaleDateString('fa-IR'),
          isAccepted: false
        };
        return { ...q, answers: [...q.answers, newAns] };
      }
      return q;
    }));

    setAnsInputMap(prev => ({ ...prev, [qId]: '' }));
  };

  // پذیرش یک نظریه مشورتی به عنوان پیترن طلایی حل چالش (تغییر وضعیت سوال به برطرف‌شده)
  const handleAcceptAnswer = (qId: number, ansId: number) => {
    setQuestions(prev => prev.map(q => {
      if (q.id === qId) {
        const updatedAnswers = q.answers.map(ans => {
          if (ans.id === ansId) {
            return { ...ans, isAccepted: true };
          }
          return ans;
        });
        return { ...q, status: 'resolved', answers: updatedAnswers };
      }
      return q;
    }));
  };

  // افزودن یا ساخت دفتری کاربر جدید توسط ادمین ارشد
  const handleCreateUser = (e: React.FormEvent) => {
    e.preventDefault();
    if (!userFormName.trim() || !userFormUsername.trim() || !userFormPassword.trim()) {
      alert('پرکردن کلیه کادرهای ستاره‌دار الزامی است.');
      return;
    }

    const duplicate = users.some(u => u.username === userFormUsername);
    if (duplicate) {
      alert('خطا: این نام کاربری قبلاً در شبکه داخلی استفاده شده است.');
      return;
    }

    const newUser: User = {
      id: 'u_' + Date.now(),
      name: userFormName,
      username: userFormUsername,
      password: userFormPassword,
      role: userFormRole
    };

    setUsers(prev => [...prev, newUser]);
    setUserSuccessMsg(`✔️ ناظر گرامی [${userFormName}] با هماهنگی به اعضای پرتال همگرا شد.`);
    setUserFormName('');
    setUserFormUsername('');
    setUserFormPassword('');
    setUserFormRole('contributor');

    setTimeout(() => setUserSuccessMsg(''), 4000);
  };

  // حذف پرسنل از پورتال ممیزی
  const handleDeleteUser = (id: string) => {
    if (id === currentUser?.id) {
      alert('شما قادر به حذف اکانت فعال خودتان نمی‌باشید.');
      return;
    }
    if (window.confirm('آیا از خلع دسترسی این همکار گرامی از اینترانت پورتال اطمینان دارید؟')) {
      setUsers(prev => prev.filter(u => u.id !== id));
    }
  };

  // فیلتر هوشمند اسناد آرشیو ممیزی شده براساس دپارتمان فعال و جستجو
  const filteredEntries = useMemo(() => {
    return entries.filter(entry => {
      // تفکیک سختگیرانه براساس دپارتمان فعال
      if (entry.departmentId !== activeDepartmentId) return false;

      // فیلتر امنیتی حریم ناظران: کاربران معمولی فقط اسناد approved و اسناد draft خودشان را می‌بینند
      if (entry.status !== 'approved') {
        const isOwner = currentUser && entry.author === currentUser.name;
        const isAuditor = currentUser && ['admin', 'expert'].includes(currentUser.role);
        if (!isOwner && !isAuditor) return false;
      }

      // فیلتر دسته رسته‌های فرعی
      if (filterCategory !== 'all' && entry.categoryId !== filterCategory) return false;

      // فیلتر فرمت پیوست تصویری/صوتی
      if (filterMedia !== 'all') {
        if (filterMedia === 'image' && entry.mediaType !== 'image') return false;
        if (filterMedia === 'video' && entry.mediaType !== 'video') return false;
        if (filterMedia === 'audio' && entry.mediaType !== 'audio') return false;
      }

      // فیلتر وضعیت برای مراجع
      if (filterStatus !== 'all' && entry.status !== filterStatus) return false;

      // فیلتر تکست جستجو (عنوان، شرح مسئله یا کلیدواژه‌ها)
      if (searchTerm.trim() !== '') {
        const term = searchTerm.toLowerCase();
        const matchTitle = entry.title.toLowerCase().includes(term);
        const matchProblem = entry.problem.toLowerCase().includes(term);
        const matchSolution = entry.solution.toLowerCase().includes(term);
        const matchKeywords = entry.keywords.some(k => k.toLowerCase().includes(term));
        const matchAuthor = entry.author.toLowerCase().includes(term);
        
        return matchTitle || matchProblem || matchSolution || matchKeywords || matchAuthor;
      }

      return true;
    });
  }, [entries, activeDepartmentId, filterCategory, filterMedia, filterStatus, searchTerm, currentUser]);

  // کدهای PHP انتخابی
  const copyPhpCode = (key: string, code: string) => {
    navigator.clipboard.writeText(code);
    setCopiedFileKey(key);
    setTimeout(() => setCopiedFileKey(null), 3000);
  };

  // آمار کلان دپارتمان فعال به صورت پویا
  const stats = useMemo(() => {
    const deptEntries = entries.filter(e => e.departmentId === activeDepartmentId);
    const approved = deptEntries.filter(e => e.status === 'approved').length;
    const pending = deptEntries.filter(e => e.status === 'draft').length;
    const rejected = deptEntries.filter(e => e.status === 'rejected').length;
    
    // تعداد سوالات و تصاویر دپارتمان
    const deptQ = questions.filter(q => q.departmentId === activeDepartmentId);
    const pendingQ = deptQ.filter(q => q.status === 'open').length;
    const totalVisual = visualTips.filter(t => t.departmentId === activeDepartmentId).length;

    // محاسبات آماری دسته رسته‌ها برای چارت میله‌ای بومی
    const deptCats = CATEGORIES.filter(c => c.departmentId === activeDepartmentId);
    const categoryDistribution = deptCats.map(cat => {
      const count = deptEntries.filter(e => e.categoryId === cat.id && e.status === 'approved').length;
      return { name: cat.name, count };
    });

    const maxCount = Math.max(...categoryDistribution.map(c => c.count), 1);

    return {
      approved,
      pending,
      rejected,
      pendingQ,
      totalVisual,
      categoryDistribution,
      maxCount
    };
  }, [entries, activeDepartmentId, questions, visualTips]);

  return (
    <div className="min-h-screen bg-slate-50 text-slate-800 font-sans leading-normal flex flex-col" dir="rtl">
      
      {/* هدر بالایی پورتال */}
      <header className="bg-gradient-to-r from-slate-900 via-teal-950 to-slate-900 text-white shadow-md print:hidden">
        <div className="max-w-7xl mx-auto px-4 py-3 flex flex-wrap justify-between items-center gap-4">
          
          <div className="flex items-center gap-3">
            {logoUrl ? (
              <img 
                src={logoUrl} 
                alt="لوگوی شرکت" 
                className="h-11 max-w-[120px] object-contain rounded bg-white/10 p-1 border border-teal-500/20" 
                referrerPolicy="no-referrer"
              />
            ) : (
              <div className="bg-teal-600 p-2 rounded-lg text-white">
                <Building2 className="w-6 h-6 animate-pulse" />
              </div>
            )}
            <div>
              <h1 className="text-sm font-black tracking-tight text-teal-400">سامانه مدیریت دانش ضمنی و ممیزی تجربیات فنی</h1>
              <p className="text-[10px] text-slate-300 font-bold">بومی‌سازی شده نیروگاه حرارتی تولید انرژی الکتریکی</p>
            </div>
          </div>

          {/* سوئیچ وضعیت حساب جاری کارگاه یا فرم ورود */}
          {currentUser ? (
            <div className="flex items-center gap-3 bg-slate-800/80 p-2.5 rounded-lg border border-slate-700">
              <div className="text-right">
                <div className="text-xs font-black text-slate-100 flex items-center gap-1.5">
                  <span className="w-2 h-2 rounded-full bg-emerald-500 animate-ping" />
                  <span>{currentUser.name}</span>
                </div>
                <div className="text-[9px] text-teal-400 mt-0.5 font-bold">
                  {currentUser.role === 'admin' && '👑 مدیر عالی کل سیستم'}
                  {currentUser.role === 'expert' && '🔬 ناظر فیدبک‌دهنده کارشناس'}
                  {currentUser.role === 'contributor' && '👷 ناظر صحرایی پایگاه کار'}
                </div>
              </div>
              <button 
                onClick={handleLogout}
                className="bg-rose-950 hover:bg-rose-900 text-rose-300 p-1.5 rounded-lg text-xs font-bold border border-rose-800 transition-colors flex items-center gap-1 cursor-pointer"
                title="خروج از حساب دفتری"
              >
                <LogOut className="w-3.5 h-3.5" />
              </button>
            </div>
          ) : (
            <div className="flex items-center gap-2">
              <span className="text-xs text-slate-400">پنل ممیزی آفلاین:</span>
              <button 
                onClick={() => handleQuickLogin('admin')}
                className="bg-teal-900 border border-teal-700 text-white px-2.5 py-1 rounded text-[10px] font-bold cursor-pointer hover:bg-teal-800"
              >
                ورود سریع ادمین
              </button>
              <button 
                onClick={() => handleQuickLogin('expert')}
                className="bg-amber-900 border border-amber-800 text-white px-2.5 py-1 rounded text-[10px] font-bold cursor-pointer hover:bg-amber-800"
              >
                ورود سریع ممیز ارشد
              </button>
            </div>
          )}
        </div>
      </header>

      {/* نوار سوئیچ‌کننده ارشد دپارتمان‌های کلیدی سازمان (هسته اصلی تعامل نیروگاهی) */}
      <section className="bg-slate-200 border-b border-slate-300 py-2 shadow-inner print:hidden">
        <div className="max-w-7xl mx-auto px-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          
          <div className="flex items-center gap-2">
            <span className="text-[11px] font-black text-slate-600 bg-slate-300 px-2 py-1 rounded-md">سوئیچ دپارتمان مرجع:</span>
            <div className="flex flex-wrap gap-1.5">
              {DEPARTMENTS.map(dept => {
                const isActive = activeDepartmentId === dept.id;
                return (
                  <button
                    key={dept.id}
                    onClick={() => {
                      setActiveDepartmentId(dept.id);
                      setSelectedEntry(null); // بازگشت به لیست در صورت تغییر پیترن
                    }}
                    className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer border ${
                      isActive 
                        ? 'bg-gradient-to-r from-teal-700 to-teal-800 text-white border-teal-950 shadow-md ring-2 ring-teal-500/20' 
                        : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-300'
                    }`}
                  >
                    {dept.id === 'welfare' && <Compass className="w-3.5 h-3.5" />}
                    {dept.id === 'operations' && <Zap className="w-3.5 h-3.5" />}
                    {dept.id === 'safety' && <AlertCircle className="w-3.5 h-3.5" />}
                    <span>{dept.name}</span>
                    {isActive && <span className="text-[9px] bg-teal-900 text-white px-2 py-0.2 rounded-full font-black">فعال</span>}
                  </button>
                );
              })}
            </div>
          </div>

          {/* ابزارک گام‌به‌گام آپلود لوگوی سازمانی */}
          <div className="bg-white px-3 py-1.5 rounded-lg border border-slate-300 flex items-center justify-between gap-3 text-xs">
            <span className="text-[11px] font-bold text-slate-500">لوگوی اختصاصی گزارش رسمی:</span>
            <div className="flex items-center gap-2">
              {logoUrl ? (
                <>
                  <span className="text-emerald-600 font-mono text-[9px] font-bold">لوگو فعال ✓</span>
                  <button 
                    onClick={handleRemoveLogo} 
                    className="text-red-500 hover:text-red-700 font-bold text-[10px] cursor-pointer"
                  >
                    حذف لوگو
                  </button>
                </>
              ) : (
                <label className="text-teal-600 hover:text-teal-800 font-bold text-[11px] flex items-center gap-1 cursor-pointer">
                  <span>آپلود لوگو 📁</span>
                  <input 
                    type="file" 
                    accept="image/*" 
                    className="hidden" 
                    onChange={handleLogoUpload} 
                  />
                </label>
              )}
            </div>
          </div>

        </div>
      </section>

      {/* اگر کاربر وارد نشده بود، فرم لاگین جذاب روتر ممیزی و در دسترس بودن را نشان بده */}
      {!currentUser ? (
        <main className="flex-1 max-w-lg mx-auto w-full p-4 flex items-center justify-center py-16">
          <div className="bg-white rounded-2xl shadow-xl border border-slate-200 p-8 w-full space-y-6">
            <div className="text-center space-y-2">
              <div className="bg-gradient-to-b from-teal-500 to-teal-700 w-14 h-14 rounded-2xl mx-auto flex items-center justify-center text-white shadow-lg">
                <Database className="w-7 h-7" />
              </div>
              <h2 className="text-lg font-extrabold text-slate-900">طرح ممیزی اینترانت لوکال نیروگاه</h2>
              <p className="text-xs text-slate-500 leading-relaxed">به منظور تکمیل آرشیو ۱۰ ساله تجارب ضمنی، ابتدا با یکی از کدهای کارگزینی لاگین کنید.</p>
            </div>

            {loginError && (
              <div className="p-3 bg-red-50 text-red-800 rounded-lg text-xs font-bold border border-red-200 text-center">
                {loginError}
              </div>
            )}

            <form onSubmit={handleLogin} className="space-y-4 text-xs">
              <div>
                <label className="block font-bold text-slate-700 mb-1">شناسه کاربری پایگاه لوکال</label>
                <input 
                  type="text"
                  required
                  placeholder="مثال: admin یا user"
                  value={loginUsername}
                  onChange={(e) => setLoginUsername(e.target.value)}
                  className="w-full border p-3 rounded-xl focus:outline-teal-600 text-center font-bold text-sm"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">کلمه عبور امنیتی اینترانت</label>
                <input 
                  type="password"
                  required
                  placeholder="رمز نمونه: 123"
                  value={loginPassword}
                  onChange={(e) => setLoginPassword(e.target.value)}
                  className="w-full border p-3 rounded-xl focus:outline-teal-600 text-center font-bold text-sm"
                />
              </div>

              <button 
                type="submit"
                className="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 px-4 rounded-xl shadow-md transition-colors text-xs flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <LogIn className="w-4 h-4" />
                <span>ورود به سامانه ممیزی و مستندسازی ↩️</span>
              </button>
            </form>

            {/* بخش کلیک‌آسان ورودی مستقیم جهت تسریع تسک‌های ممیزی */}
            <div className="border-t pt-5">
              <span className="text-[10px] font-bold text-slate-400 block text-center mb-3">یا انتخاب یکی از نقش‌های پیش‌فرض برای ورود سریع آزمایشی:</span>
              <div className="grid grid-cols-3 gap-2">
                <button
                  type="button"
                  onClick={() => handleQuickLogin('admin')}
                  className="bg-teal-50 hover:bg-teal-100 border border-teal-200 text-teal-800 p-2 text-[10px] rounded-xl font-bold transition-all cursor-pointer text-center"
                >
                  👑 مدیر پورتال<br/><span className="text-[8px] opacity-75">(مهدی اسماعیلی)</span>
                </button>
                <button
                  type="button"
                  onClick={() => handleQuickLogin('expert')}
                  className="bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-800 p-2 text-[10px] rounded-xl font-bold transition-all cursor-pointer text-center"
                >
                  🔬 کارشناس ممیز<br/><span className="text-[8px] opacity-75">(دکتر علوی)</span>
                </button>
                <button
                  type="button"
                  onClick={() => handleQuickLogin('contributor')}
                  className="bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-800 p-2 text-[10px] rounded-xl font-bold transition-all cursor-pointer text-center"
                >
                  👷 ناظر میدانی<br/><span className="text-[8px] opacity-75">(مهندس رضایی)</span>
                </button>
              </div>
            </div>

          </div>
        </main>
      ) : (
        <>
          {/* بخش ناوبری اصلی پورتال ممیزی */}
          <nav className="bg-white border-b border-slate-200 shadow-sm print:hidden">
            <div className="max-w-7xl mx-auto px-4 flex flex-wrap gap-1">
              
              <button
                onClick={() => { setActiveTab('archive'); setSelectedEntry(null); }}
                className={`py-3.5 px-4 font-bold text-xs flex items-center gap-1.5 border-b-2 transition-all cursor-pointer ${
                  activeTab === 'archive' && !selectedEntry
                    ? 'border-teal-600 text-teal-700 bg-teal-50/50' 
                    : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50'
                }`}
              >
                <FolderOpen className="w-4 h-4 text-slate-400" />
                <span>آرشیو تجارب ممیزی‌شده ({filteredEntries.length})</span>
              </button>

              <button
                onClick={() => { setActiveTab('create'); setSelectedEntry(null); }}
                className={`py-3.5 px-4 font-bold text-xs flex items-center gap-1.5 border-b-2 transition-all cursor-pointer ${
                  activeTab === 'create' 
                    ? 'border-teal-600 text-teal-700 bg-teal-50/50' 
                    : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50'
                }`}
              >
                <PlusCircle className="w-4 h-4 text-slate-400" />
                <span>ثبت سند چالش و تجربه جدید</span>
              </button>

              {/* کارتابل ممیزی فقط مخصوص ادمین‌ها و اکسپرت‌ها است */}
              {['admin', 'expert'].includes(currentUser.role) && (
                <button
                  onClick={() => { setActiveTab('audit'); setSelectedEntry(null); }}
                  className={`py-3.5 px-4 font-bold text-xs flex items-center gap-1.5 border-b-2 transition-all relative cursor-pointer ${
                    activeTab === 'audit' 
                      ? 'border-amber-500 text-amber-800 bg-amber-50/50' 
                      : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50'
                  }`}
                >
                  <UserCheck className="w-4 h-4 text-amber-500" />
                  <span>کارتابل ممیزی و فیدبک</span>
                  {entries.filter(e => e.departmentId === activeDepartmentId && e.status === 'draft').length > 0 && (
                    <span className="bg-amber-600 text-white font-mono text-[9px] w-4.5 h-4.5 rounded-full flex items-center justify-center animate-bounce">
                      {entries.filter(e => e.departmentId === activeDepartmentId && e.status === 'draft').length}
                    </span>
                  )}
                </button>
              )}

              <button
                onClick={() => { setActiveTab('qa'); setSelectedEntry(null); }}
                className={`py-3.5 px-4 font-bold text-xs flex items-center gap-1.5 border-b-2 transition-all cursor-pointer ${
                  activeTab === 'qa' 
                    ? 'border-teal-600 text-teal-700 bg-teal-50/50' 
                    : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50'
                }`}
              >
                <MessageSquare className="w-4 h-4 text-slate-400" />
                <span>مشاوره‌ها و پرسش همکاران</span>
              </button>

              <button
                onClick={() => { setActiveTab('tips'); setSelectedEntry(null); }}
                className={`py-3.5 px-4 font-bold text-xs flex items-center gap-1.5 border-b-2 transition-all cursor-pointer ${
                  activeTab === 'tips' 
                    ? 'border-teal-600 text-teal-700 bg-teal-50/50' 
                    : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50'
                }`}
              >
                <ImageIcon className="w-4 h-4 text-slate-400" />
                <span>نکات تصویری و بهداشتی کارگاه</span>
              </button>

              <button
                onClick={() => { setActiveTab('php_src'); setSelectedEntry(null); }}
                className={`py-3.5 px-4 font-bold text-xs flex items-center gap-1.5 border-b-2 transition-all cursor-pointer ${
                  activeTab === 'php_src' 
                    ? 'border-indigo-600 text-indigo-700 bg-indigo-50/50' 
                    : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50'
                }`}
              >
                <FileCode className="w-4 h-4 text-indigo-500" />
                <span>کدهای آفلاین PHP برای سرور لوکال</span>
              </button>

              {/* ادمین ارشد کل سامانه حق مدیریت اعضاء را دارد */}
              {currentUser.role === 'admin' && (
                <button
                  onClick={() => { setActiveTab('users_mgmt'); setSelectedEntry(null); }}
                  className={`py-3.5 px-4 font-bold text-xs flex items-center gap-1.5 border-b-2 transition-all cursor-pointer ${
                    activeTab === 'users_mgmt' 
                      ? 'border-slate-800 text-slate-900 bg-slate-100' 
                      : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50'
                  }`}
                >
                  <Users className="w-4 h-4 text-slate-500" />
                  <span>مدیریت پرسنل و دسترسی‌ها ({users.length})</span>
                </button>
              )}

            </div>
          </nav>

          {/* بدنه محتوای پورتال ممیزی */}
          <main className="flex-1 max-w-7xl mx-auto w-full p-4 space-y-6">
            
            {/* ۱. آمارهای کلیدی سریع دپارتمان فعال */}
            {activeTab !== 'php_src' && activeTab !== 'users_mgmt' && !selectedEntry && (
              <div className="grid grid-cols-2 lg:grid-cols-5 gap-4 print:hidden">
                <div className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
                  <div>
                    <span className="text-2xl font-black text-slate-800">{stats.approved}</span>
                    <p className="text-[10px] text-slate-400 mt-1">تجربه ممیزی شده و سبز</p>
                  </div>
                  <div className="text-xl bg-emerald-50 text-emerald-600 p-2.5 rounded-xl">✓</div>
                </div>

                <div className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
                  <div>
                    <span className="text-2xl font-black text-slate-800">
                      {entries.filter(e => e.departmentId === activeDepartmentId && e.status === 'draft').length}
                    </span>
                    <p className="text-[10px] text-slate-400 mt-1">در انتظار کارشناسی ممیز</p>
                  </div>
                  <div className="text-xl bg-amber-50 text-amber-600 p-2.5 rounded-xl">⏳</div>
                </div>

                <div className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
                  <div>
                    <span className="text-2xl font-black text-slate-800">
                      {entries.filter(e => e.departmentId === activeDepartmentId && e.status === 'rejected').length}
                    </span>
                    <p className="text-[10px] text-slate-400 mt-1">مردود شده علمی نیاز به ویرایش</p>
                  </div>
                  <div className="text-xl bg-rose-50 text-rose-600 p-2.5 rounded-xl">❌</div>
                </div>

                <div className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
                  <div>
                    <span className="text-2xl font-black text-slate-800">{stats.pendingQ}</span>
                    <p className="text-[10px] text-slate-400 mt-1">پرسش فعال بی‌پاسخ</p>
                  </div>
                  <div className="text-xl bg-violet-50 text-violet-600 p-2.5 rounded-xl">❓</div>
                </div>

                <div className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
                  <div>
                    <span className="text-2xl font-black text-slate-800">{stats.totalVisual}</span>
                    <p className="text-[10px] text-slate-400 mt-1">تصویر هشدار و نکته</p>
                  </div>
                  <div className="text-xl bg-blue-50 text-blue-600 p-2.5 rounded-xl">📸</div>
                </div>
              </div>
            )}

            {/* ۲. تب مطالعه فیزیکی گزارش تک‌برگی رسمی (جهت پرینت و PDF با لوگو) */}
            {selectedEntry && (
              <div className="max-w-4xl mx-auto bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-6 print:shadow-none print:border-none print:p-0">
                
                {/* سربرگ رسمی مخصوص چاپ - مخفی در طراحی وب، نمایان در پرینت و پی‌دی‌اف و گزارش فیزیکی */}
                <div className="hidden print:flex justify-between items-center border-b-2 border-slate-900 pb-4 mb-4">
                  <div className="text-right text-xs space-y-1">
                    <div className="font-extrabold text-slate-950 text-sm">وزارت نیرو / شرکت بارز تولید نیروی مرکزی نیروگاه</div>
                    <div className="text-[10px] text-slate-500 font-medium">پورتال مرجع پایش و مستندسازی سیستمی دانش‌های فنی</div>
                    <div className="text-[10px] text-slate-500">منطقه ممیزی: {DEPARTMENTS.find(d => d.id === selectedEntry.departmentId)?.name}</div>
                  </div>
                  <div className="text-center">
                    <h2 className="text-base font-black text-slate-900">سند شناسنامه ممیزی و تجارب ثبت‌شده کارگاه</h2>
                    <span className="text-[9px] text-slate-500 font-bold bg-slate-100 rounded px-2.5 py-0.5 mt-1 inline-block">نسخه اینترانت لوکال رسمی</span>
                  </div>
                  <div className="flex flex-col items-center gap-1 shrink-0 text-left">
                    {logoUrl ? (
                      <img src={logoUrl} alt="لوگوی شرکت" className="h-[48px] max-w-[124px] object-contain rounded bg-white p-0.5 border" referrerPolicy="no-referrer" />
                    ) : (
                      <div className="bg-slate-900 text-white p-2 rounded h-11 w-11 flex items-center justify-center font-bold text-xs font-mono">
                        Power
                      </div>
                    )}
                    <span className="text-[8px] text-slate-400 font-mono">ID: #{selectedEntry.id}</span>
                  </div>
                </div>

                {/* سربرگ آنلاین (مخصوص نمایش وب، مخفی در پرینت) */}
                <div className="flex justify-between items-start gap-4 border-b pb-4 print:hidden">
                  <div>
                    <span className="text-xs bg-teal-50 text-teal-700 border border-teal-200 px-3 py-1 rounded-full">{selectedEntry.categoryName}</span>
                    <h2 className="text-base font-black text-slate-800 mt-2">🔍 گزارش فنی شماره {selectedEntry.id} - {selectedEntry.title}</h2>
                  </div>
                  <div className="flex gap-2 shrink-0">
                    <button 
                      onClick={() => window.print()}
                      className="text-xs bg-teal-600 hover:bg-teal-500 text-white border border-teal-700 px-3 py-1.5 rounded-lg flex items-center gap-1.5 shadow-sm font-bold cursor-pointer transition-colors"
                    >
                      📊 چاپ گزارش تک‌برگی (PDF)
                    </button>
                    <button 
                      onClick={() => setSelectedEntry(null)}
                      className="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 border px-3 py-1.5 rounded-lg cursor-pointer"
                    >
                      بازگشت به آرشیو ↩️
                    </button>
                  </div>
                </div>

                {/* عنوان چاپی مخصوص چاپی پرینت */}
                <div className="hidden print:block space-y-1">
                  <div className="text-right text-[11px] text-slate-600 font-bold">دسته موضوعی: {selectedEntry.categoryName}</div>
                  <h3 className="text-sm font-black text-slate-900">سند دانش غنی: {selectedEntry.title}</h3>
                </div>

                {/* متادیتا */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100 text-xs">
                  <div>
                    <span className="text-slate-400">ثبت‌کننده تجربه:</span>
                    <p className="font-bold text-slate-800 mt-0.5">{selectedEntry.author}</p>
                  </div>
                  <div>
                    <span className="text-slate-400">تاریخ ثبت پورتال:</span>
                    <p className="font-bold text-slate-800 mt-0.5">{selectedEntry.dateOccurred}</p>
                  </div>
                  <div>
                    <span className="text-slate-400">مقدار فرکانس کلیک:</span>
                    <p className="font-bold text-slate-800 mt-0.5">{selectedEntry.views} مرتبه بازیابی</p>
                  </div>
                  <div>
                    <span className="text-slate-400">وضعیت تایید علمی:</span>
                    <p className="mt-0.5">
                      {selectedEntry.status === 'approved' && <span className="text-emerald-700 font-bold">✓ مصوب و منتشر شده</span>}
                      {selectedEntry.status === 'draft' && <span className="text-amber-700 font-bold">⚠️ پیش‌نویس موقت</span>}
                      {selectedEntry.status === 'rejected' && <span className="text-red-700 font-bold">❌ اصلاحیه مدیریتی</span>}
                    </p>
                  </div>
                </div>

                {/* بند شرح چالش کارگاه */}
                <div className="space-y-1">
                  <h4 className="text-xs font-black text-red-600 flex items-center gap-2">
                    <span>🚨</span>
                    <span>یک: شرح چالش یا مسئله مخل بهره‌وری مطرح شده در نیروگاه:</span>
                  </h4>
                  <p className="bg-slate-50 border-r-4 border-red-500 p-4 rounded-l-lg text-slate-700 leading-relaxed text-justify text-xs whitespace-pre-line">
                    {selectedEntry.problem}
                  </p>
                </div>

                {/* بند راهکار اعمال شده */}
                <div className="space-y-1">
                  <h4 className="text-xs font-black text-emerald-700 flex items-center gap-2">
                    <span>✅</span>
                    <span>دو: اقدامات نظارتی، تاتامی و مهندسی اعمال شده جهت مهار:</span>
                  </h4>
                  <p className="bg-slate-50 border-r-4 border-emerald-600 p-4 rounded-l-lg text-slate-700 leading-relaxed text-justify text-xs whitespace-pre-line">
                    {selectedEntry.solution}
                  </p>
                </div>

                {/* بند بازخورد یا حاصل عملکرد */}
                {selectedEntry.result && (
                  <div className="space-y-1">
                    <h4 className="text-xs font-black text-slate-800 flex items-center gap-2">
                      <span>📊</span>
                      <span>سه: نتایج حاصله و صرفه‌جویی‌های رفاهی - اقتصادی متعاقب بهره‌برداری:</span>
                    </h4>
                    <p className="bg-slate-50 border-r-4 border-slate-700 p-4 rounded-l-lg text-slate-700 leading-relaxed text-justify text-xs whitespace-pre-line">
                      {selectedEntry.result}
                    </p>
                  </div>
                )}

                {/* ضمیمه‌های چندرسانه‌ای ذخیره شده */}
                {selectedEntry.mediaUrl && (
                  <div className="space-y-2 print:hidden">
                    <h4 className="text-xs font-bold text-slate-700">📎 پیوست چندرسانه‌ای ناظر کارگاه:</h4>
                    <div className="p-3 bg-slate-100 rounded-lg border border-slate-200 max-w-lg">
                      {selectedEntry.mediaType === 'image' && (
                        <img src={selectedEntry.mediaUrl} alt="پیوست تصویر چالش" className="w-full max-h-80 object-contain rounded border pointer-events-none" referrerPolicy="no-referrer" />
                      )}
                      {selectedEntry.mediaType === 'video' && (
                        <video src={selectedEntry.mediaUrl} controls className="w-full max-h-80 rounded border" />
                      )}
                      {selectedEntry.mediaType === 'audio' && (
                        <div className="flex items-center gap-3 p-2 bg-white rounded border">
                          <AudioIcon className="w-8 h-8 text-teal-600 animate-pulse" />
                          <audio src={selectedEntry.mediaUrl} controls className="w-full h-8" />
                        </div>
                      )}
                    </div>
                  </div>
                )}

                {/* پاورقی امضای ممیز در برگه پرینت */}
                <div className="hidden print:grid grid-cols-2 gap-8 pt-12 text-xs border-t mt-12">
                  <div className="text-center space-y-4">
                    <p className="text-slate-400">ناظر ثبت‌کننده رویداد:</p>
                    <div className="h-10" />
                    <p className="font-bold text-slate-700">{selectedEntry.author}</p>
                  </div>
                  <div className="text-center space-y-4">
                    <p className="text-slate-400">کارشناس ممیز عالی و مدیر مسئول پورتال:</p>
                    <div className="h-10" />
                    <p className="font-extrabold text-slate-900">مهدی اسماعیلی - تایید نهایی</p>
                  </div>
                </div>

              </div>
            )}

            {/* ۳. تب آرشیو اسناد (لیست و فیلترها) */}
            {activeTab === 'archive' && !selectedEntry && (
              <div className="space-y-6">
                
                {/* بخش فیلترهای پیشرفته جستجو */}
                <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-5 print:hidden">
                  <div className="flex items-center gap-1.5 pb-3 border-b border-slate-100 mb-4">
                    <Filter className="w-4 h-4 text-teal-600" />
                    <h3 className="font-bold text-xs text-slate-700">جعبه فیلتر هوشمند و کلمات کلیدی پایگاه دانش</h3>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                    
                    {/* فیلد ورودی متن یا تگ */}
                    <div className="relative">
                      <label className="block font-bold text-slate-600 mb-1">جستجوی آزاد (عنوان، مسئله یا رسته)</label>
                      <div className="relative">
                        <input 
                          type="text"
                          value={searchTerm}
                          onChange={(e) => setSearchTerm(e.target.value)}
                          placeholder="تایپ کنید... (مثال: تاتامی، بویلر)"
                          className="w-full border p-2 rounded-lg bg-slate-50 focus:bg-white focus:outline-teal-600 pr-8"
                        />
                        <Search className="w-4 h-4 text-slate-400 absolute right-2.5 top-3" />
                      </div>
                    </div>

                    {/* فیلتر رسته دپارتمانی */}
                    <div>
                      <label className="block font-bold text-slate-600 mb-1">رسته اختصاصی دپارتمان فعال</label>
                      <select
                        value={filterCategory}
                        onChange={(e) => setFilterCategory(e.target.value)}
                        className="w-full border p-2 rounded-lg bg-white"
                      >
                        <option value="all">کلیه رسته‌های فنی</option>
                        {CATEGORIES.filter(c => c.departmentId === activeDepartmentId).map(c => (
                          <option key={c.id} value={c.id}>{c.name}</option>
                        ))}
                      </select>
                    </div>

                    {/* فیلتر پیوست‌های چندرسانه‌ای */}
                    <div>
                      <label className="block font-bold text-slate-600 mb-1">فرمت پرونده رسانه‌ای</label>
                      <select
                        value={filterMedia}
                        onChange={(e) => setFilterMedia(e.target.value as any)}
                        className="w-full border p-2 rounded-lg bg-white"
                      >
                        <option value="all">همه اسناد (با/بدون پیوست)</option>
                        <option value="image">دارای تصویر کارگاهی</option>
                        <option value="video">دارای فیلم کوتاه</option>
                        <option value="audio">دارای صدای ضبط‌شده ناظر</option>
                      </select>
                    </div>

                    {/* فیلتر وضعیت ممیزی (ویژه ادمین و ممیز ارشد) */}
                    <div>
                      {['admin', 'expert'].includes(currentUser.role) ? (
                        <>
                          <label className="block font-bold text-amber-800 mb-1">متریک ارزیابی ممیزی</label>
                          <select
                            value={filterStatus}
                            onChange={(e) => setFilterStatus(e.target.value)}
                            className="w-full border border-amber-300 p-2 rounded-lg bg-amber-50/20 font-bold"
                          >
                            <option value="all">همه وضعیت‌ها</option>
                            <option value="approved">سبز: مصوب شده علمی</option>
                            <option value="draft">زرد: پیش‌نویس موقت</option>
                            <option value="rejected">قرمز: مردود نیاز به بازنگری</option>
                          </select>
                        </>
                      ) : (
                        <div className="flex items-end h-full">
                          <span className="text-[10px] text-teal-700 bg-teal-50 border border-teal-100 p-2 rounded-lg w-full text-center font-bold">
                            🔒 فیلتر حریم: نمایش اسناد مصوب
                          </span>
                        </div>
                      )}
                    </div>

                  </div>
                </div>

                {/* پنل نتایج جستجوی اسناد */}
                <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                  
                  <div className="px-5 py-4 border-b flex justify-between items-center bg-slate-100">
                    <h3 className="font-extrabold text-xs text-slate-800 flex items-center gap-2">
                      <span>📂</span>
                      <span>سوابق و پرونده‌های مستند دانش ({filteredEntries.length} مورد یافته شده)</span>
                    </h3>
                    <span className="text-[10px] bg-teal-600 text-white px-3 py-1 rounded-full font-black">
                      دپارتمان: {DEPARTMENTS.find(d => d.id === activeDepartmentId)?.name}
                    </span>
                  </div>

                  {filteredEntries.length === 0 ? (
                    <div className="p-16 text-center text-slate-400 space-y-2">
                      <FolderOpen className="w-12 h-12 mx-auto stroke-1" />
                      <p className="font-bold text-xs">هیچ تجربه مصوب یا ثبتی مطابق پارامترهای برگزیده جستجو یافت نشد.</p>
                      <p className="text-[10px] opacity-75">دکمه شبیه‌ساز یا رسته جستجو کاما را کنترل کنید.</p>
                    </div>
                  ) : (
                    <div className="divide-y divide-slate-100 text-xs">
                      {filteredEntries.map(entry => (
                        <div 
                          key={entry.id} 
                          className="p-5 hover:bg-slate-50 transition-colors flex flex-col md:flex-row md:items-start justify-between gap-4"
                        >
                          <div className="space-y-1.5 flex-1 text-right">
                            <div className="flex flex-wrap items-center gap-2">
                              <span className="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-bold">کد: {entry.id}</span>
                              <span className="bg-teal-50 text-teal-800 border border-teal-100 px-2 py-0.5 rounded text-[10px] font-bold">{entry.categoryName}</span>
                              <h4 className="font-extrabold text-slate-900 text-sm hover:text-teal-700 cursor-pointer" onClick={() => { setSelectedEntry(entry); entry.views++; }}>{entry.title}</h4>
                              
                              {entry.status === 'draft' && <span className="text-[9px] bg-amber-100 text-amber-800 font-bold px-1.5 py-0.5 rounded border border-amber-300">⏳ پیش‌نویس ممیزی نشده</span>}
                              {entry.status === 'rejected' && <span className="text-[9px] bg-rose-100 text-rose-800 font-bold px-1.5 py-0.5 rounded border border-rose-300">⚠️ مردود نیاز به بازنویسی علمی</span>}
                              
                              {/* نماد فرمت رسانه */}
                              {entry.mediaType === 'image' && <span className="text-[10px] text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">📸 تصویر</span>}
                              {entry.mediaType === 'video' && <span className="text-[10px] text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">🎥 فیلم</span>}
                              {entry.mediaType === 'audio' && <span className="text-[10px] text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">🔊 رکوردصدا</span>}
                            </div>

                            <p className="text-[10px] text-slate-400">ثبت از: <strong>{entry.author}</strong> | تاریخ: {entry.dateOccurred} | تعداد مطالعه: {entry.views} مرتبه</p>
                            
                            <p className="text-slate-600 line-clamp-2 text-justify select-none leading-relaxed">
                              <strong>چالش عیبیابی:</strong> {entry.problem}
                            </p>

                            {/* تگ‌های کلیدواژه‌ها */}
                            {entry.keywords.length > 0 && (
                              <div className="flex flex-wrap gap-1 pt-1">
                                {entry.keywords.map((kw, idx) => (
                                  <span key={idx} className="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-[5px] text-[10px]">#{kw}</span>
                                ))}
                              </div>
                            )}

                            {/* نمایش تذکر ممیز در صورت رد شدن */}
                            {entry.status === 'rejected' && entry.rejectionReason && (
                              <div className="bg-rose-50 border border-rose-200 text-rose-800 p-2.5 rounded-lg mt-2 text-[10px] leading-relaxed">
                                <strong>تذکر و پیشنهاد اصلاحی ممیز ارشد:</strong> {entry.rejectionReason}
                              </div>
                            )}
                          </div>

                          <div className="flex md:flex-col gap-2 shrink-0 justify-end">
                            <button
                              onClick={() => { setSelectedEntry(entry); entry.views++; }}
                              className="bg-slate-900 text-white hover:bg-slate-800 text-xs px-3.5 py-2 rounded-lg font-bold flex items-center gap-1 cursor-pointer transition-colors"
                            >
                              <span>شناسنامه و چاپ</span>
                              <ChevronLeft className="w-3.5 h-3.5" />
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}

                </div>

                {/* بخش چارت یا آمار فراوانی در پایین پورتال */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                  
                  {/* چارت فراوانی دسته‌بندی‌ها به طور بومی */}
                  <div className="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <h3 className="text-xs font-bold text-slate-700 flex items-center gap-2 mb-4 border-b pb-3">
                      <span>📊</span>
                      <span>فراوانی اسناد مصوب در رسته‌های دپارتمان فعال</span>
                    </h3>

                    <div className="space-y-3.5 text-xs">
                      {stats.categoryDistribution.map((cat, idx) => {
                        const pct = stats.approved > 0 ? (cat.count / stats.maxCount) * 100 : 0;
                        return (
                          <div key={idx} className="flex items-center gap-4">
                            <span className="w-1/3 truncate text-slate-600 font-bold" title={cat.name}>{cat.name}</span>
                            <div className="flex-1 bg-slate-100 rounded-full h-4 overflow-hidden">
                              <div 
                                style={{ width: `${pct}%` }}
                                className="bg-gradient-to-r from-teal-500 to-teal-700 h-full rounded-full transition-all duration-500"
                              />
                            </div>
                            <span className="w-12 text-left font-black text-slate-700">{cat.count} سند</span>
                          </div>
                        );
                      })}
                    </div>
                  </div>

                  {/* نخبگان مشارکت کننده */}
                  <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex flex-col justify-between text-xs">
                    <div className="space-y-3">
                      <h3 className="font-extrabold text-slate-700 border-b pb-3 flex items-center gap-1.5">
                        <Award className="w-4 h-4 text-amber-500" />
                        <span>سرپرستان ناظر فعال این دپارتمان</span>
                      </h3>
                      <ul className="space-y-2.5">
                        <li className="flex justify-between items-center bg-slate-50 p-2.5 rounded-lg border">
                          <span className="font-bold">🥇 دکتر علوی</span>
                          <span className="bg-teal-100 text-teal-800 text-[10px] font-bold px-2 py-0.5 rounded-full">۲ تجربه مصوب</span>
                        </li>
                        <li className="flex justify-between items-center bg-slate-50 p-2.5 rounded-lg border">
                          <span className="font-bold">🥈 مهندس رضایی</span>
                          <span className="bg-teal-100 text-teal-800 text-[10px] font-bold px-2 py-0.5 rounded-full">۱ تجربه مصوب</span>
                        </li>
                        <li className="flex justify-between items-center bg-slate-50 p-2.5 rounded-lg border">
                          <span className="font-bold">🥉 مهندس عباسی</span>
                          <span className="bg-slate-100 text-slate-500 text-[10px] px-2 py-0.5 rounded-full">پیش‌نویس جدید</span>
                        </li>
                      </ul>
                    </div>

                    <div className="bg-teal-50 text-teal-800 p-3 rounded-lg border border-teal-200 leading-relaxed mt-4 text-[10px] text-justify">
                      💡 <strong>راهنما:</strong> اسناد ثبت‌شده ناظران جدیداً به صورت پیش‌نویس ذخیره شده و پس از ممیزی کامل توسط کارشناس ممیز ارشد، در آرشیو عمومی اینترانت سازمان نمایان خواهند شد.
                    </div>
                  </div>

                </div>

              </div>
            )}

            {/* ۴. تب ثبت سند جدید (با درگ اند دراپ و شبیه‌ساز آپلود رسانه) */}
            {activeTab === 'create' && (
              <div className="max-w-4xl mx-auto bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-6 text-right">
                <div>
                  <h3 className="text-base font-black text-slate-900">برگه رسمی ثبت چالش و تجربه مهار سازه‌ای کارگاه</h3>
                  <p className="text-xs text-slate-500 mt-1">اطلاعات ضمنی حاصله از فعالیت ناظرات میدانی جهت غنی‌سازی مستمر پرسال دپارتمان فنی</p>
                </div>

                {formSuccess && (
                  <div className="p-4 bg-emerald-50 text-emerald-800 text-xs font-bold rounded-xl border border-emerald-250 animate-fade-in text-center">
                    {formSuccess}
                  </div>
                )}

                <form onSubmit={handleCreateKnowledge} className="space-y-5 text-xs">
                  
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="md:col-span-2">
                      <label className="block font-bold text-slate-700 mb-1">عنوان کلیدی دستاورد یا چالش رویداد *</label>
                      <input 
                        type="text"
                        required
                        value={formTitle}
                        onChange={(e) => setFormTitle(e.target.value)}
                        className="w-full border p-2.5 rounded-lg focus:outline-teal-500 bg-slate-50"
                        placeholder="مثال: روش مهار جلبک سالن بدنسازی / خنک‌سازی دیواره سوپرهیتر بویلر"
                      />
                    </div>

                    <div>
                      <label className="block font-bold text-slate-700 mb-1">رسته/موضوع فنی پروژه *</label>
                      <select
                        value={formCategory}
                        onChange={(e) => setFormCategory(e.target.value)}
                        className="w-full border p-2.5 rounded-lg bg-white"
                        required
                      >
                        {CATEGORIES.filter(c => c.departmentId === activeDepartmentId).map(c => (
                          <option key={c.id} value={c.id}>{c.name}</option>
                        ))}
                      </select>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block font-bold text-slate-700 mb-1">یک: شرح کامل چالش و عواقب نفوذ (مانند خسارت، آسیب پوستی پرسنل، افت تولید) *</label>
                      <textarea
                        required
                        rows={4}
                        value={formProblem}
                        onChange={(e) => setFormProblem(e.target.value)}
                        className="w-full border p-2.5 rounded-lg focus:outline-teal-500 text-justify bg-slate-50"
                        placeholder="مشکل مواجه شده را با ابعاد عددی و توصیفی وارد نمایید..."
                      />
                    </div>

                    <div>
                      <label className="block font-bold text-slate-700 mb-1">دو: راهکار اتخاذ شده و مهار فنی (اقلام نصب شده، گرید مواد، استانداردهای نظارت) *</label>
                      <textarea
                        required
                        rows={4}
                        value={formSolution}
                        onChange={(e) => setFormSolution(e.target.value)}
                        className="w-full border p-2.5 rounded-lg focus:outline-teal-500 text-justify bg-slate-50"
                        placeholder="تشریح نمایید چه اقداماتی انجام شد تا چالش به طور کامل و ریشه‌ای برطرف شود..."
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block font-bold text-slate-700 mb-1">سه: نتایج حاصله، دستاوردهای رفاهی-حقوقی و اقتصادی (ویژگی اختیاری)</label>
                    <textarea
                      rows={2}
                      value={formResult}
                      onChange={(e) => setFormResult(e.target.value)}
                      className="w-full border p-2.5 rounded-lg focus:outline-teal-500 text-justify bg-slate-50"
                      placeholder="مانند کاهش غلظت هاگ قارچی به حد بهینه زیستی / افزایش طول عمر تجهیزات بویلر..."
                    />
                  </div>

                  {/* گام کلیدواژه‌ها */}
                  <div>
                    <label className="block font-bold text-slate-700 mb-1">کلمات کلیدی و مگا کدهای جستجو (تفکیک با کاما یا ویرگول)</label>
                    <input 
                      type="text"
                      value={formKeywords}
                      onChange={(e) => setFormKeywords(e.target.value)}
                      className="w-full border p-2.5 rounded-lg focus:outline-teal-500 bg-slate-50 text-left font-mono"
                      placeholder="مثال: رطوبت, تاتامی_ورزشی, بویلر, ایمنی"
                    />
                  </div>

                  {/* آپلود هوشمند و درگ اند دراپ پیوست رسانه‌ای (امضای ممیزی ۱۰ ساله) */}
                  <div>
                    <span className="block font-bold text-slate-700 mb-2">📎 آپلود پیوست‌های الکترونیکی ناظر (تصویر ملموس ممیزی، فیلم یا صدای ضبط شده ناظر پایگاه)</span>
                    <div 
                      onDragOver={handleDragOver}
                      onDragLeave={handleDragLeave}
                      onDrop={handleDrop}
                      className={`border-2 border-dashed rounded-xl p-6 text-center transition-all cursor-pointer ${
                        isDragOver ? 'border-teal-500 bg-teal-50/50' : 'border-slate-300 bg-slate-50 hover:bg-slate-100/50'
                      }`}
                    >
                      <input 
                        type="file"
                        id="form_file_input"
                        className="hidden" 
                        onChange={handleFileChange}
                        accept="image/*,video/*,audio/*"
                      />
                      <label htmlFor="form_file_input" className="cursor-pointer block space-y-3">
                        <UploadCloud className="w-10 h-10 text-slate-400 mx-auto" />
                        <div>
                          <p className="font-bold text-xs text-teal-700">فایل گزارش را به اینجا درگ کنید یا کلیک نمایید</p>
                          <p className="text-[10px] text-slate-400 mt-1">پذیرش تصاویر تاتامی و بویلر، فیلم‌های نظارتی کارگاه یا فایل صدای ضبط شده تکنسین</p>
                        </div>
                      </label>
                    </div>

                    {/* نمایش لایو وضعیت آپلود */}
                    {uploadProgress !== null && (
                      <div className="bg-slate-100 border p-3.5 rounded-xl text-xs space-y-2 mt-3">
                        <div className="flex justify-between font-bold text-slate-700">
                          <span>در حال باگذاری فایل: <strong>{formMediaFile?.name}</strong></span>
                          <span>{uploadProgress}%</span>
                        </div>
                        <div className="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                          <div 
                            className="bg-teal-600 h-full transition-all duration-300 rounded-full" 
                            style={{ width: `${uploadProgress}%` }}
                          />
                        </div>
                        {uploadProgress === 100 && (
                          <div className="flex items-center gap-1.5 text-emerald-700 font-bold text-[10px] pt-1">
                            <span>✓ پردازش محلی به اتمام رسید. نوع شناسایی شده:</span>
                            {formMediaType === 'image' && <span className="bg-emerald-100 px-2 py-0.5 rounded text-[9px]">تصویر گزارش</span>}
                            {formMediaType === 'video' && <span className="bg-emerald-100 px-2 py-0.5 rounded text-[9px]">فیلم ممیزی</span>}
                            {formMediaType === 'audio' && <span className="bg-emerald-100 px-2 py-0.5 rounded text-[9px]">صدای رکورد شده ناظر دپارتمان</span>}
                          </div>
                        )}
                      </div>
                    )}
                  </div>

                  <div className="flex gap-2 justify-end pt-4 border-t">
                    <button 
                      type="submit" 
                      className="bg-indigo-950 hover:bg-indigo-900 border border-indigo-900 text-white font-extrabold py-2.5 px-6 rounded-lg shadow-sm transition-colors cursor-pointer"
                    >
                      ثبت نهایی اطلاعات به عنوان پیش‌نویس موقت 🗳️
                    </button>
                  </div>
                </form>
              </div>
            )}

            {/* ۵. کارتابل ممیزی برای مدیر و ممیز ارشد */}
            {activeTab === 'audit' && ['admin', 'expert'].includes(currentUser.role) && (
              <div className="space-y-6">
                
                <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                  <h3 className="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                    <FolderLock className="w-5 h-5 text-amber-500 animate-pulse" />
                    <span>کارتابل تخصصی باز ارزیابی و ممیزی کیفی تجارب ثبت شده دپارتمان فعال</span>
                  </h3>
                  <p className="text-xs text-slate-500 mt-1">کلیه فایل‌های باگذاری‌شده در وضعیت draft می‌باشند که پس از تحلیل، با تایید شما وارد آرشیو و پی‌دی‌اف عمومی نیروگاه خواهند شد.</p>
                </div>

                {entries.filter(e => e.departmentId === activeDepartmentId && e.status === 'draft').length === 0 ? (
                  <div className="bg-white rounded-xl border border-dashed border-slate-300 p-16 text-center text-slate-400 space-y-2">
                    <CheckCircle2 className="w-12 h-12 text-emerald-500 mx-auto" strokeWidth={1.5} />
                    <p className="font-bold text-xs">سامانه کاملاً ممیزی شده است.</p>
                    <p className="text-[10px]">هیچ پرونده در صف ممیزی دپارتمان {DEPARTMENTS.find(d => d.id === activeDepartmentId)?.name} یافت نشد.</p>
                  </div>
                ) : (
                  <div className="space-y-4">
                    {entries.filter(e => e.departmentId === activeDepartmentId && e.status === 'draft').map(entry => (
                      <div key={entry.id} className="bg-white rounded-xl shadow-sm border border-amber-300 p-5 space-y-4">
                        
                        <div className="flex justify-between items-start gap-4 flex-wrap border-b pb-3">
                          <div>
                            <span className="text-[10px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded font-black">شناسه موقت: {entry.id}</span>
                            <h4 className="font-extrabold text-sm text-slate-900 mt-1.5">{entry.title}</h4>
                            <p className="text-[10px] text-slate-400 mt-1">ثبت کننده: {entry.author} ({entry.authorRole}) | موضوع: {entry.categoryName}</p>
                          </div>
                          
                          <div className="flex gap-2">
                            <button
                              onClick={() => handleAuditApprove(entry.id)}
                              className="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors cursor-pointer"
                            >
                              تایید و انتشار عمومی ✓
                            </button>
                            <button
                              onClick={() => {
                                if (activeRejectionInputId === entry.id) {
                                  setActiveRejectionInputId(null);
                                } else {
                                  setActiveRejectionInputId(entry.id);
                                }
                              }}
                              className="bg-rose-600 hover:bg-rose-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors cursor-pointer"
                            >
                              رد و ارجاع به اصلاحیه ❌
                            </button>
                          </div>
                        </div>

                        {/* جزئیات برای بررسی ممیز */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs leading-relaxed text-justify">
                          <div className="bg-slate-50 p-3 rounded-lg border">
                            <strong className="text-red-700 block mb-1">شرح مسئله ثبت شده:</strong>
                            <p className="text-slate-700">{entry.problem}</p>
                          </div>
                          <div className="bg-slate-50 p-3 rounded-lg border">
                            <strong className="text-emerald-700 block mb-1">شرح راهکار اتخاذ شده:</strong>
                            <p className="text-slate-700">{entry.solution}</p>
                          </div>
                        </div>

                        {/* فیلد ورودی تذکرهای ممیز ارشد */}
                        {activeRejectionInputId === entry.id && (
                          <div className="bg-rose-50 border border-rose-200 p-4 rounded-xl space-y-3">
                            <label className="block text-xs font-bold text-rose-800">تذکر ممیزی خود را تشریح نمایید تا ناظر پرونده آن را اصلاح کند:</label>
                            <textarea
                              rows={2}
                              value={rejectionComments[entry.id] || ''}
                              onChange={(e) => setRejectionComments({ ...rejectionComments, [entry.id]: e.target.value })}
                              placeholder="مثال: دوز مواد گرانولی رزین در شرح راهکار قید نشده است. لطفاً اصلاح شود."
                              className="w-full border border-rose-300 p-2.5 rounded-lg text-xs focus:outline-rose-500 bg-white"
                            />
                            <div className="flex gap-2 justify-end">
                              <button 
                                onClick={() => handleAuditReject(entry.id)}
                                className="bg-rose-700 hover:bg-rose-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold cursor-pointer"
                              >
                                ثبت قطعی مردود علمی ↩️
                              </button>
                            </div>
                          </div>
                        )}

                      </div>
                    ))}
                  </div>
                )}

              </div>
            )}

            {/* ۶. تب فروم چالش‌ها و مکالمات مشورتی ناظران پروژه */}
            {activeTab === 'qa' && (
              <div className="space-y-6 text-right">
                
                <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                  <h3 className="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                    <Clock className="w-5 h-5 text-teal-600" />
                    <span>فروم همفکری و برطرف‌سازی چالش‌های زنده دپارتمان فنی</span>
                  </h3>
                  <p className="text-xs text-slate-500 mt-1">ناظران گرامی در صورت به وجود آمدن بن‌بست فکری در حین اجرا، چالش خود را با ممیزان و نوابغ به اشتراک بگذارید.</p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                  
                  {/* لیست چالش‌های موجود دپارتمان */}
                  <div className="lg:col-span-2 space-y-4">
                    <h4 className="font-extrabold text-xs text-slate-600 border-b pb-2 flex items-center gap-1.5">
                      <span>💬</span>
                      <span>مکالمات روانِ ناظران پیرامون مشکلات جاری دپارتمان</span>
                    </h4>

                    {questions.filter(q => q.departmentId === activeDepartmentId).length === 0 ? (
                      <div className="bg-white rounded-xl border p-12 text-center text-slate-400 text-xs">
                        هیچ چالشی برای دپارتمان فعال ثبت نشده است. اولین نفری باشید که چالش عملیاتی خود را ابراز می‌دارد.
                      </div>
                    ) : (
                      questions.filter(q => q.departmentId === activeDepartmentId).map(q => (
                        <div 
                          key={q.id} 
                          className={`bg-white rounded-xl border p-5 space-y-4 relative ${
                            q.status === 'resolved' ? 'border-2 border-emerald-500 bg-emerald-50/10' : 'border-slate-200 shadow-sm'
                          }`}
                        >
                          {q.status === 'resolved' && (
                            <span className="absolute top-4 left-4 bg-emerald-100 text-emerald-800 font-bold px-3 py-1 rounded-full text-[10px] border border-emerald-300">
                              ✓ مهار شده علمی
                            </span>
                          )}

                          <div className="flex items-center gap-2">
                            {q.priority === 'critical' && <span className="bg-red-100 text-red-800 font-bold px-2 py-0.5 rounded text-[9px] border border-red-300">🚨 بحرانی</span>}
                            {q.priority === 'urgent' && <span className="bg-orange-100 text-orange-850 font-bold px-2 py-0.5 rounded text-[9px] border border-orange-300">⚠️ فوری</span>}
                            {q.priority === 'normal' && <span className="bg-slate-100 text-slate-600 font-bold px-2 py-0.5 rounded text-[9px]">عادی</span>}
                            <h5 className="font-extrabold text-sm text-slate-900">{q.title}</h5>
                          </div>

                          <p className="text-xs text-slate-600 bg-slate-50/50 p-3 rounded-lg border text-justify whitespace-pre-line leading-relaxed">
                            {q.questionText}
                          </p>

                          <div className="text-[10px] text-slate-400 flex justify-between border-b pb-2 font-bold">
                            <span>طراح چالش: {q.author} | تاریخ درج: {q.createdAt}</span>
                            <span>{q.answers.length} پیشنهاد ثبت شده</span>
                          </div>

                          {/* پاسخ‌های ابراز شده */}
                          <div className="space-y-2 mr-4 md:mr-6 text-xs">
                            {q.answers.map(ans => (
                              <div 
                                key={ans.id} 
                                className={`p-3 rounded-lg leading-relaxed relative border ${
                                  ans.isAccepted ? 'bg-emerald-50 border-emerald-400' : 'bg-slate-50 border-slate-200'
                                }`}
                              >
                                {ans.isAccepted && (
                                  <span className="absolute top-2 left-2 text-[9px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full border border-emerald-300">
                                    🥇 نظریه گزیده ناظر ارشد
                                  </span>
                                )}
                                <p className="text-slate-800 text-justify font-medium">{ans.answerText}</p>
                                <div className="text-[9px] text-slate-400 flex justify-between pt-2">
                                  <span>مشاور: {ans.replierName} ({ans.replierRole})</span>
                                  <div className="flex gap-2 items-center">
                                    <span>ثبت: {ans.createdAt}</span>
                                    {currentUser.id === q.userId && !ans.isAccepted && q.status !== 'resolved' && (
                                      <button
                                        onClick={() => handleAcceptAnswer(q.id, ans.id)}
                                        className="bg-emerald-600 text-white font-bold px-2 py-0.5 rounded text-[9px] cursor-pointer hover:bg-emerald-500"
                                      >
                                        ✓ انتخاب پاسخ به عنوان طلایی
                                      </button>
                                    )}
                                  </div>
                                </div>
                              </div>
                            ))}
                          </div>

                          {/* فرم ارسال پاسخ برای سوال جاری */}
                          <div className="mr-4 md:mr-6 flex gap-2">
                            <input 
                              type="text"
                              value={ansInputMap[q.id] || ''}
                              onChange={(e) => setAnsInputMap({ ...ansInputMap, [q.id]: e.target.value })}
                              placeholder="پیشنهاد مشورتی کارآمد خود را وارد نمایید..."
                              className="flex-1 border p-2.5 rounded-lg text-xs bg-slate-50 focus:bg-white focus:outline-teal-500"
                            />
                            <button
                              onClick={() => handlePostAnswer(q.id)}
                              className="bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs px-4 py-2 rounded-lg cursor-pointer shrink-0 transition-all"
                            >
                              فرستادن راهکار ↩️
                            </button>
                          </div>

                        </div>
                      ))
                    )}
                  </div>

                  {/* فرم ثبت چالش جدید */}
                  <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-5 h-fit space-y-4">
                    <h4 className="font-extrabold text-xs text-slate-700 border-b pb-2">➕ ثبت یا ابراز چالش جدید در جبهه کاری</h4>
                    
                    <form onSubmit={handlePostQuestion} className="space-y-4 text-xs">
                      <div>
                        <label className="block font-bold text-slate-600 mb-1">عنوان موضوعی چالش</label>
                        <input 
                          type="text"
                          required
                          value={qTitle}
                          onChange={(e) => setQTitle(e.target.value)}
                          placeholder="مثال: نشت آب جکهای هیدرولیک"
                          className="w-full border p-2.5 rounded-lg bg-slate-50"
                        />
                      </div>

                      <div>
                        <label className="block font-bold text-slate-600 mb-1">متن توضیحی سوال و شواهد میدانی</label>
                        <textarea
                          required
                          rows={4}
                          value={qText}
                          onChange={(e) => setQText(e.target.value)}
                          placeholder="موضوع را کامل تشریح کنید..."
                          className="w-full border p-2.5 rounded-lg bg-slate-50"
                        />
                      </div>

                      <div>
                        <label className="block font-bold text-slate-600 mb-1">میزان فوریت چالش</label>
                        <select
                          value={qPriority}
                          onChange={(e) => setQPriority(e.target.value as any)}
                          className="w-full border p-2.5 rounded-lg bg-white"
                        >
                          <option value="normal">عادی</option>
                          <option value="urgent">فوری</option>
                          <option value="critical">بحرانی</option>
                        </select>
                      </div>

                      <button
                        type="submit"
                        className="w-full bg-teal-700 hover:bg-teal-600 text-white font-bold py-2.5 rounded-lg shadow-sm cursor-pointer transition-colors"
                      >
                        ثبت و ارسال چالش به شبکه پورتال 🗳️
                      </button>
                    </form>
                  </div>

                </div>

              </div>
            )}

            {/* ۷. نکات تصویری نظارتی و بهداشتی کارگاه */}
            {activeTab === 'tips' && (
              <div className="space-y-6 text-right">
                
                <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                  <h3 className="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                    <Compass className="w-5 h-5 text-teal-600" />
                    <span>نکات ملموس نظارت تصویری و هشدارهای ایمنی بهداشتی</span>
                  </h3>
                  <p className="text-xs text-slate-500 mt-1">تلفیق تجار ملموس گذشته با تصاویر به روز جهت مرور سریع پرسنل و کارآموزان جدید دپارتمان</p>
                </div>

                {visualTips.filter(t => t.departmentId === activeDepartmentId).length === 0 ? (
                  <div className="bg-white rounded-xl border p-12 text-center text-slate-400 text-xs">
                    تصویری برای دپارتمان فعال ثبت نشده است.
                  </div>
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {visualTips.filter(t => t.departmentId === activeDepartmentId).map(tip => (
                      <div key={tip.id} className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col justify-between">
                        {tip.imageUrl && (
                          <img 
                            src={tip.imageUrl} 
                            alt={tip.title} 
                            className="w-full h-48 object-cover pointer-events-none" 
                            referrerPolicy="no-referrer"
                          />
                        )}
                        <div className="p-4 space-y-2">
                          <h4 className="font-extrabold text-xs text-slate-900 flex items-center gap-1">
                            <span className="w-1.5 h-1.5 bg-rose-500 rounded-full animate-pulse" />
                            <span>{tip.title}</span>
                          </h4>
                          <p className="text-[11px] text-slate-600 text-justify leading-relaxed whitespace-pre-line bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                            {tip.description}
                          </p>
                        </div>
                      </div>
                    ))}
                  </div>
                )}

              </div>
            )}

            {/* ۸. تب کدهای آفلاین PHP برای XAMPP و MySQL محلی */}
            {activeTab === 'php_src' && (
              <div className="space-y-6 text-right">
                
                <div className="bg-white rounded-xl shadow-sm border border-indigo-200 p-5">
                  <h3 className="text-sm font-extrabold text-indigo-900 flex items-center gap-2">
                    <Database className="w-5 h-5 text-indigo-700" />
                    <span>پک کدهای آفلاین PHP/MySQL سازگار با سرورهای XAMPP و WAMP محلی نیروگاه</span>
                  </h3>
                  <p className="text-xs text-slate-500 mt-1">از آنجا که این پورتال جهت امنیت بالا باید بر روی سرورهای اینترانت محلی بدون دسترسی اینترنت اجرا شود، تیم مهندسی کدهای خام PHP متبوع را فراهم نموده است.</p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 text-xs">
                  
                  {/* منوی لیست کدهای در دسترس */}
                  <div className="bg-white rounded-xl border border-slate-200 p-4 h-fit space-y-2">
                    <span className="font-bold text-slate-400 block pb-2 border-b">لیست فایلهای PHP لوکال:</span>
                    {Object.entries(PHP_FILES_DICT).map(([key, item]) => (
                      <button
                        key={key}
                        onClick={() => setActivePhpKey(key)}
                        className={`w-full text-right p-2.5 rounded-lg font-bold transition-all flex items-center justify-between cursor-pointer border ${
                          activePhpKey === key 
                            ? 'bg-indigo-50 border-indigo-300 text-indigo-950 font-black' 
                            : 'bg-white hover:bg-slate-50 border-transparent text-slate-600'
                        }`}
                      >
                        <span className="truncate">{item.filename}</span>
                        <ChevronLeft className="w-3.5 h-3.5" />
                      </button>
                    ))}
                  </div>

                  {/* پنل نمایش جزئیات و کدهای کپی‌شونده */}
                  <div className="lg:col-span-3 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm flex flex-col">
                    <div className="bg-slate-900 text-slate-200 p-4 flex justify-between items-center">
                      <div>
                        <span className="text-indigo-400 font-mono font-bold text-xs">{PHP_FILES_DICT[activePhpKey].filename}</span>
                        <p className="text-[10px] text-slate-400 mt-1">{PHP_FILES_DICT[activePhpKey].description}</p>
                      </div>
                      
                      <button
                        onClick={() => copyPhpCode(activePhpKey, PHP_FILES_DICT[activePhpKey].code)}
                        className="bg-indigo-700 hover:bg-indigo-600 text-white font-bold py-1.5 px-3.5 rounded-lg text-[11px] transition-colors flex items-center gap-1 cursor-pointer shadow-md"
                      >
                        {copiedFileKey === activePhpKey ? (
                          <>
                            <Check className="w-3.5 h-3.5" />
                            <span>کپی شد! ✓</span>
                          </>
                        ) : (
                          <>
                            <Copy className="w-3.5 h-3.5" />
                            <span>کپی کدهای این فایل</span>
                          </>
                        )}
                      </button>
                    </div>

                    <div className="p-4 bg-slate-950 font-mono text-left text-xs overflow-auto max-h-[500px] leading-relaxed select-text text-emerald-400 whitespace-pre">
                      {PHP_FILES_DICT[activePhpKey].code}
                    </div>
                  </div>

                </div>

              </div>
            )}

            {/* ۹. تب مدیریت کاربران (ادمین ارشد) */}
            {activeTab === 'users_mgmt' && currentUser.role === 'admin' && (
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 text-right">
                
                <div className="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden text-xs">
                  <div className="bg-slate-100 p-4 border-b font-extrabold flex justify-between items-center text-slate-800">
                    <span>👥 لیست ناظران و کارشناسان عضو اینترانت</span>
                    <span className="text-[10px] bg-slate-200 px-2 py-0.5 rounded-full font-black text-slate-600">{users.length} نفر</span>
                  </div>

                  <div className="divide-y text-xs">
                    {users.map(u => (
                      <div key={u.id} className="p-4 flex items-center justify-between gap-4 bg-white hover:bg-slate-50 transition-all">
                        <div className="space-y-1">
                          <p className="font-extrabold text-slate-900 text-sm">{u.name}</p>
                          <p className="font-mono text-[10px] text-slate-400">نام کاربری دفتری: <strong>{u.username}</strong> | کلمه عبور: <code>{u.password || '******'}</code></p>
                        </div>
                        
                        <div className="flex items-center gap-2">
                          <span className={`text-[10px] font-black px-2 py-0.5 rounded-full ${
                            u.role === 'admin' ? 'bg-teal-100 text-teal-800' :
                            u.role === 'expert' ? 'bg-amber-100 text-amber-800' :
                            'bg-slate-200 text-slate-700'
                          }`}>
                            {u.role === 'admin' && 'مدیر عالی سیستم'}
                            {u.role === 'expert' && 'ممیز ارشد علمی'}
                            {u.role === 'contributor' && 'ناظر صحرایی'}
                          </span>
                          
                          <button 
                            onClick={() => handleDeleteUser(u.id)}
                            className="text-red-600 hover:text-red-800 p-1.5 hover:bg-rose-50 rounded transition-all cursor-pointer text-[11px] font-bold"
                            title="خلع عضویت"
                          >
                            حذف 🗑️
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                {/* فرم تعریف عضو جدید کارگاه */}
                <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-5 text-xs text-right space-y-4">
                  <h3 className="font-bold text-slate-800 border-b pb-2 flex items-center gap-1.5">
                    <UserCheck className="w-4 h-4 text-teal-600" />
                    <span>➕ صدور دسترسی برای پرسنل جدید دپارتمان</span>
                  </h3>

                  {userSuccessMsg && (
                    <div className="p-3.5 bg-emerald-50 text-emerald-800 font-bold rounded-lg border border-emerald-200">
                      {userSuccessMsg}
                    </div>
                  )}

                  <form onSubmit={handleCreateUser} className="space-y-4">
                    <div>
                      <label className="block font-bold text-slate-600 mb-1">نام و نام خانوادگی پرسنلی *</label>
                      <input 
                        type="text"
                        required
                        value={userFormName}
                        onChange={(e) => setUserFormName(e.target.value)}
                        placeholder="مثال: مهندس رنجبر"
                        className="w-full border p-2.5 rounded-lg bg-slate-50 focus:bg-white focus:outline-teal-500"
                      />
                    </div>

                    <div>
                      <label className="block font-bold text-slate-600 mb-1">نام کاربری دفتری انحصاری *</label>
                      <input 
                        type="text"
                        required
                        value={userFormUsername}
                        onChange={(e) => setUserFormUsername(e.target.value)}
                        placeholder="مثال: ranzhbar"
                        className="w-full border p-2.5 rounded-lg bg-slate-50 focus:bg-white focus:outline-teal-500 text-left font-mono"
                      />
                    </div>

                    <div>
                      <label className="block font-bold text-slate-600 mb-1">رمز عبور امنیتی اینترانت *</label>
                      <input 
                        type="text"
                        required
                        value={userFormPassword}
                        onChange={(e) => setUserFormPassword(e.target.value)}
                        placeholder="رمز عبور محلی"
                        className="w-full border p-2.5 rounded-lg bg-slate-50 focus:bg-white focus:outline-teal-500 text-center font-mono"
                      />
                    </div>

                    <div>
                      <label className="block font-bold text-slate-600 mb-1">انتخاب نقش و حدود دسترسی</label>
                      <select
                        value={userFormRole}
                        onChange={(e) => setUserFormRole(e.target.value as any)}
                        className="w-full border p-2.5 rounded-lg bg-white"
                      >
                        <option value="contributor">ناظر میدانی دپارتمان (تولید اسناد در انتظار ممیزی)</option>
                        <option value="expert">ممیز ارشد دپارتمان (حق تایید/رد و فیدبک‌دهی علمی)</option>
                        <option value="admin">مدیر کل سامانه (دسترسی نامحدود و مدیریت همکاران)</option>
                      </select>
                    </div>

                    <button
                      type="submit"
                      className="w-full bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 rounded-lg transition-colors cursor-pointer"
                    >
                      ذخیره عضو در شبکه لوکال نیروگاه 🗳️
                    </button>
                  </form>
                </div>

              </div>
            )}

          </main>
        </>
      )}

      {/* پاورقی استاندارد و مقتدرانه هماهنگ با درخواست‌های کاربر گرامی */}
      <footer className="bg-slate-900 text-slate-400 py-6 border-t border-slate-800 text-center text-xs mt-auto">
        <div className="max-w-7xl mx-auto px-4 space-y-2">
          <p className="font-extrabold text-slate-200 flex items-center justify-center gap-1">
            <span>💻</span>
            <span>طراح و برنامه نویس: <strong>مهدی اسماعیلی</strong></span>
            <span className="text-slate-500 mx-2">|</span>
            <span>نسخه ویژه آزمون رفاهی تولید نیرو:</span>
            <code className="bg-slate-800 text-teal-400 px-2.5 py-0.5 rounded-md font-mono text-[10px]">v2.5.0-Welfare-Release</code>
          </p>
          <p className="text-[10px] text-slate-500">تمامی حقوق علمی و معنوی پرونده‌های مستند دانش فنی محفوظ برای سازمان برق نیروگاه‌های جنوب کشور می‌باشد.</p>
        </div>
      </footer>

    </div>
  );
}
