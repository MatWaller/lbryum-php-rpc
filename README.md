# lbryum-php-rpc - Lbryum libarary with no dependencies 
```
Licence: MIT
Author: Mathew Waller <wallermadev@lbryum.io>
Language: PHP 5.6
```

# Requirements
On the PHP side there are not much requirements, you only need atleast PHP 5.6 and the curl-Extension installed. Optional you need [Composer](http://getcomposer.org) to install this library. 

# Install
First you need to install [Composer](https://getcomposer.org/doc/00-intro.md), after you accomplished this you can go ahead:
```
composer require wallermadev/lbryum-php-rpc
```
Then you can simply include the autoloader and begin using the library:
```php
// Include composer autoloader
require_once 'vendor/autoloader.php';

// Create new instance
$lbryum = new \Lbryum\Client('http://127.0.0.1/', 7777);
$lbryum->getVersion();
```
