# قائمة التحقق من إعداد المتجر الإلكتروني

## خطوات الإعداد الأولي

### 1. إضافة الصلاحيات ✓

- [x] تم إضافة صلاحيات E-Commerce إلى `config/permissions.php`
- [ ] تشغيل `php artisan db:seed --class=PermissionSeeder` لإضافة الصلاحيات الجديدة
- [ ] تشغيل `php artisan db:seed --class=AddECommercePermissionsSeeder` لتحديث Roles الموجودة

### 2. التحقق من الصلاحيات

- [ ] تسجيل الدخول كـ Tenant Owner
- [ ] التحقق من وجود E-Commerce في القائمة الجانبية
- [ ] التحقق من القدرة على الوصول لجميع صفحات E-Commerce

### 3. إنشاء المتجر

- [ ] الانتقال إلى E-Commerce > Stores
- [ ] إنشاء متجر جديد
- [ ] اختيار Theme
- [ ] ضبط الإعدادات (currency, language, etc.)
- [ ] تفعيل المتجر (Is Active)

### 4. إضافة المنتجات

- [ ] التأكد من وجود منتجات في ERP > Products
- [ ] الانتقال إلى E-Commerce > Products
- [ ] اختيار المتجر
- [ ] مزامنة المنتجات (Sync All أو Sync Individual)
- [ ] تحديد الأسعار (ecommerce_price) لكل منتج

### 5. التحقق من المتجر

- [ ] فتح `/storefront/{store-slug}` في متصفح جديد
- [ ] التحقق من ظهور المنتجات
- [ ] إضافة منتج للسلة
- [ ] التحقق من عمل Cart

### 6. اختبار الطلب

- [ ] الذهاب إلى Checkout
- [ ] إدخال بيانات الشحن
- [ ] إنشاء الطلب
- [ ] التحقق من:
  - [ ] إنشاء Order في E-Commerce
  - [ ] إنشاء Sales Order في ERP
  - [ ] إنشاء Customer
  - [ ] ربط Customer بـ CRM Contact

---

## ملاحظات مهمة

1. **Tenant Owner يحصل تلقائياً على جميع الصلاحيات** عند إنشاء Tenant جديد
2. **للتأكد من الصلاحيات للـ Tenants الموجودة**: قم بتشغيل `AddECommercePermissionsSeeder`
3. **المنتجات تأتي من ERP فقط** - لا يمكن إضافتها مباشرة في المتجر
4. **الطلبات تتحول تلقائياً** إلى Sales Orders في ERP
5. **العملاء يتحولون تلقائياً** إلى CRM Contacts

---

## الأوامر المطلوبة

```bash
# إضافة الصلاحيات الجديدة
php artisan db:seed --class=PermissionSeeder

# تحديث Roles الموجودة بصلاحيات E-Commerce
php artisan db:seed --class=AddECommercePermissionsSeeder

# أو تحديث Tenant Owner محدد
php artisan ensure:tenant-owners-have-super-admin-role
```

---

**تاريخ الإنشاء:** 2026-01-22

























