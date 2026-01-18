# دليل الخدمات المطلوبة للتشغيل

## ✅ الخدمات الأساسية المطلوبة

### 1. **Web Server** (Apache/Nginx)
**الحالة:** ✅ يعمل تلقائياً مع XAMPP

**التحقق:**
```bash
# في المتصفح
http://localhost
```

---

### 2. **Database Server** (MySQL/MariaDB)
**الحالة:** ✅ يعمل تلقائياً مع XAMPP

**التحقق:**
```bash
# في Terminal
php artisan db:show
# أو
mysql -u root -p
```

**الإعدادات في `.env`:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erp
DB_USERNAME=root
DB_PASSWORD=
```

---

### 3. **PHP CLI** (Command Line Interface)
**الحالة:** ✅ متوفر مع XAMPP

**التحقق:**
```bash
php -v
# يجب أن يكون PHP 8.2 أو أعلى
```

---

## ⚠️ الخدمات الاختيارية (لكن موصى بها)

### 4. **Redis Server** (اختياري لكن موصى به)
**متى تحتاجه:**
- إذا كنت تستخدم `QUEUE_CONNECTION=redis`
- إذا كنت تستخدم `CACHE_STORE=redis`
- لتحسين الأداء في Production

**التثبيت (Windows):**
```bash
# تحميل Redis for Windows من:
# https://github.com/microsoftarchive/redis/releases
# أو استخدام WSL

# أو استخدام Docker
docker run -d -p 6379:6379 redis:latest
```

**التحقق:**
```bash
redis-cli ping
# يجب أن يرد: PONG
```

**الإعدادات في `.env`:**
```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**بدون Redis:**
- يمكنك استخدام `database` للـ Queue و Cache
- سيعمل النظام لكن بسرعة أقل

---

## 📋 ما تحتاج تشغيله

### في Development (Local - XAMPP)

#### ✅ **ضروري:**
1. **XAMPP Control Panel** - تشغيل Apache و MySQL
2. **Terminal 1:** `php artisan schedule:work`
3. **Terminal 2:** `php artisan queue:work`

#### ⚠️ **اختياري (لكن موصى به):**
4. **Redis Server** (إذا كنت تستخدم Redis)

---

### في Production (Server)

#### ✅ **ضروري:**
1. **Web Server** (Apache/Nginx) - يعمل تلقائياً
2. **Database Server** (MySQL/MariaDB) - يعمل تلقائياً
3. **Supervisor/Systemd** - لتشغيل:
   - `php artisan schedule:work`
   - `php artisan queue:work`
4. **Cron Job** - لتشغيل Scheduler (بديل لـ schedule:work)

#### ⚠️ **موصى به بشدة:**
5. **Redis Server** - لتحسين الأداء

---

## 🔍 كيفية التحقق من أن كل شيء يعمل

### 1. التحقق من Database
```bash
php artisan db:show
# أو
php artisan migrate:status
```

### 2. التحقق من Queue
```bash
php artisan queue:monitor
# أو
php artisan queue:work --once
```

### 3. التحقق من Cache
```bash
php artisan cache:clear
php artisan config:cache
```

### 4. التحقق من Redis (إذا كان مستخدماً)
```bash
redis-cli ping
# يجب أن يرد: PONG
```

### 5. التحقق من Scheduled Tasks
```bash
php artisan schedule:list
```

---

## 📝 ملخص سريع

| الخدمة | Development | Production | ملاحظات |
|--------|-------------|------------|---------|
| **Apache/Nginx** | ✅ XAMPP | ✅ ضروري | يعمل تلقائياً |
| **MySQL** | ✅ XAMPP | ✅ ضروري | يعمل تلقائياً |
| **PHP CLI** | ✅ XAMPP | ✅ ضروري | متوفر |
| **schedule:work** | ✅ يدوياً | ✅ Supervisor | ضروري |
| **queue:work** | ✅ يدوياً | ✅ Supervisor | ضروري |
| **Redis** | ⚠️ اختياري | ⚠️ موصى به | لتحسين الأداء |

---

## 🚀 سيناريوهات التشغيل

### السيناريو 1: Development بدون Redis
```bash
# Terminal 1
php artisan schedule:work

# Terminal 2
php artisan queue:work
```
**ملاحظة:** سيعمل النظام باستخدام Database للـ Queue و Cache

### السيناريو 2: Development مع Redis
```bash
# 1. تشغيل Redis
redis-server

# Terminal 1
php artisan schedule:work

# Terminal 2
php artisan queue:work redis
```

### السيناريو 3: Production
```bash
# استخدام Supervisor أو Systemd
# راجع QUEUE_AND_SCHEDULE_GUIDE.md
```

---

## ❓ الأسئلة الشائعة

### س: هل أحتاج Redis؟
**ج:** لا، لكن موصى به. يمكنك استخدام `database` للـ Queue و Cache.

### س: هل أحتاج تشغيل شيء آخر غير schedule:work و queue:work؟
**ج:** لا، هما كافيان. فقط تأكد من أن:
- Apache/MySQL يعملان (XAMPP)
- Database migrations تم تشغيلها
- `.env` مضبوط بشكل صحيح

### س: ماذا لو لم أشغل queue:work؟
**ج:** ستتراكم Jobs في Queue ولن تُعالج، مما يؤدي إلى:
- عدم إرسال الإشعارات
- عدم تشغيل Workflows
- عدم تصدير التقارير

### س: ماذا لو لم أشغل schedule:work؟
**ج:** لن تعمل Scheduled Tasks مثل:
- Recurring Invoices
- Reorder Rules
- Activity Reminders

---

## ✅ الخلاصة

**ما تحتاج تشغيله:**
1. ✅ XAMPP (Apache + MySQL)
2. ✅ `php artisan schedule:work`
3. ✅ `php artisan queue:work`
4. ⚠️ Redis (اختياري لكن موصى به)

**لا تحتاج:**
- ❌ أي خدمات إضافية أخرى
- ❌ أي background processes أخرى

**ملاحظة:** في Production، استخدم Supervisor أو Systemd لإدارة `schedule:work` و `queue:work` بدلاً من تشغيلهما يدوياً.

