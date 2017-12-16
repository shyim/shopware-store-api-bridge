<?php

namespace ShyimStoreApi;

use ShyimStoreApi\Components\Packagist\PackagistUpdater;
use ShyimStoreApi\Components\Packagist\PluginVersionUpdater;
use ShyimStoreApi\Components\StoreListingService;
use ShyimStoreApi\Controllers\PluginStore;
use Silex\Provider\DoctrineServiceProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Application
 * @package ShyimStoreApi
 */
class Application extends \Silex\Application
{
    const SHOPWARE_API = 'https://api.shopware.com';

    /**
     * Application constructor.
     * @param array $values
     */
    public function __construct(array $values = array())
    {
        parent::__construct($values);
        $this['debug'] = true;
        $this['root_dir'] = dirname(__DIR__, 2);
        $this['storage_dir'] = $this['root_dir'] . '/storage';

        $this->setupServices();

        $this->error([$this, 'proxyToShopwareApi']);
    }

    private function setupServices()
    {
        $this->register(new DoctrineServiceProvider(), [
            'db.default_options' => [
                'driver' => 'pdo_mysql',
                'host' => 'mysql',
                'dbname' => 'store',
                'user' => 'root',
                'password' => 'toor',
                'charset' => 'utf8mb4',
            ],
        ]);

        $this['packagist_plugin_version_updater'] = $this->factory(function($c) {
            return new PluginVersionUpdater($c['db']);
        });

        $this['packagist_sync'] = $this->factory(function($c) {
            return new PackagistUpdater($c['db'], $c['packagist_plugin_version_updater']);
        });

        $this['store_listing'] = $this->factory(function($c) {
            return new StoreListingService($c['db']);
        });

        $this->register(new PluginStore());
    }

    public function proxyToShopwareApi(\Exception $e, Request $request)
    {
        die(json_encode($this->proxy($request)));
        return $this->json($this->proxy($request));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function proxy(Request $request)
    {
        $curl = curl_init(self::SHOPWARE_API . $request->getRequestUri());
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request->getMethod());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $header = [];

        foreach ($request->headers->all() as $key => $value) {
            $header[] = strtoupper($key) . ': ' . $value[0];
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        if ($request->getMethod() !== 'GET') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getContent());
        }

        $response = curl_exec($curl);
        curl_close($curl);

        file_put_contents('./logs/' . md5($request->getRequestUri()) . '.json', json_encode(json_decode($response, true), JSON_PRETTY_PRINT));

        return json_decode($response, true);
    }
}