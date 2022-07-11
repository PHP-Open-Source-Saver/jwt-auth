### Install via composer

Run the following command to pull in the latest version:

```bash
composer require php-open-source-saver/jwt-auth
```

-------------------------------------------------------------------------------

### Add service provider ( Laravel 5.4 or below )

Add the service provider to the `providers` array in the `config/app.php` config file as follows:

```php
'providers' => [

    ...

    PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider::class,
]
```

-------------------------------------------------------------------------------

### Publish the config

Run the following command to publish the package config file:

```bash
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
```

You should now have a `config/jwt.php` file that allows you to configure the basics of this package.

### Generate secrets

For generating secrets have a look at [generate secrets](generate-secrets.md).