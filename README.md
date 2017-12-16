# Bridge between Shopware Store API and our Shopware Shop

## This project is experimental

## What is the goal of this project?

Install and Update composer packages in easiest way directly integrated in the default Plugin Manager, without custom plugins installed on Shop.
In the first step it would be only possible with packagist, but the plan is also integrate custom satis server to install and update company internal plugins using default shopware plugin manager.


## How this works?

This application is between the shopware shop and the shopware store api and adjust the data with the packagist populated data.
The shopware store api url is changeable in config.php

```php
'store' => [
    'apiEndpoint' => 'http://api.localhost',
],
```
[The Response signature has been also to disabled](https://github.com/shopware/shopware/blob/5.3/engine/Shopware/Bundle/PluginInstallerBundle/StoreClient.php#L524) otherwise all requests would be blocked.

I will create for that a Pull Request, to make it configureable in config.php

## What is planed next time?
* Use plugins from custom Satis server
* Reading plugin.xml of latest version to provide more plugin informations
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
![Listing](https://i.imgur.com/wuJ6Fnu.png)
