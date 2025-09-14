# Laravel Changelog

A Laravel package to manage a changelog file through command-line interface. This package helps you create and maintain a standardized changelog following the [Keep a Changelog](https://keepachangelog.com/) format and [Semantic Versioning](https://semver.org/).

[![Latest Version on Packagist](https://img.shields.io/packagist/v/iperamuna/laravel-changelog.svg?style=flat-square)](https://packagist.org/packages/iperamuna/laravel-changelog)
[![Total Downloads](https://img.shields.io/packagist/dt/iperamuna/laravel-changelog.svg?style=flat-square)](https://packagist.org/packages/iperamuna/laravel-changelog)
[![License](https://img.shields.io/packagist/l/iperamuna/laravel-changelog.svg?style=flat-square)](https://packagist.org/packages/iperamuna/laravel-changelog)

## Features

- Initialize a new changelog file
- Add new releases with semantic versioning
- Add unreleased changes
- Edit existing releases
- Promote unreleased changes to a new release
- Web interface to view the changelog
- Dark mode support

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x

## Installation

You can install the package via composer:

```bash
composer require iperamuna/laravel-changelog
```

After installing the package, publish the configuration file:

```bash
php artisan vendor:publish --tag=laravel-changelog-config
```

And the assets:

```bash
php artisan vendor:publish --tag=laravel-changelog-assets
```

Optionally, you can publish the views:

```bash
php artisan vendor:publish --tag=laravel-changelog-views
```


## Configuration

The package configuration file is located at `config/changelog.php`. Here you can customize:

```php
return [
    // The path where the changelog file will be stored
    'path' => env('CHANGELOG_FILEPATH', base_path()),

    // The name of the changelog file
    'filename' => env('CHANGELOG_FILENAME', 'CHANGELOG.md'),

    // The URL where the changelog will be accessible
    'url' => '/changelog',

    // Whether to secure the changelog with authentication
    'secure' => false,

    // The authentication guard to use when secure is true
    'guard' => 'web',
];
```

### URL Guard Mechanism

The package includes a URL guard mechanism that allows you to secure the changelog page with authentication:

- When `secure` is set to `true`, the changelog page will only be accessible to authenticated users.
- The `guard` option specifies which authentication guard to use (defaults to 'web').
- If `secure` is `false`, the changelog page will be publicly accessible.

This is useful for protecting sensitive changelog information in production environments while keeping it accessible to your team.

## Usage

### Initializing a Changelog

To create a new changelog file:

```bash
php artisan changelog:init
```

This command will guide you through the process of creating a new changelog file. You'll be prompted to:
- Add content to the header section
- Optionally add an Unreleased section

### Adding a New Release

To add a new release to the changelog:

```bash
php artisan changelog:add-release
```

This command will:
- Suggest a new version based on semantic versioning
- Prompt for release details (version, date, URL)
- Allow you to add content to each section (Added, Changed, Fixed, etc.)

### Adding Unreleased Changes

To add changes to the Unreleased section:

```bash
php artisan changelog:add-unreleased
```

### Editing an Existing Release

To edit an existing release:

```bash
php artisan changelog:edit-release
```

This command allows you to modify the content of an existing release.

### Promoting Unreleased Changes

To promote unreleased changes to a new release:

```bash
php artisan changelog:promote-unreleased
```

This command moves changes from the Unreleased section to a new release.

## Web Interface

The package provides a web interface to view the changelog. By default, it's accessible at `/changelog`, but you can customize this in the configuration file.

## Customization

### Views

You can customize the views by publishing them:

```bash
php artisan vendor:publish --tag=laravel-changelog-views
```

The views will be published to `resources/views/vendor/laravel-changelog`.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Author

- [Indunil Peramuna](https://github.com/iperamuna)
