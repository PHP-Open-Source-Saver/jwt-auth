### Install via composer

Run the following command to pull in the latest version:

```bash
composer require php-open-source-saver/jwt-auth
```

-------------------------------------------------------------------------------

### Copy the config

Copy the `config` file from `vendor/php-open-source-saver/jwt-auth/config/config.php` to `config` folder of your Lumen application and rename it to `jwt.php`

Register your config by adding the following in the `bootstrap/app.php` before middleware declaration.

```php
$app->configure('jwt');
```

-------------------------------------------------------------------------------

### Bootstrap file changes

Add the following snippet to the `bootstrap/app.php` file under the providers section as follows:

```php
// Uncomment this line
$app->register(App\Providers\AuthServiceProvider::class);

// Add this line
$app->register(PHPOpenSourceSaver\JWTAuth\Providers\LumenServiceProvider::class);
```

Then uncomment the `auth` middleware in the same file:

```php
$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
]);
```

-------------------------------------------------------------------------------

### Generate secrets

For generating secrets have a look at [generate secrets](generate-secrets.md).