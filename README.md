# Bridge between Shopware Store API and your Shopware Shop

## This project is experimental

## What is the goal of this project?

Install and Update composer packaged plugins in integrated Plugin Manager. 
The composer packages can be also on a self-hosted satis server. Easier update company internal plugins like it were in plugin store.

## How this works?

This application is between the shopware shop and the shopware store api and adjust the data with the bridge populated data.
As first step the bridge checkouts all plugins from packagist and optional satis and packages the plugins like in shopware store.
The shopware store api url is changeable in config.php like so

```php
'store' => [
    'apiEndpoint' => 'http://api.localhost',
],
```
[The response signature must be deactivated manually](https://github.com/shopware/shopware/blob/5.3/engine/Shopware/Bundle/PluginInstallerBundle/StoreClient.php#L524) otherwise all requests would be blocked.

I will create for that a Pull Request, to make it configureable in config.php or custom a custom certificate.

## What is planed?
* Restrict shop domains to specific plugins
* Your ideas?

## Installation

### Requirements 
* PHP 7.1.3 or higher
* MySQL or MariaDB
* system function allowed
* Unix system installed with zip and git

### Configuration

* Copy .env_default to .env and adjust the settings
* Run migrations "php bin/console doctrine:migrations:migrate"
* Run packagist sync command "php bin/console packagist:sync"

# Screenshots
![Search](https://i.imgur.com/JQ7eWmX.png)
![Listing](https://i.imgur.com/oEKH9G6.png)
