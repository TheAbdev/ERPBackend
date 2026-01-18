# دليل تشغيل Queue و Schedule Workers

## الفرق بين `queue:work` و `schedule:work`

### 1. `php artisan schedule:work` (Scheduler)
**الوظيفة:** يشغل Scheduled Tasks (Cron Jobs) المحددة في `routes/console.php`

**المهام التي يشغلها:**
- ✅ Recurring Invoices (يومياً)
- ✅ Reorder Rules (يومياً)
- ✅ Activity Reminders (كل 5 دقائق)
- ✅ Scheduled Reports (كل دقيقة)
- ✅ System Health Checks (كل 5 دقائق)
- ✅ Webhook Retries (كل 10 دقائق)

**متى تحتاجه:** دائماً في Production

---

### 2. `php artisan queue:work` (Queue Worker)
**الوظيفة:** يعالج Queue Jobs في الخلفية (Background Jobs)

**الـ Jobs التي يعالجها:**
- ✅ `ExecuteWorkflowJob` - تشغيل Workflows تلقائياً
- ✅ `ExportReportJob` - تصدير التقارير (PDF/Excel)
- ✅ `DeliverWebhookJob` - إرسال Webhooks
- ✅ `SendDealNotificationJob` - إشعارات الصفقات
- ✅ `SendActivityReminderJob` - تذكيرات الأنشطة
- ✅ `ImportDataJob` - استيراد البيانات

**متى تحتاجه:** دائماً في Production (إذا كان لديك أي من هذه الميزات مفعلة)

---

## كيفية التشغيل

### في Development (Local)
```bash
# Terminal 1: تشغيل Scheduler
php artisan schedule:work

# Terminal 2: تشغيل Queue Worker
php artisan queue:work
```

### في Production (Server)

#### الخيار 1: استخدام Supervisor (موصى به)
إنشاء ملف `/etc/supervisor/conf.d/erp-backend.conf`:

```ini
[program:erp-scheduler]
process_name=%(program_name)s
command=php /path/to/ERPBackend/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/logs/scheduler.log

[program:erp-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/ERPBackend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/logs/queue-worker.log
```

ثم تشغيل:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start erp-scheduler
sudo supervisorctl start erp-queue-worker:*
```

#### الخيار 2: استخدام Systemd
إنشاء ملف `/etc/systemd/system/erp-scheduler.service`:
```ini
[Unit]
Description=ERP Backend Scheduler
After=network.target

[Service]
User=www-data
WorkingDirectory=/path/to/ERPBackend
ExecStart=/usr/bin/php artisan schedule:work
Restart=always

[Install]
WantedBy=multi-user.target
```

إنشاء ملف `/etc/systemd/system/erp-queue-worker.service`:
```ini
[Unit]
Description=ERP Backend Queue Worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/path/to/ERPBackend
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3
Restart=always

[Install]
WantedBy=multi-user.target
```

ثم تشغيل:
```bash
sudo systemctl enable erp-scheduler
sudo systemctl enable erp-queue-worker
sudo systemctl start erp-scheduler
sudo systemctl start erp-queue-worker
```

---

## ملاحظات مهمة

### 1. في Development
- يمكنك استخدام `schedule:work` فقط إذا لم تكن هناك Jobs في Queue
- لكن يُنصح بتشغيل `queue:work` أيضاً لتجنب تراكم Jobs

### 2. في Production
- **يجب** تشغيل كليهما دائماً
- استخدم Supervisor أو Systemd لضمان استمرار التشغيل
- راجع الـ Logs بانتظام

### 3. مراقبة الـ Queue
```bash
# عرض عدد Jobs في Queue
php artisan queue:monitor

# عرض Failed Jobs
php artisan queue:failed

# إعادة تشغيل Failed Job
php artisan queue:retry {job-id}

# حذف Failed Job
php artisan queue:forget {job-id}
```

### 4. إعدادات Queue
تأكد من أن `QUEUE_CONNECTION` في `.env` مضبوط على:
```env
QUEUE_CONNECTION=redis
# أو
QUEUE_CONNECTION=database
```

---

## الخلاصة

| البيئة | schedule:work | queue:work | السبب |
|--------|---------------|------------|-------|
| **Development** | ✅ نعم | ✅ نعم (موصى به) | لاختبار جميع الميزات |
| **Production** | ✅ **ضروري** | ✅ **ضروري** | لضمان عمل النظام بشكل صحيح |

**ملاحظة:** بدون `queue:work`، ستتراكم Jobs في Queue ولن يتم معالجتها، مما يؤدي إلى:
- عدم إرسال الإشعارات
- عدم تشغيل Workflows
- عدم تصدير التقارير
- عدم إرسال Webhooks

