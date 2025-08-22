# phpfit/cache-file

PSR-16 Implementation of simple cache usinig file

## Installation

```bash
composer require phpfit/cache-file
```

## Usage

```php
use PhpFit\CacheFile\File;

$cache = new File(__DIR__ . '/cache');

// Set cache for 12 second
$cache->set('key', 'value', 12);

$value = $cache->get('key', 'default');
```

## License

The phpfit/cache-file library is licensed under the MIT license.
See [License File](LICENSE.md) for more information.
