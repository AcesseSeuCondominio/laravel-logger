## Laravel Logger

## Installation

Add private repository

```json
{
  ...
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:AcesseSeuCondominio/laravel-logger.git"
    }
  ]
}
```

Require this package with composer

```shell
composer require acesseseucondominio/laravel-logger:"dev-main" -n
```

### Laravel without auto-discovery:

If you don't use auto-discovery, add the LoggerServiceProvider to the providers array in config/app.php

```php
AcesseSeuCondominio\Logger\LoggerServiceProvider::class,
```

## Configuration

If config/log.php does not exist, create one following this example

```php
<?php

return [
    'application' => 'asc-api', // Update project name
    'git_commit' => @exec('git rev-parse --short HEAD 2>/dev/null'),
    'build_id' => env('BUILD_ID', '0000'),
];

```

