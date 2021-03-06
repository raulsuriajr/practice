# Composer Boilerplate for Drupal 8
This boilerplate uses the [drupal-composer](https://www.drupal.org/project/bootstrap_barrio) installation process and
[Bootstrap Barrio](https://www.drupal.org/project/bootstrap_barrio) as its base theme.

## Features

- Drupal 8 version: **v8.7.3**
- Bootstrap **v4.3.1**
- With mobile detection library
- Subtheme is already configured, with minimal regions.
- Image Effects and Pipelines are already configured.
- Development mode is already configured. Just Uncomment `lines 780-782` in `settings.php` file.
- Configured Bootstrap 4 SASS
- Gulp workflow and tasks are already configured. See **Theme workflow settings/installation** on how to set up.
- Core and Contrib module dependencies are managed by Composer.
- Third party javascript libraries are managed by NPM

## Getting Started
These instructions will get you a copy of the project up and running on your local machine for development

### # Requirements
- PHP version >= 7.2
- PHP Package Manager (Composer)

### # Drupal Installation
```
NOTE: before running the installation, please make sure to follow the requirements above.
```

1. Clone the project repo 
2. Delete the `.git` folder inside the root directory
3. Run `composer update` in the root directory
4. Create a `settings.php` file by duplicating `default.settings.php`
5. Uncomment `line 537` in your `settings.php` and set `sites/default/files/private` as the value.
6. Install Drupal like normal.
7. After installation configure file system's temporary directory to `tmp` (without trailing slash)
8. Restore from the latest backup. see folder: `sites/default/files/private/backup_migrate`
9. Uncomment `lines 780-782` in `settings.php` to enable development mode
10. Clear cache, Run updb, Run cron

### # Installed Contib Modules
- [Module Filter](https://www.drupal.org/project/module_filter)
- [Backup Migrate](https://www.drupal.org/project/backup_migrate)
- [Admin Toolbar](https://www.drupal.org/project/admin_toolbar)
- [Adminimal Admin Toolbar](https://www.drupal.org/project/adminimal_admin_toolbar)
- [Pathauto](https://www.drupal.org/project/pathauto)
- [Metatag](https://www.drupal.org/project/metatag)
- [Twig Tweak](https://www.drupal.org/project/twig_tweak)
- [Twig Extensions](https://www.drupal.org/project/twig_extensions)
- [Twig Field Value](https://www.drupal.org/project/twig_field_value)
- [Coffee](https://www.drupal.org/project/coffee)
- [D8 Editor Advanced link](https://www.drupal.org/project/editor_advanced_link)
- [Editor Advanced Image](https://www.drupal.org/project/editor_advanced_image)
- [Back to top](https://www.drupal.org/project/editor_advanced_image)
- [Image Effects](https://www.drupal.org/project/image_effects)
- [Image Optimize](https://www.drupal.org/project/imageapi_optimize)
- [reSmush.it](https://www.drupal.org/project/imageapi_optimize_resmushit)

### # Uninstalled Core Modules
Uninstalled modules for optimizations. You can enable if needed in your project.

- Contact
- Comment
- History
- Quick Edit
- Shortcut

### # User 1 Account Details
- Username: `system`
- Password: `Password123`