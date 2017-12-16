# Bridge between Shopware Store API and our Shopware Shop

## What is the goal of this project?

Install and Update composer packages in easiest way directly integrated in the default Plugin Manager, without custom plugins installed on Shop.
In the first step it would be only possible with packagist, but the plan is also integrate custom satis server to install and update company internal plugins.

## How this works?

This application is between the shopware shop and the shopware store api and adjust the data with the packagist populated data.
The shopware store api curl is changeable in config.php

```php
'store' => [
    'apiEndpoint' => 'http://api.localhost',
],
```

## Installation

### Requirements
* PHP 7.0 or higher
* MySQL or MariaDB
* system function allowed
* Unix system installed with zip and git

### Configuration

* Create a new mysql database and import the install.sql and adjust the settings in src/ShyimStoreApi/Application.php
* Run packagist sync command "php bin/console.php packagist:sync"
