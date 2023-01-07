<div dir="rt" align="right">

### التثبيت عن طريق كومبوزر

نفذ الايعاز التالي وقم بتحميل اخر نسخة متوفرة:

```bash
composer require php-open-source-saver/jwt-auth
```

-------------------------------------------------------------------------------

### قم باضافة مستكشف البكج (لارافل اقل من 5.4)

قم باضافة الى مصفوفة `providers` داخل ملف `config/app.php`:

```php
'providers' => [

    ...

    PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider::class,
]
```

-------------------------------------------------------------------------------

### قم بنشر الاعدادات

قم بتنفيذ الايعاز التالي لنقل الاعدادات من مجلد المشروع الى مجلد ملفات الاعدادات:

```bash
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
```

الان الملف موجود في `config/jwt.php` لتغيير اعدادات البكج.

### انشاء مفتاح سري  

لانشاء مفتاح سري يمكنك القاء نظرة على [انشاء مفتاح سري](generate-secrets.pr-ar.md).

</div>