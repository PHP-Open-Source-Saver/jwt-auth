<div dir="rtl" align="right">

### انشاء مفتاح سري جديد

لقد قمت باضافة الايعاز التالي لانشاء مفتاح جديد:

```bash
php artisan jwt:secret
```

هذا سيقوم بتحديث ملف `.env` ووضع المفتاح السري بشكل `JWT_SECRET=foobar`

سيقوم هذا المفتاح باضافة توقيع الى التوكن المرسل وبالاعتماد على طريقة التشفير المستخدمة.

### انشاء شهادة

لانشاء شهادات قم بتنفيذ الايعاز التالي 

```bash
php artisan jwt:generate-certs
```

سيتم تحديث`.env` ليتم استخدامة مع الشهادات الجدد. 

هذا الايعاز يستقبل العناصر التالية

| الاسم | الوصف |
|---|---|
| force | override existing certificates |
| algo | خوارزمية التشفير rsa او ec |
| bits | Key length for rsa |
| curve | Curve to be used for ec |
| sha | طريقة التشفير |
| passphrase | Passphrase for the cert |
| ask-passphrase | Ask for passphrase instead of passing as parameter |
| dir | مجلد حفظ الشهادات |

#### امثلة 

انشاء 4096 bit rsa شهادة بخوارزمية sha 512

```bash
php artisan jwt:generate-certs --force --algo=rsa --bits=4096 --sha=512
```

Generating a ec certificate with prime256v1-curve and sha 512

```bash
php artisan jwt:generate-certs --force --algo=ec --curve=prime256v1 --sha=512
```

</div>