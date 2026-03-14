# h2bit/filament-advanced-log-viewer

Advanced Filament v3 log viewer page with:

- sidebar grouped by channel
- channel/date/size friendly list
- sidebar search + date range filtering
- content search + line limits
- Laravel stack trace-style expansion
- JSON pretty print for jobs/fic channels

## Install (from Packagist)

```bash
composer require h2bit/filament-advanced-log-viewer
```

## Install (local path repository)

Add in your project `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "packages/h2bit/filament-advanced-log-viewer"
    }
  ]
}
```

Then:

```bash
composer require h2bit/filament-advanced-log-viewer:@dev
```

Register plugin in your panel provider:

```php
use H2bit\FilamentAdvancedLogViewer\FilamentAdvancedLogViewerPlugin;

->plugins([
    FilamentAdvancedLogViewerPlugin::make(),
])
```

Optional config publish:

```bash
php artisan vendor:publish --tag=filament-advanced-log-viewer-config
```

## Config

`config/filament-advanced-log-viewer.php`

- `navigation_group`, `navigation_label`, `navigation_icon`, `navigation_sort`
- `slug`, `title`
- `logs_glob`
- `required_role`
- `channel_meta`
