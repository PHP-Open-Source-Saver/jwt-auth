<div dir="rtl" align="right">
## الحقوق
<p dir='rtl' align='right'>
ان هذا المشروع منسوخ من 

[tymonsdesigns/jwt-auth](https://github.com/tymondesigns/jwt-auth/wiki) 
</br> لقد قررنا نسخه بسبب ان صاحب المشروع الاصلي لم يحدثه منذ زمن طويل.
</p>


## <div dir="rtl" align="right">للتحديث الى المستودع الحالي من [`tymondesigns/jwt-auth`](https://github.com/tymondesigns/jwt-auth)</div>

<div dir="rtl" align="right">

ان هذا المشروع يستخدم نيم سبيس مختلف عن,  `tymondesigns/jwt-auth`, ولكن يستخدم نفس الخصائص فلا داعي للقلق:

1) استخدم ايعاز `composer remove tymon/jwt-auth`
   > **ملاحظة** قد تظهر بعض الاخطاء يمكنك تجاهلها.
2) قم باستبدال `Tymon\JWTAuth` ب `PHPOpenSourceSaver\JWTAuth`.
   > **نصيحة**: يمكن استخدام الاختصارات بمحرر النصوص لديك مثلا.  <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>R</kbd>
3) نفذ `composer require php-open-source-saver/jwt-auth`

### ملاحظات

بسبب بعض الاضافات في مشروعنا قد تواجه مشاكل بالتوافقية. _ولكن هذا لن يؤثر على مشروعك بشكل عام_, الا اذا كنت [implicitly disabled autodiscovery](https://laravel.com/docs/8.x/packages#opting-out-of-package-discovery) للبكج الاصلي.

العناصر الغير متوافقة حاليا:
- [`JWTGuard`](src/JWTGuard.php) يحتوي على متغير جديد في دالة الانشاء [`$eventDispatcher`](src/Providers/AbstractServiceProvider.php#L97) 
</div>

## الوثائق

جميع الشروحات متوفرة على [laravel-jwt-auth.readthedocs.io](https://laravel-jwt-auth.readthedocs.io/)

-----------------------------------

## الأمان

اذا وجدت اي ثغرة يمكنك اتباع [بوليصة الامان](https://github.com/PHP-Open-Source-Saver/jwt-auth/security/policy)

## الرخصة

The MIT License (MIT)


</div>