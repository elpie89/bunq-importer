# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## 3.0.0 - 2021-12-12

A fake release to announce that the new **[Firefly III Data Importer](https://github.com/firefly-iii/data-importer/)** has replaced the Spectre importer.

## 2.1.3 - 2021-04-23

### Changed

- Updated underlying dependencies.

## 2.1.2 - 2021-01-03

### Changed

- Updated documentation links and libraries.

## 2.1.1 - 2021-01-02

### Changed

- Updated documentation links and libraries.

## 2.1.0 - 2020-11-29

⚠️ Several changes in this release may break Firefly III's duplication detection or are backwards incompatible.

### Changed

- ⚠️ All environment variables that used to be called "URI" are now called "URL" because I finally learned the difference between a URL and a URI.

## 2.0.4 - 2020-11-24

### Fixed

- [Issue 4080](https://github.com/firefly-iii/firefly-iii/issues/4080) Due to a silly mistake by me, some lines had a `sprintf()` too many while others were missing one.

### Changed

- Minimum version of Firefly III required changed to 5.4.0

## 2.0.3 - 2020-11-19

### Changed

- Upgrade to Laravel 7.0. Please complain [here](https://github.com/bunq/sdk_php/issues/204) about the lack of upgrade to Laravel 8.

## 2.0.2 - 2020-08-01

- Support for reverse proxy through `TRUSTED_PROXIES`

## 2.0.1 - 2020-07-17

### Changed
- [Issue 3569](https://github.com/firefly-iii/firefly-iii/issues/3569) Typo

## 2.0.0 - 2020-07-12

### Changed
- Now requires PHP 7.4. Make sure you update!
- Can now use a vanity URL. See the example environment variables file, `.env.example` for instructions.
- This version requires Firefly III v5.3.0

## 1.0.0 - 2020-05-05

This release was preceded by several alpha and beta versions:

- 1.0.0-alpha.1 on 2020-03-07
- 1.0.0-alpha.2 on 2020-03-08
- 1.0.0-beta.1 on 2020-03-13
- 1.0.0-beta.2 on 2020-04-01

### Added
- Initial release.

### Changed
- Initial release.

### Deprecated
- Initial release.

### Removed
- Initial release.

### Fixed
- Initial release.

### Security
- Initial release.
