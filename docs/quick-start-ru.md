Перед тем как продолжить, убедитесь, что вы установили пакет в соответствии с инструкциями по установке для
[Laravel](laravel-installation-ru.md) или [Lumen](lumen-installation).

### Обновление User модели

Нам нужно добавить контракт `PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject` в модель User,
который требует выполнения двух методов: `getJWTIdentifier()` и `getJWTCustomClaims()`.

В приведенном ниже примере показана основная структура. В ней вы можете делать изменения, если ваш проект этого требует.

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

### Настройка гардов (Auth Guards)

*примечание: будет работать только если вы используете Laravel 5.2 и выше.*

Для использования `jwt` гардов (guards) и подключения аутентификации в ваш проект, нужно внести некоторые изменения в файл `config/auth.php`.

Добавьте в файл следующее:

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

Здесь мы говорим `api` guard использовать `jwt` драйвер. <br>

Теперь мы можем пользоваться встроенной системой аутентификации Laravel с подключенной JWT Auth.


### Добавление основных маршрутов (routes) аутентификации

Добавляем маршруты в файл `routes/api.php`:

```php
Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {

    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');

});
```

### Создание контроллера AuthController

Создадим контроллер `AuthController` вручную или с использованием следующей artisan команды:

```bash
php artisan make:controller AuthController
```

Затем добавим:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
```

Теперь отправим POST-запрос по маршруту login (`http://example.dev/auth/login`). Используя действительные учетные данные мы увидим ответ:

```json
{
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.TJVA95OrM7E2cBab30RMHrHDcEfxjoYZgeFONFh7HgQ",
    "token_type": "bearer",
    "expires_in": 3600
}
```

Этот токен мы можем использовать для отправки аутентифицированных запросов к нашему приложению.

*примечание: Если вы используете Postman для отправки запросов, не забывайте устанавливать значение Type: Bearer Token в drop-out меню*
### Аутентифицированные запросы

Есть несколько способов отправить токен через http:

**Заголовок Authorization**

`Authorization: Bearer eyJhbGciOiJIUzI1NiI...`

**Строка запроса (Query string parameter)**

`http://example.dev/me?token=eyJhbGciOiJIUzI1NiI...`

**Post параметр**

**Куки (Cookies)**

**Laravel маршруты (routes)**
