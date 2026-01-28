# دليل شامل: الإشعارات (Notifications) والـ Observers

## 📋 نظرة عامة

هذا الدليل يشرح:
1. كيف يتم تخزين الإشعارات في قاعدة البيانات
2. ما هي الأحداث (Events) التي تسبب إنشاء إشعارات
3. ما هي وظائف الـ Observers في الكود

---

## 🔔 كيف يتم تخزين الإشعارات (Notifications)

### الجداول المستخدمة:

#### 1. جدول `notifications` (Laravel Notifications)
- **الموقع:** `database/migrations/2026_01_05_084803_create_notifications_table.php`
- **الاستخدام:** للإشعارات القياسية من Laravel
- **الحقول:**
  - `id` (UUID)
  - `tenant_id` - معرف المستأجر
  - `notifiable_type` - نوع الكائن (مثل: User)
  - `notifiable_id` - معرف الكائن
  - `type` - نوع الإشعار (مثل: ActivityDueNotification)
  - `data` (JSON) - بيانات الإشعار
  - `read_at` - تاريخ القراءة

#### 2. جدول `erp_notifications` (ERP Notifications)
- **الموقع:** `database/migrations/2026_01_05_163146_create_notifications_table.php`
- **الاستخدام:** للإشعارات المخصصة في ERP
- **الحقول:**
  - `id`
  - `tenant_id` - معرف المستأجر
  - `user_id` - معرف المستخدم
  - `entity_type` - نوع الكائن المرتبط (مثل: Deal, Activity)
  - `entity_id` - معرف الكائن المرتبط
  - `type` - نوع الإشعار (info, warning, alert)
  - `title` - عنوان الإشعار
  - `message` - نص الإشعار
  - `metadata` (JSON) - بيانات إضافية
  - `read_at` - تاريخ القراءة

---

## 🎯 الأحداث (Events) التي تسبب إنشاء إشعارات

### 1. **`ActivityDue` Event**
- **الملف:** `app/Events/ActivityDue.php`
- **متى يتم إطلاقه:**
  - من `CheckActivityReminders` command (كل 5 دقائق)
  - عند اقتراب موعد نشاط
- **الـ Listener:** `SendActivityReminderListener`
- **الـ Job:** `SendActivityReminderJob`
- **الإشعار:** `ActivityDueNotification`
- **كيف يتم التخزين:**
  ```php
  // في SendActivityReminderJob
  $user->notify(new ActivityDueNotification($activity));
  
  // في TenantDatabaseChannel
  Notification::create([
      'tenant_id' => $tenantId,
      'notifiable_type' => 'App\Models\User',
      'notifiable_id' => $user->id,
      'type' => 'App\Notifications\ActivityDueNotification',
      'data' => [
          'type' => 'activity_due',
          'activity_id' => $activity->id,
          'activity_subject' => $activity->subject,
          'message' => "Activity '{$activity->subject}' is due soon."
      ]
  ]);
  ```

---

### 2. **`DealStatusChanged` Event**
- **الملف:** `app/Events/DealStatusChanged.php`
- **متى يتم إطلاقه:**
  - عند تغيير حالة Deal (من `Deal` model في `boot()` method)
  - عند تغيير مرحلة Deal
- **الـ Listener:** `SendDealNotificationListener`
- **الـ Job:** `SendDealNotificationJob`
- **الإشعار:** `DealStatusNotification`
- **كيف يتم التخزين:**
  ```php
  // في Deal model (boot method)
  event(new DealStatusChanged($deal, $deal->status, $original['status']));
  
  // في SendDealNotificationJob
  $user->notify(new DealStatusNotification($deal, $action));
  
  // في TenantDatabaseChannel
  Notification::create([
      'tenant_id' => $tenantId,
      'notifiable_type' => 'App\Models\User',
      'notifiable_id' => $user->id,
      'type' => 'App\Notifications\DealStatusNotification',
      'data' => [
          'type' => 'deal_update',
          'deal_id' => $deal->id,
          'deal_title' => $deal->title,
          'action' => $action,
          'message' => "Deal '{$deal->title}' has been updated."
      ]
  ]);
  ```

---

### 3. **`NoteMentioned` Event**
- **الملف:** `app/Events/NoteMentioned.php`
- **متى يتم إطلاقه:**
  - عند ذكر مستخدم في Note (من `NoteController`)
- **الـ Listener:** `SendMentionNotificationListener`
- **الإشعار:** `MentionNotification`
- **كيف يتم التخزين:**
  ```php
  // في NoteController
  event(new NoteMentioned($note, $user, $mentionedBy));
  
  // في SendMentionNotificationListener
  $event->mentionedUser->notify(
      new MentionNotification($event->note, $event->mentionedBy)
  );
  
  // في TenantDatabaseChannel
  Notification::create([
      'tenant_id' => $tenantId,
      'notifiable_type' => 'App\Models\User',
      'notifiable_id' => $mentionedUser->id,
      'type' => 'App\Notifications\MentionNotification',
      'data' => [
          'type' => 'mention',
          'note_id' => $note->id,
          'mentioned_by' => $mentionedBy->name,
          'message' => "{$mentionedBy->name} mentioned you in a note."
      ]
  ]);
  ```

---

### 4. **إشعارات من `NotificationService`**
- **الملف:** `app/Modules/ERP/Services/NotificationService.php`
- **الاستخدام:** لإرسال إشعارات مخصصة مباشرة
- **كيف يتم التخزين:**
  ```php
  // في NotificationService
  Notification::create([
      'tenant_id' => $tenantId,
      'user_id' => $userId,
      'entity_type' => 'App\Modules\ERP\Models\Invoice',
      'entity_id' => $invoiceId,
      'type' => 'info', // أو 'warning', 'alert'
      'title' => 'New Invoice Created',
      'message' => 'A new invoice has been created.',
      'metadata' => ['invoice_number' => 'INV-001']
  ]);
  ```

---

## 👁️ وظائف الـ Observers في الكود

### ما هي الـ Observers؟
الـ Observers هي فئات تراقب التغييرات على Models وتنفذ إجراءات تلقائية عند حدوث أحداث معينة (created, updated, deleted).

---

### 1. **`LeadObserver`**
- **الملف:** `app/Observers/LeadObserver.php`
- **الوظائف:**
  - **`created()`** - عند إنشاء Lead جديد:
    - تشغيل Workflow تلقائياً (`lead.created` event)
    - تعيين Lead تلقائياً إذا لم يكن معيناً (`LeadAssignmentService`)
- **متى يتم استخدامه:**
  - عند إنشاء Lead جديد من API أو واجهة المستخدم
- **التسجيل:** في `AppServiceProvider`:
  ```php
  \App\Modules\CRM\Models\Lead::observe(\App\Observers\LeadObserver::class);
  ```

---

### 2. **`DealObserver`**
- **الملف:** `app/Modules/CRM/Observers/DealObserver.php`
- **الوظائف:**
  - **`updated()`** - عند تحديث Deal:
    - تسجيل تغييرات المرحلة (Stage) في Audit Log
    - تسجيل تغييرات الحالة (Status) في Audit Log
- **متى يتم استخدامه:**
  - عند تحديث Deal (تغيير المرحلة أو الحالة)
- **التسجيل:** في `AppServiceProvider`:
  ```php
  \App\Modules\CRM\Models\Deal::observe(\App\Modules\CRM\Observers\DealObserver::class);
  ```

---

### 3. **`CacheInvalidationObserver`**
- **الملف:** `app/Observers/CacheInvalidationObserver.php`
- **الوظائف:**
  - **`created()`** - عند إنشاء سجل جديد
  - **`updated()`** - عند تحديث سجل
  - **`deleted()`** - عند حذف سجل
  - **`restored()`** - عند استعادة سجل محذوف
  - **الوظيفة الرئيسية:** حذف الـ Cache تلقائياً عند تغيير البيانات
- **النماذج المراقبة:**
  - **CRM:** Lead, Contact, Account, Deal, Activity, Note, LeadScore, LeadAssignmentRule, NoteAttachment
  - **ERP:** Product, ProductCategory, StockItem, InventoryTransaction, SalesOrder, PurchaseOrder, Account, JournalEntry, RecurringInvoice, CreditNote, Expense, ExpenseCategory, InventorySerial, ReorderRule
  - **Core:** Tag, Team, CustomField
- **التسجيل:** في `AppServiceProvider`:
  ```php
  \App\Modules\CRM\Models\Lead::observe(\App\Observers\CacheInvalidationObserver::class);
  // ... وغيرها من النماذج
  ```

---

### 4. **ERP Observers** (في `app/Modules/ERP/Observers/`)
- **`AccountObserver`** - مراقبة حسابات المحاسبة
- **`JournalEntryObserver`** - مراقبة قيود اليومية
- **`InvoiceObserver`** - مراقبة الفواتير
- **`PurchaseInvoiceObserver`** - مراقبة فواتير الشراء
- **`PaymentObserver`** - مراقبة المدفوعات
- **`FixedAssetObserver`** - مراقبة الأصول الثابتة
- **`AssetDepreciationObserver`** - مراقبة استهلاك الأصول
- **`RecurringInvoiceObserver`** - مراقبة الفواتير المتكررة
- **`CreditNoteObserver`** - مراقبة إشعارات الائتمان
- **`ExpenseObserver`** - مراقبة المصروفات

---

## 🔄 تدفق العمل (Workflow)

### مثال: إشعار عند تغيير حالة Deal

```
1. المستخدم يحدث Deal (يغير الحالة)
   ↓
2. DealObserver::updated() يتم استدعاؤه تلقائياً
   ↓
3. DealObserver يسجل التغيير في Audit Log
   ↓
4. Deal model (في boot method) يطلق Event:
   event(new DealStatusChanged($deal, $deal->status, $oldStatus))
   ↓
5. SendDealNotificationListener يستمع للحدث
   ↓
6. Listener يضيف SendDealNotificationJob إلى الطابور
   ↓
7. queue:work يعالج Job
   ↓
8. SendDealNotificationJob يستدعي:
   $user->notify(new DealStatusNotification($deal, $action))
   ↓
9. TenantDatabaseChannel يحفظ الإشعار في جدول notifications
   ↓
10. الإشعار يظهر للمستخدم في واجهة التطبيق
```

---

## 📊 ملخص الأحداث والإشعارات

| الحدث | متى يتم إطلاقه | Listener | Job | Notification | الجدول |
|-------|---------------|----------|-----|--------------|--------|
| **ActivityDue** | من Scheduled Task (كل 5 دقائق) | `SendActivityReminderListener` | `SendActivityReminderJob` | `ActivityDueNotification` | `notifications` |
| **DealStatusChanged** | عند تغيير حالة/مرحلة Deal | `SendDealNotificationListener` | `SendDealNotificationJob` | `DealStatusNotification` | `notifications` |
| **NoteMentioned** | عند ذكر مستخدم في Note | `SendMentionNotificationListener` | ❌ مباشر | `MentionNotification` | `notifications` |
| **NotificationService** | عند استدعاء Service مباشرة | ❌ | ❌ | ❌ | `erp_notifications` |

---

## 📝 ملاحظات مهمة

1. **جدولان للإشعارات:**
   - `notifications` - للإشعارات القياسية من Laravel (عبر `TenantDatabaseChannel`)
   - `erp_notifications` - للإشعارات المخصصة (عبر `NotificationService`)

2. **الـ Observers تعمل تلقائياً:**
   - لا تحتاج لاستدعاء يدوي
   - يتم تسجيلها في `AppServiceProvider::boot()`

3. **الأحداث (Events) تحتاج Listener:**
   - يتم تسجيل Listeners في `AppServiceProvider::boot()`
   - بعض Listeners تضيف Jobs إلى الطابور

4. **الإشعارات يمكن أن تكون في الطابور:**
   - جميع Notification classes تطبق `ShouldQueue`
   - يتم معالجتها عبر `queue:work`

---

## 🔍 كيفية إضافة إشعار جديد

### الخطوات:

1. **إنشاء Event:**
   ```php
   // app/Events/MyEvent.php
   class MyEvent {
       public function __construct(public $entity) {}
   }
   ```

2. **إنشاء Notification:**
   ```php
   // app/Notifications/MyNotification.php
   class MyNotification extends Notification implements ShouldQueue {
       public function via($notifiable) {
           return [TenantDatabaseChannel::class];
       }
       
       public function toArray($notifiable) {
           return ['message' => '...'];
       }
   }
   ```

3. **إنشاء Listener:**
   ```php
   // app/Listeners/SendMyNotificationListener.php
   class SendMyNotificationListener implements ShouldQueue {
       public function handle(MyEvent $event) {
           $event->user->notify(new MyNotification($event->entity));
       }
   }
   ```

4. **تسجيل Listener في AppServiceProvider:**
   ```php
   Event::listen(MyEvent::class, SendMyNotificationListener::class);
   ```

5. **إطلاق Event عند الحاجة:**
   ```php
   event(new MyEvent($entity));
   ```

---

**للمزيد من التفاصيل:** راجع ملفات الكود في:
- `app/Events/`
- `app/Notifications/`
- `app/Listeners/`
- `app/Observers/`
- `app/Providers/AppServiceProvider.php`



















