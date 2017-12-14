<?php

namespace ShyimStoreApi;

use ShyimStoreApi\Components\Packagist\PackagistUpdater;
use ShyimStoreApi\Components\Packagist\PluginVersionUpdater;
use Silex\Provider\DoctrineServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        $this->get('/pluginStore/categories', [$this, 'handleCustomCategories']);
    }

    private function setupServices()
    {
        $this->register(new DoctrineServiceProvider(), [
            'db.default_options' => [
                'driver' => 'pdo_mysql',
                'host' => '127.0.0.1',
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
    }

    public function proxyToShopwareApi(\Exception $e, Request $request)
    {
        die(json_encode($this->proxy($request)));
        return $this->json($this->proxy($request));
    }

    private function proxy(Request $request)
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

        return json_decode($response, true);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function handleCustomCategories(Request $request)
    {
        $response = $this->proxy($request, true);

        $response[] = [
            'categoryId' => 1000,
            'parentId' => null,
            'name' => [
                'de_DE' => 'Composer',
                'en_GB' => 'Composer'
            ]
        ];

        $response[] = [
            'categoryId' => 1001,
            'parentId' => 1000,
            'name' => [
                'de_DE' => '5.2 Plugins',
                'en_GB' => '5.2 Plugins'
            ]
        ];

        $response[] = [
            'categoryId' => 1002,
            'parentId' => 1000,
            'name' => [
                'de_DE' => 'Frontend',
                'en_GB' => 'Frontend'
            ]
        ];

        $response[] = [
            'categoryId' => 1003,
            'parentId' => 1000,
            'name' => [
                'de_DE' => 'Core',
                'en_GB' => 'Core'
            ]
        ];

        $response[] = [
            'categoryId' => 1004,
            'parentId' => 1000,
            'name' => [
                'de_DE' => 'Backend',
                'en_GB' => 'Backend'
            ]
        ];

        return $this->json($response);
    }
}