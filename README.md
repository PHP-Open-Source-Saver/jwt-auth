<a href="README.pr-ar.md">
<img src="https://img.shields.io/badge/lang-ar-blue" />
</a>

## Credits
[This repository is a fork from original tymonsdesigns/jwt-auth](https://github.com/tymondesigns/jwt-auth/wiki), we decided to fork and work independent because the original one was not being updated for long time and keep doing support for the application.

## Migrating from [`tymondesigns/jwt-auth`](https://github.com/tymondesigns/jwt-auth)

This uses different namespace, then `tymondesigns/jwt-auth`, but overall, provides the same API, that makes migration to this repository pretty easy:

1) Run `composer remove tymon/jwt-auth`
   > **Info** An error will appear because the package is still in use, ignore it.
2) Replace all the occurrences of `Tymon\JWTAuth` with `PHPOpenSourceSaver\JWTAuth`.
   > **Tip**: You can use *Find and Replace* feature of your IDE. Try it with <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>R</kbd>
3) Run `composer require php-open-source-saver/jwt-auth`

### Notes

Due to new features, added in our library, there are some incompatibilities. _This won't hurt you in most cases_, unless you have [implicitly disabled autodiscovery](https://laravel.com/docs/11.x/packages#opting-out-of-package-discovery) for original Tymon's package.

Current compatability breaks:
- [`JWTGuard`](src/JWTGuard.php) have new required constructor parameter [`$eventDispatcher`](src/Providers/AbstractServiceProvider.php#L97) 

## Documentation

Full documentation is available at [laravel-jwt-auth.readthedocs.io](https://laravel-jwt-auth.readthedocs.io/)

-----------------------------------

## Security

If you want to disclose a security related issue, please follow our [security policy](https://github.com/PHP-Open-Source-Saver/jwt-auth/security/policy)

## License

The MIT License (MIT)
