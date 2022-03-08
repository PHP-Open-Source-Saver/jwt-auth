# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

You can find and compare releases at the GitHub release page.

## [Unreleased]

### Added
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

[Unreleased]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/1.4.0...HEAD
[1.4.0]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/1.3.0...1.4.0
[1.3.0]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/1.1.1...1.2.0
[1.1.1]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/PHP-Open-Source-Saver/jwt-auth/compare/1.0.2...1.1.0
