Let's review some of the options in the `config/jwt.php` file that we published earlier.
I won't go through all of the options here since [the file itself](https://github.com/tymondesigns/jwt-auth/blob/1.0.0-beta.2/config/config.php) is pretty well documented.

First up is:

```php
'secret' => env('JWT_SECRET'),
```

### Configuring Custom TTL for Auth Guards

To set a custom TTL (Time To Live) for each `jwt` guard, you can follow these steps:

For each guard that you want to have its own TTL, add the `ttl` option inside the guard's configuration.

Setting a custom TTL for a guard allows it to use its specific `ttl` value rather than the global `ttl` value defined in `config/jwt.php`. This can be particularly useful when using multiple guards for different providers and you wish to set different TTLs for each one.

Below is an example of how you can configure guards with their respective TTLs:

```php
'guards' => [
    'customers' => [
        'driver' => 'jwt',
        'provider' => 'customers',
        'ttl' => env('JWT_CUSTOMERS_TTL', 15), // Custom TTL for 'customers' guard (15 minutes)
    ],
    'administrators' => [
        'driver' => 'jwt',
        'provider' => 'administrators',
        'ttl' => null, // 'administrators' guard has no expiration
    ],
    // if no 'ttl' is set, it will use the 'ttl' value in `config/jwt.php`
    'users' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

In the above configuration example:

1. The `customers` guard will have a custom TTL of 15 minutes.
2. The `administrators` guard has a `ttl` set to `null`, indicating that it will have no expiration, meaning the token will never automatically expire for this guard.
3. Guards that do not have `ttl` inside the guard config will use the default `ttl` in `config/jwt.php`
4. By setting custom TTLs for different guards, you can have fine-grained control over token expiration and enhance the security and flexibility of your authentication system.

Coming soon...
