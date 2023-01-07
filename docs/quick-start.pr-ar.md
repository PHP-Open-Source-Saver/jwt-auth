<div dir="rtl" align="right">

قبل الاستمرار يجب التاكد من انك قمت بتثبيت بكج اللارافل
[Laravel](laravel-installation) او [Lumen](lumen-installation).

### قم بتحديث مودل اليوزر

اولا قم بتضمين `PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject` الى مودل اليوزر,
بعدها قم بانشاء الدوال التالية `getJWTIdentifier()` و `getJWTCustomClaims()`.

المثال التالي سيوضح لك ما تحتاجه بالضبط , مع تغيير حسب متطلباتك او شكل المودل لديك

<div dir="ltr" align="left">

```php
<?php

namespace App;

use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
```

</div>

### قم بضبط جدار التوثيق

*ملاحظة: هذا الضبط لاصدار لارافل 5.2 واعلى.*

داخل ملف `config/auth.php` يجب اجراء بعض التعديلات حتى يمكنك استخدام `jwt` 

قم بعمل التغييرات التالية:

<div dir="ltr" align="left">


```php
'defaults' => [
    'guard' => 'api',
    'passwords' => 'users',
],

...

'guards' => [
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

</div>

هنا سنخبر جدار ال `api` ليقوم باستخدام `jwt` , ونقوم باستخدام جدار `api` 
ليكون بشكل افتراضي.

الان يمكننا استخدام خصائص ووثوقية اللارافل ولكن سيعمل `jwt` بالخلفية

### اضافة بعض الروابط 

نقوم بتعديل ملف `routes/api.php` كالتالي:

<div dir="ltr" align="left">

```php
Route::controller(AuthController::class)->prefix('auth')->middleware('api')->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('me', 'me');
    Route::post('refresh', 'refresh');
});

```

</div>

### نقوم بانشاء متحكم جديد ( كونترولر)

نقوم بانشاء `AuthController`, بتنفيذ الايعاز:

```bash
php artisan make:controller AuthController
```

بعدها نقوم باضافة:

<div dir="ltr" align="left">


```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $credentials = $request->only('email', 'password');

        $token = Auth::guard('api')->attempt($credentials);
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = Auth::guard('api')->user();
        return response()->json([
            'status' => 'success',
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }


    public function logout()
    {
        Auth::guard('api')->logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::guard('api')->user(),
            'authorization' => [
                'token' => Auth::guard('api')->refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    public function me()
    {
        return response()->json(Auth::guard('api')->user());
    }
}

```

</div>

الان يمكنك ارسال الركوست الى (. `http://example.dev/auth/login`) باستخدام معلومات تسجيل صحيحة:

```json
{
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.TJVA95OrM7E2cBab30RMHrHDcEfxjoYZgeFONFh7HgQ",
    "token_type": "bearer",
    "expires_in": 3600
}
```

الان يمكنك استخدام التوكن لاجراء عملية الوثوقية.

### كيفية التأكد من التوكن

هنالك عدة طرق للتأكد من المستخدم عن طريق التوكن

**ارساله بالهيدر**

`Authorization: Bearer eyJhbGciOiJIUzI1NiI...`

**ارساله مع الركوست**

`http://example.dev/me?token=eyJhbGciOiJIUzI1NiI...`

**ارسالة على شكل فورم**

**كوكيز**


</div>