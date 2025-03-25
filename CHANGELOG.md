# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

You can find and compare releases at the [GitHub release page](https://github.com/PHP-Open-Source-Saver/jwt-auth/releases).

## [Unreleased]
### Fixed
 - Fixed the return type of getMinutesUntilExpired in BlackList, which returned a float instead of an int when using Carbon v2.
 - Fixed PHPStan issue in JWTGenerateSecretCommand by ensuring displayKey($key); is called before returning, avoiding returning a void method.  
 - Fixed missing return true; statements in validatePayload() and validateRefresh() methods of Expiration.php, IssuedAt.php, and NotBefore.php to resolve PHPStan errors.  
 - Fixed PHPStan error related to new static() by refactoring hasAllClaims method in Collection class.


## [2.8.0] 2025-02-11
Please see (https://github.com/PHP-Open-Source-Saver/jwt-auth/releases/tag/2.8.0)

### Added
- #268 Implement config variable to allow iat to remain unchanged claim when refreshing a token
- Adds support for Laravel 12
- Adds CI testing for PHP 8.4
- Don't show jwt secret if show option is false even if the key is updated
- Casts config ints to int by default in new config file publishes
- Override "id" method in JWTGuard

### Removed

- Dropping support for PHP 8.1, if you are still on this version, please update your PHP version in order to use the latest version of this package.

## [2.7.2] 2024-09-28

### Added
- Add `cookie_key_name` config to customize cookie name for authentication
- Delegate `Auth::id()` calls to the newly added `getUserId` method

## [2.7.0] 2024-07-24

### Fixed
- Support for Carbon 3 alongside Carbon 2

## [2.6.0] 2024-07-11

### Added
- New `getUserId` method

## [2.5.0] 2024-07-03

### Added
- Refresh iat claim when refreshing a token

## [2.4.0] 2024-05-27

### Added
- Support for lcobucci/jwt^5.0 (and dropped support for ^4.0)
- SetSecret regenerates config with new secret in the Lcobucci provider

## [2.3.0] 2024-05-09

### Added
- Support for Carbon 3 (and drop Carbon 1, but it was unused anyway)

### Removed
- Dropped support for Laravel < 10 and PHP < 8.1

### Fixed
- Use `id` claim for identify user if `sub` doesn't exists.

## [2.2.0] 2024-03-12

### Added
- Different TTL configurations for each guard
- lcobucci/jwt: add array support for `aud` claim
- Laravel 11 support

## [2.1.0] 2023-02-17

### Added
- Laravel 10 support

## [2.0.0] 2022-09-08
- No changes to 2.0.0-RC1

### Added
- Arabic translation for docs by hawkiq

## [2.0.0-RC1] 2022-08-25

### Added
- Adds Octane Compatibility
- Added `ask-passphrase` parameter to generating certs command
- Support autocomplete guard

### Fixed
- Default config value for `show_black_list_exception` changed to true
- Auth header not ignoring other auth schemes
- Fixed replacing of values using regex

## [1.4.2] 2022-04-22

### Added
- Added exception if secret key or private/public key are missing

### Fixed
- Add command for generating certs

## [1.4.1] - 2022-01-24

### Fixed
- Add more ReturnTypeWillChange for PHP 8.1 compatibility

## [1.4.0] - 2022-01-18

### Added

### Fixed
- Fixes #101 - Secret is not nullable but should be according to the library config boilerplate
- Fixes #99 - Steps for migrating from tymons package

## [1.3.0] - 2022-01-13

### Added
- PHP 8.1 support (#58, #77, #87)
- Typed variables (#52)

### Fixed
- Compatability with Laravel 6 versions below 6.15

## [1.2.0] - 2021-11-16

### Added
- Dispatch Auth Events by @okaufmann in #45

## [1.1.1] - 2021-10-21

### Changed
- Blacklisted token exception no more thrown by default by @Messhias in #32

### Fixed
- ECDSA signers by @josecl in #31

## [1.1.0] - 2021-11-11

### Added
- PHP 8.0 and `lcobucci/jwt` version 4 compatability by @eschricker in #14
- Option to hide Blacklisted Token exception by @Messhias in #7
- Throw exception for invalid encrypted cookies by @eschricker in #22

### Fixed
- Typo in tests by @eschricker in #23

[Unreleased]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/2.0.0...HEAD
[2.0.0]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/1.4.2...2.0.0
[2.0.0-RC1]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/1.4.2...2.0.0-RC1
[1.4.2]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/1.4.1...1.4.2
[1.4.1]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/1.4.0...1.4.1
[1.4.0]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/1.3.0...1.4.0
[1.3.0]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/1.1.1...1.2.0
[1.1.1]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/1.0.2...1.1.0
