# AmpCacheUrl PHP Library

This PHP library provides a class called `Generator` that allows you to generate cache URLs for the AMP Cache. It is inspired by the `@ampproject/toolbox-cache-url` library in Node.js.

## Installation

You can install this library using Composer. Run the following command:

```
composer require nzm/amp-cache-url
```

## Usage

Here's an example of how to use the `Generator` class:

```php
use Nzm\AmpCacheUrl\Generator;
use Nzm\AmpCacheUrl\ServingMode;

$generator = new Generator();
$domainSuffix = 'cdn.ampproject.org';
$url = 'https://example.com/amp/page.html';
$servingMode = ServingMode::Content;

$cacheUrl = $generator->Generate($domainSuffix, $url, $servingMode);

echo $cacheUrl;
```

This will output the cache URL for the provided URL and serving mode.

### URL Generation

The `Generate` method in the `Generator` class takes the domain suffix, the URL, and an optional serving mode as parameters and returns the cache URL for the given input. It handles the necessary encoding and formatting of the URL.

## Credits

This library is an implementation of the `@ampproject/toolbox-cache-url` library in Node.js. You can find the original Node.js library [here](https://github.com/ampproject/amp-toolbox.git).

## License

This library is released under the [MIT License](LICENSE).