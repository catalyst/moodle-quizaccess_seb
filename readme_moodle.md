# Installing or updating third party libraries.

## Composer dependencies.

This plugin uses Composer to handle dependencies. For instructions on installing Composer on your machine see: https://getcomposer.org/doc/00-intro.md

### Refresh current version.

1. Follow the instructions in getcomposer to install composer globally.
2. In the plugin directory, run:
    1. `composer update`
    2. `composer install`
3. Check that the files in `vendor` directory have been updated.

### Update to higher version.

The versions of the dependencies are defined in composer.json. In order to install a higher version of the a library, follow these steps:

1. Change the version number of the desired library in composer.json.
2. Follow the instructions in getcomposer to install composer globally.
3. In the plugin directory, run:
    1. `composer update`
    2. `composer install`
4. Check that the files in `vendor` directory have been updated.
