# دليل استخدام Note Mention من الفرونت إند

## 📋 نظرة عامة

لإنشاء Note مع Mention (ذكر مستخدم) من الفرونت إند، يجب كتابة Mention في حقل `body` بصيغة معينة.

---

## 🔗 API Endpoint

```
POST /api/crm/notes
```

---

## 📝 الحقول المطلوبة (Request Body)

### الحقول الأساسية:

```json
{
  "noteable_type": "lead|contact|account|deal",
  "noteable_id": 123,
  "body": "النص هنا @123 أو @{123}"
}
```

### شرح الحقول:

| الحقل | النوع | الوصف | مثال |
|-------|-------|-------|------|
| **noteable_type** | string | نوع الكائن المرتبط بالـ Note | `"lead"`, `"contact"`, `"account"`, `"deal"` |
| **noteable_id** | integer | معرف الكائن المرتبط | `123` |
| **body** | string | نص الـ Note (يحتوي على Mention) | `"مرحباً @123 هذا مهم"` |

---

## 💡 كيفية كتابة Mention في حقل `body`

### الصيغة المطلوبة:

يمكن كتابة Mention بطريقتين:

1. **`@user_id`** - بدون أقواس
   ```
   @123
   ```

2. **`@{user_id}`** - مع أقواس
   ```
   @{123}
   ```

### أمثلة:

#### مثال 1: Mention واحد
```json
{
  "noteable_type": "deal",
  "noteable_id": 5,
  "body": "مرحباً @123، يرجى مراجعة هذه الصفقة"
}
```

#### مثال 2: عدة Mentions
```json
{
  "noteable_type": "lead",
  "noteable_id": 10,
  "body": "مرحباً @123 و @456، هذا Lead يحتاج متابعة"
}
```

#### مثال 3: Mention مع أقواس
```json
{
  "noteable_type": "contact",
  "noteable_id": 7,
  "body": "@{123} يرجى الاتصال بهذا العميل"
}
```

#### مثال 4: Mention في منتصف النص
```json
{
  "noteable_type": "account",
  "noteable_id": 3,
  "body": "هذا الحساب مهم. @123 يرجى متابعته. شكراً"
}
```

---

## 🔄 كيف يعمل Mention؟

### الخطوات:

1. **إرسال Request:**
   ```javascript
   POST /api/crm/notes
   {
     "noteable_type": "deal",
     "noteable_id": 5,
     "body": "مرحباً @123"
   }
   ```

2. **الـ Backend يستقبل Request:**
   - `NoteController::store()` ينشئ Note
   - يستدعي `parseMentions()` لتحليل النص

3. **تحليل Mentions:**
   ```php
   // في parseMentions()
   preg_match_all('/@\{?(\d+)\}?/', $body, $matches);
   // يبحث عن: @123 أو @{123}
   ```

4. **حفظ Mentions:**
   - يتم ربط المستخدمين المذكورين بالـ Note في جدول `note_mentions`
   - يتم إطلاق Event: `NoteMentioned`

5. **إرسال إشعار:**
   - `SendMentionNotificationListener` يستمع للحدث
   - يرسل `MentionNotification` للمستخدم المذكور
   - يتم حفظ الإشعار في جدول `notifications`

---

## 💻 مثال كود من الفرونت إند (React/TypeScript)

### مثال 1: إنشاء Note مع Mention

```typescript
// في مكون React
const createNoteWithMention = async () => {
  const noteData = {
    noteable_type: 'deal',
    noteable_id: 5,
    body: 'مرحباً @123، يرجى مراجعة هذه الصفقة'
  };

  try {
    const response = await fetch('/api/crm/notes', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(noteData)
    });

    const result = await response.json();
    console.log('Note created:', result.data);
  } catch (error) {
    console.error('Error:', error);
  }
};
```

### مثال 2: مكون إدخال مع Mention

```typescript
import { useState } from 'react';

const NoteForm = ({ noteableType, noteableId }) => {
  const [body, setBody] = useState('');
  const [selectedUsers, setSelectedUsers] = useState([]);

  // عند اختيار مستخدم من قائمة
  const handleUserSelect = (userId: number, userName: string) => {
    // إضافة Mention للنص
    const mention = `@${userId}`;
    setBody(prev => prev + ` ${mention} `);
    
    // حفظ المستخدم المختار
    setSelectedUsers(prev => [...prev, { id: userId, name: userName }]);
  };

  const handleSubmit = async () => {
    const noteData = {
      noteable_type: noteableType,
      noteable_id: noteableId,
      body: body
    };

    // إرسال Request
    await createNote(noteData);
  };

  return (
    <div>
      <textarea
        value={body}
        onChange={(e) => setBody(e.target.value)}
        placeholder="اكتب Note... استخدم @ للذكر"
      />
      
      {/* قائمة المستخدمين للاختيار */}
      <UserSelector onSelect={handleUserSelect} />
      
      <button onClick={handleSubmit}>إرسال</button>
    </div>
  );
};
```

### مثال 3: Auto-complete للـ Mentions

```typescript
const MentionInput = ({ noteableType, noteableId }) => {
  const [body, setBody] = useState('');
  const [showUserList, setShowUserList] = useState(false);
  const [users, setUsers] = useState([]);

  // عند كتابة @
  const handleInputChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    const value = e.target.value;
    setBody(value);

    // إذا كتب @، عرض قائمة المستخدمين
    if (value.endsWith('@')) {
      setShowUserList(true);
      fetchUsers(); // جلب قائمة المستخدمين
    } else {
      setShowUserList(false);
    }
  };

  // عند اختيار مستخدم
  const handleUserClick = (userId: number) => {
    // استبدال @ بـ @userId
    const newBody = body.replace(/@$/, `@${userId}`);
    setBody(newBody);
    setShowUserList(false);
  };

  return (
    <div>
      <textarea
        value={body}
        onChange={handleInputChange}
        placeholder="اكتب @ لذكر مستخدم"
      />
      
      {showUserList && (
        <div className="user-list">
          {users.map(user => (
            <div
              key={user.id}
              onClick={() => handleUserClick(user.id)}
            >
              {user.name}
            </div>
          ))}
        </div>
      )}
    </div>
  );
};
```

---

## 📊 Response من API

### عند نجاح الإنشاء:

```json
{
  "data": {
    "id": 1,
    "tenant_id": 1,
    "noteable_type": "deal",
    "noteable_id": 5,
    "body": "مرحباً @123، يرجى مراجعة هذه الصفقة",
    "created_by": 1,
    "mentions": [
      {
        "id": 123,
        "name": "أحمد محمد",
        "email": "ahmed@example.com"
      }
    ],
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  },
  "message": "Note created successfully."
}
```

---

## ⚠️ ملاحظات مهمة

### 1. **صيغة Mention:**
   - يجب أن يكون `@` متبوعاً برقم (ID المستخدم)
   - يمكن استخدام `@123` أو `@{123}`
   - لا يمكن استخدام اسم المستخدم مباشرة: `@أحمد` ❌

### 2. **التحقق من المستخدم:**
   - يجب أن يكون المستخدم المذكور موجوداً
   - يجب أن يكون في نفس Tenant
   - إذا لم يكن موجوداً، لن يتم إنشاء Mention

### 3. **Mentions متعددة:**
   - يمكن ذكر عدة مستخدمين في Note واحد
   - كل مستخدم سيستلم إشعار منفصل

### 4. **Reply مع Mention:**
   - يمكن استخدام Mention في الردود أيضاً
   - Endpoint: `POST /api/crm/notes/{note}/replies`
   - نفس الصيغة: `@123` أو `@{123}`

---

## 🔍 مثال كامل: إنشاء Note مع Mention لـ Deal

```typescript
// 1. جلب قائمة المستخدمين
const users = await fetch('/api/users').then(r => r.json());

// 2. عرض واجهة اختيار المستخدم
const selectedUserId = 123; // من واجهة المستخدم

// 3. إنشاء Note
const noteData = {
  noteable_type: 'deal',
  noteable_id: 5,
  body: `مرحباً @${selectedUserId}، يرجى مراجعة هذه الصفقة. المبلغ: 5000 ريال`
};

// 4. إرسال Request
const response = await fetch('/api/crm/notes', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify(noteData)
});

// 5. التحقق من النتيجة
if (response.ok) {
  const result = await response.json();
  console.log('Note created with mentions:', result.data.mentions);
  
  // المستخدم @123 سيستلم إشعار تلقائياً
}
```

---

## 📝 ملخص سريع

1. **Endpoint:** `POST /api/crm/notes`
2. **الحقول المطلوبة:**
   - `noteable_type`: `"lead"` | `"contact"` | `"account"` | `"deal"`
   - `noteable_id`: رقم
   - `body`: نص يحتوي على `@user_id` أو `@{user_id}`
3. **صيغة Mention:** `@123` أو `@{123}`
4. **النتيجة:** يتم إنشاء Note وإرسال إشعار للمستخدم المذكور تلقائياً

---

**للمزيد من التفاصيل:** راجع `app/Modules/CRM/Http/Controllers/NoteController.php`

