# Nursery — قاموس الحقول (من السكرينات القديمة فقط)

> **مسميات عربية + حقول DB** — بدون نسخ تصميم ynmodata.

## قائمة الأطفال

| المسمى (قديم) | حقل / ملاحظة |
|---------------|--------------|
| قائمة الأطفال | عنوان الصفحة |
| إضافة طفل | `route: nursery.children.create` |
| جميع الفترات | فلتر `period` (تقارير لاحقاً) |
| فلاتر الفصول (1س، 1أ…) | `classroom_id` |
| إجمالي الأطفال | عدد كل السجلات |
| الحسابات النشطة | `status = active` |
| الحسابات المؤرشفة | `status = inactive` |

## إضافة طفل — المعلومات الأساسية

| المسمى | حقل DB |
|--------|--------|
| الاسم | `nursery_children.name` |
| الجنس | `nursery_children.gender` |
| تاريخ الميلاد ميلادي | `nursery_children.date_of_birth` |
| تاريخ الميلاد هجري | لاحقاً (حساب من الميلادي) |
| الفصل | `nursery_enrollments.classroom_id` |
| صورة الطفل | `attachments` (لاحقاً) |

## المعلومات الصحية

| المسمى | حقل DB |
|--------|--------|
| الحساسية | `nursery_children.allergies` |
| الأمراض | `nursery_children.diseases` |
| إضافة دواء | `nursery_child_medications` — اسم، جرعة، تكرار، وقت، ملاحظة |
| ملاحظات | `nursery_children.health_notes` |

## معلومات ولي الأمر

| المسمى | حقل DB |
|--------|--------|
| اختر ولي أمر | `guardian_id` موجود / أو إنشاء جديد |
| الاسم | `nursery_guardians.name` |
| رقم بطاقة الهوية | `nursery_guardians.national_id` |
| العلاقة | `nursery_children.guardian_relationship` |
| رقم الجوال | `nursery_guardians.phone` |
| البريد الإلكتروني | `nursery_guardians.email` |
| العنوان | `nursery_guardians.address` |
| المنطقة | `nursery_guardians.region` |
| المدينة | `nursery_guardians.city` |
| حفظ وإرسال دعوة انضمام | `nursery_guardians.portal_access_token` — من نموذج/ملف الطفل + dashboard QR |

## حضور (مرجع)

حضور، انصراف، إجازة، غائب، لا يوجد سجل → `nursery_attendance_logs.status`
