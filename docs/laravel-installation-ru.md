### Установка с помощью composer

Запустите следующую команду для загрузки последней версии:

```bash
composer require php-open-source-saver/jwt-auth
```

-------------------------------------------------------------------------------

### Добавление сервис-провайдера (для версии Laravel 5.4 или ниже)

Добавьте сервис-провайдер в массив `providers`, находящийся в `config/app.php` :

```php
'providers' => [

    ...

    PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider::class,
]
```

-------------------------------------------------------------------------------

### Настройка файлов конфигов

Перенесем файлы конфигов к себе в проект. <br>
Выполним следующую команду:

```bash
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
```

Теперь у нас есть файл `config/jwt.php`, в котором мы можем менять настройки пакета.

### Сгенерируйте секретные ключи

Для генерации ключей загляните в [Генерация ключей](generate-secrets-ru.md).
